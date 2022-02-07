<?php

use \Mysql\Issue\NodeModel as MysqlIssueNode;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\TreeModel as MysqlIssueTree;
use \Mysql\Issue\ExeRotateModel as MysqlIssueExeRotate;
use \Mysql\Issue\WaitingServerModel as MysqlIssueWaitServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Business\Issue\ServerModel as BusIssueServer;
use \Mysql\Issue\OftenUseModel as MysqlIssueOftenUse;
use \Mysql\Issue\UserModel as MysqlIssueUser;
use \Mysql\Issue\RestModel as MysqlIssueUserRest;
use \ExtInterface\Ldap\ApiModel as ExtLdapApi;
use \ExtInterface\Duty\ApiModel as ExtDutyApi;
use \ExtInterface\IdcMc\ApiModel as ExtIdcMcApi;

/**
 * 进程处理模块
 */
class ProcessController extends \Base\Controller_AbstractCli
{

    private $_way = '';
    private $_php = '';

    /**
     * 根据result和status判断提案进行    php cli/cli.php request_uri='/cli/Process/checkProcess/id/9772/way/web'
     * Status状态,0:未批准,1:已批准,2:重开,3:已关闭,4:已执行
     * Result结果,0:处理中,1:成功,2:失败,3:驳回,4:取消,5:终止
     */
    public function checkProcessAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id']) && isset($param['way'])) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');

            $id = $param['id'];
            $way = $param['way'];

            $serverInfo = MysqlIssueServer::getInstance()->getOneServer(
                array('i_status', 'i_result', 'info_id', 'i_type'), array('id' => $id));
            $this->_way = $way;
            $status = $serverInfo['i_status'];
            $result = $serverInfo['i_result'];

            BusIssueServer::getInstance()->MysqlToRedisServer($id); //更新Redis数据

            //根据提案类型修改执行组
//            $this->__modifyExeInfo($id, $serverInfo['info_id'], $serverInfo['i_type']);

            //审批提案
            if ($status == '0' && $result == '0') {
                $this->__checkStatus($id, $serverInfo['info_id'], $serverInfo['i_type']);
            }

            //执行提案
            if ($status == '1' && $result == '0') {
                $this->__exeStatus($id, $serverInfo['info_id'], $serverInfo['i_type']);
            }

            //完成提案
            if ($status == '4' && $result == '1') {
                $this->__finishStatus($id);
            }

            //驳回提案
            if ($status == '3' && $result == '3') {
                $this->__rejectStatus($id);
            }

            //重开提案
            if ($status == '2' && $result == '0') {
                $this->__checkStatus($id, $serverInfo['info_id'], $serverInfo['i_type']);
            }

            //终止提案
            if ($status == '3' && $result == '5') {
                $this->__stopStatus($id);
            }
        }

    }

    /**
     * 处理提案的审批情况
     * @param $id string
     * @param $infoId string
     * @param $type string
     * @return array
     */
    private function __checkStatus($id, $infoId, $type)
    {
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(
            array('checkJson', 'exeJson'), array('id' => $infoId));

        $checkArr = json_decode($serverInfo['checkJson'], true);
        $checkList = $checkArr['list']['check'] ?? null;
        $csList = $checkArr['list']['cs'] ?? null;

        if (!empty($checkList)) {
            foreach ($checkList as $user => $check) {
                if ($check['status'] == '1') {
                    $data['id'] = $id;
                    $data['user'] = $user;
                    $data['role'] = '审批人';
                    $data['type'] = $type;
                    $data['way'] = $this->_way;
                    $this->saveProcessStatus($data, 'check');
                    break;
                }
            }
        }

        if (!empty($csList)) {
            foreach ($csList as $user => $cs) {
                if ($cs['status'] == '1') {
                    $data['id'] = $id;
                    $data['user'] = $user;
                    $data['role'] = '会签人';
                    $data['type'] = $type;
                    $data['way'] = $this->_way;
                    $this->saveProcessStatus($data, 'check');
                }
            }
        }

        //无审批人提案操作
        if (empty($checkList) && empty($csList)) {

            $updateServer['i_status'] = 1;
            $updateServer['i_result'] = 0;
            MysqlIssueServer::getInstance()->updateServerInfo($updateServer, array('id' => $id));

            $exeInfo = json_decode($serverInfo['exeJson'], true);
            $exeInfo['status'] = 1;
            $exeInfo['list'][key($exeInfo['list'])]['status'] = 1;

            $updateInfo['exeJson'] = json_encode($exeInfo);
            MysqlIssueServerInfo::getInstance()->updateServerInfo($updateInfo, array('id' => $infoId));
            BusIssueServer::getInstance()->MysqlToRedisServer($id);

            $this->__exeStatus($id, $infoId, $type);

            //提案审批后,创建子提案和相关提案
            $this->__createRelateIssue($id);
        }
    }

    /**
     * 处理提案的执行情况
     * @param $id string
     * @param $infoId string
     * @param $type string
     * @return array
     * status执行状态,0:未执行,1:等待分配或领取,2:等待执行,3:执行完毕
     */

    private function __exeStatus($id, $infoId, $type)
    {
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(
            array('exeJson'), array('id' => $infoId));

        $exeArr = json_decode($serverInfo['exeJson'], true);

        //assign receive

        $exeList = $exeArr['list'] ?? null;

        if (!empty($exeList)) {

            //执行任务领取模式的处理
            foreach ($exeList as $exeKey => $exe) {

                if (empty($exe['type'])) {
                    $exe['type']['name'] = 'receive';
                }

                //任务领取模式
                if ($exe['type']['name'] == 'receive') {

                    //执行过程,等待领取状态进行处理
                    if ($exe['status'] == 1) {
                        foreach ($exe['member'] as $user) {
                            $data['id'] = $id;
                            $data['user'] = $user;
                            $data['role_id'] = $exeKey;
                            $data['role'] = $exe['name'];
                            $data['type'] = $type;
                            $data['way'] = $this->_way;
                            $this->saveProcessStatus($data, 'receive');
                        }
                        break;
                    }

                    //执行过程,等待执行状态进行处理
                    if ($exe['status'] == 2) {

                        $receiveInfo = MysqlIssueNode::getInstance()->getOneNode(array('operator', 'operator_agent'),
                            array('server_id' => $id, 'action' => 'receive', 'status' => 1), null, 'id desc'
                        );
                        $operator = empty($receiveInfo['operator_agent']) ? $receiveInfo['operator'] : $receiveInfo['operator_agent'];

                        $data['id'] = $id;
                        $data['user'] = $operator;
                        $data['role_id'] = $exeKey;
                        $data['role'] = $exe['name'];
                        $data['type'] = $type;
                        $data['way'] = $this->_way;
                        $this->saveProcessStatus($data, 'exe');
                    }
                }

                //任务指派模式
                if ($exe['type']['name'] == 'appoint' && $exe['status'] == 1) {

                    try {
                        MysqlIssueServerInfo::getInstance()->beginTransaction();

                        //任务处于等待指派状态 ,status:5
                        $exeArr['list'][$exeKey]['status'] = 5;
                        $updateInfo['exeJson'] = json_encode($exeArr);
                        $updateServerInfo = MysqlIssueServerInfo::getInstance()->updateServerInfo($updateInfo,
                            array('id' => $infoId));
                        if (!$updateServerInfo) {
                            throw new \PDOException("支配任务处理等待指派时,更新当前任务详情时错误,表IssueServerInfo");
                        }

                        $assignUser = $exe['type']['leader'];

                        $data['id'] = $id;
                        $data['user'] = $assignUser;
                        $data['role_id'] = $exeKey;
                        $data['role'] = $exe['name'];
                        $data['type'] = $type;
                        $data['way'] = $this->_way;
                        $this->saveProcessStatus($data, 'appoint');

                        MysqlIssueServerInfo::getInstance()->commitTransaction();

                        BusIssueServer::getInstance()->MysqlToRedisServer($id);

                    } catch (\PDOException $e) {

                        MysqlIssueServerInfo::getInstance()->rollbackTransaction();
                        $this->__sendErrorMsg($e->getMessage());
                    }

                }

                //执行任务自动分配模式的处理
                if ($exe['type']['name'] == 'assign' && $exe['status'] == 1) {

                    $exeRotateList = MysqlIssueExeRotate::getInstance()->getRotateListByWhere(array(
                        'name' => $type, 'status' => 1, 'exe' => $exeKey));

                    if (!empty($exeRotateList)) {

                        $ExeQueue = array();
                        foreach ($exeRotateList as $key => $exeRotateInfo) {
                            if ($exeRotateInfo['sign'] == 1) {
                                $currentId = $exeRotateInfo['id'];
                                $x = 0;
                                $n = count($exeRotateList);
                                while ($x < $n) {
                                    if ($exeRotateList[$key + $x]['now'] < $exeRotateList[$key + $x]['max']) {
                                        $data = array();
                                        $data['id'] = $exeRotateList[$key + $x]['id'];
                                        $data['now'] = $exeRotateList[$key + $x]['now'];
                                        $data['user'] = $exeRotateList[$key + $x]['user'];
                                        $ExeQueue[] = $data;
                                    }
                                    if ($key + $x == $n - 1) {
                                        $key = $key - $n;
                                    }
                                    $x++;
                                }
                            }
                        }

                        if (empty($ExeQueue)) {

                            try {
                                MysqlIssueServerInfo::getInstance()->beginTransaction();

                                //任务处于等待分配状态 ,status:4
                                $exeArr['list'][$exeKey]['status'] = 4;
                                $updateInfo['exeJson'] = json_encode($exeArr);
                                $updateServerInfo = MysqlIssueServerInfo::getInstance()->updateServerInfo($updateInfo,
                                    array('id' => $infoId));
                                if (!$updateServerInfo) {
                                    throw new \PDOException("自动分配任务处理出现任务等待时,更新当前任务详情时错误,表IssueServerInfo");
                                }

                                $data['server_id'] = $id;
                                $data['name'] = $type;
                                $data['exe'] = $exeKey;
                                $data['status'] = 0;
                                $addWaitingServer = MysqlIssueWaitServer::getInstance()->addNewWaiting($data);
                                if (!$addWaitingServer) {
                                    throw new \PDOException("自动分配任务处理出现任务等待时,添加新等待任务出错,表IssueWaitingServer");
                                }

                                MysqlIssueServerInfo::getInstance()->commitTransaction();

                                BusIssueServer::getInstance()->MysqlToRedisServer($id);

                            } catch (\PDOException $e) {

                                MysqlIssueServerInfo::getInstance()->rollbackTransaction();
                                $this->__sendErrorMsg($e->getMessage());
                            }

                        } else {

                            $currentUser = $ExeQueue[0]['user'];
                            $currentNow = $ExeQueue[0]['now'];
                            if (isset($ExeQueue[1]['id'])) {
                                $nextId = $ExeQueue[1]['id'];
                            } else {
                                $nextId = $ExeQueue[0]['id'];
                            }

                            try {

                                MysqlIssueExeRotate::getInstance()->beginTransaction();

                                $updateCurrentRotate = MysqlIssueExeRotate::getInstance()->updateRotateInfo(
                                    array('sign' => 0, 'now' => $currentNow + 1), array('id' => $currentId));
                                if (!$updateCurrentRotate) {
                                    throw new \PDOException("自动分配任务处理时,更新当前任务人时错误,表IssueExeRotate");
                                }
                                $updateNextRotate = MysqlIssueExeRotate::getInstance()->updateRotateInfo(
                                    array('sign' => 1), array('id' => $nextId));
                                if (!$updateNextRotate) {
                                    throw new \PDOException("自动分配任务处理时,更新下一任务人时错误,表IssueExeRotate");
                                }

                                $exeArr['list'][$exeKey]['status'] = 2;
                                $updateInfo['exeJson'] = json_encode($exeArr);
                                $updateServerInfo = MysqlIssueServerInfo::getInstance()->updateServerInfo($updateInfo, array('id' => $infoId));
                                if (!$updateServerInfo) {
                                    throw new \PDOException("自动分配任务处理时,更新提案详细内容数据,表IssueServerInfo");
                                }

                                MysqlIssueExeRotate::getInstance()->commitTransaction();

                            } catch (\PDOException $e) {

                                MysqlIssueExeRotate::getInstance()->rollbackTransaction();
                                $this->__sendErrorMsg($e->getMessage());
                            }

                            BusIssueServer::getInstance()->MysqlToRedisServer($id);
                            //邮件提示当前任务执行人
                            $data['id'] = $id;
                            $data['user'] = $currentUser;
                            $data['role_id'] = $exeKey;
                            $data['role'] = $exe['name'];
                            $data['type'] = $type;
                            $data['way'] = $this->_way;

                            $this->saveProcessStatus($data, 'exe');
                        }

                    } else {

                        $exeArr['list'][$exeKey]['status'] = 2;
                        $updateInfo['exeJson'] = json_encode($exeArr);
                        $updateServerInfo = MysqlIssueServerInfo::getInstance()->updateServerInfo($updateInfo, array('id' => $infoId));
                        if (!$updateServerInfo) {
                            throw new \PDOException("自动分配任务处理时,更新提案详细内容数据,表IssueServerInfo");
                        }
                        BusIssueServer::getInstance()->MysqlToRedisServer($id);

                        $data['id'] = $id;
                        $data['user'] = $exe['member'][0];
                        $data['role_id'] = $exeKey;
                        $data['role'] = $exe['name'];
                        $data['type'] = $type;
                        $data['way'] = $this->_way;

                        $this->saveProcessStatus($data, 'exe');
                    }

                }
            }
        }
    }

    /**
     * 处理提案的弄回情况
     * @param $id string
     * @return array
     */
    private function __rejectStatus($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(array('user', 'i_type'), array('id' => $id));

        $this->__sendMailMsg($id, $server['user'], 'reject', $server['i_type']);
    }

    /**
     * 处理提案的完结情况
     * @param $id string
     * @return array
     */
    private function __finishStatus($id)
    {
        $isFinish = MysqlIssueNode::getInstance()->getOneNode(array('id'), array('action' => 'finish', 'server_id' => $id));
        if (!empty($isFinish)) {
            die;
        }

        $serverArr = MysqlIssueServer::getInstance()->getOneServer(array('user', 'i_type'), array('id' => $id));

        if (!empty($serverArr)) {

            $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('server_type', 'way', 'start_time', 'end_time'),
                array('server_id' => $id, 'action' => 'create'));
            $finish['server_id'] = $id;
            $finish['server_type'] = $nodeInfo['server_type'];
            $finish['role'] = '系统';
            $finish['operator'] = 'pms';
            $finish['operator_name'] = '系统';
            $finish['action'] = 'finish';
            $finish['status'] = 1;
            $finish['remarks'] = '系统自动关闭';
            $finish['way'] = $nodeInfo['way'];
            $finish['start_time'] = $nodeInfo['start_time'];
            $finish['end_time'] = $nodeInfo['end_time'];
            MysqlIssueNode::getInstance()->addNewNode($finish);

            MysqlIssueServer::getInstance()->updateServerInfo(array('exe_person' => 'pms',
                'u_time' => date('Y-m-d H:i:s')), array('id' => $id));

            $this->__sendMailMsg($id, $serverArr['user'], 'finish', $nodeInfo['server_type']);

            BusIssueServer::getInstance()->MysqlToRedisServer($id);

            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(array('type'), array('name' => $serverArr['i_type']));

            $typeInfo = MysqlIssueOftenUse::getInstance()->getOneOftenUse(array('typeJson'),
                array('type' => $treeInfo['type'], 'user' => $serverArr['user'])
            );

            if (!empty($typeInfo)) {

                $typeArr = json_decode($typeInfo['typeJson'], true);
                if (!in_array($serverArr['i_type'], $typeArr)) {
                    $typeArr[] = $serverArr['i_type'];
                }

                $data['typeJson'] = json_encode($typeArr);
                MysqlIssueOftenUse::getInstance()->updateOftenUseInfo(
                    $data, array('type' => $treeInfo['type'], 'user' => $serverArr['user'])
                );

            } else {
                $data['user'] = $serverArr['user'];
                $data['type'] = $treeInfo['type'];
                $data['typeJson'] = json_encode(array($serverArr['i_type']));
                MysqlIssueOftenUse::getInstance()->addNewOftenUse($data);
            }

            //结束时,
            $this->__statisticsData($id);

            $this->__sendMsgToIdcMc($id);

        }
    }

    /**
     * 变更类提案执行开始时,发送通知到综合管理平台系统
     * @param $id string
     */
    private function __sendMsgToIdcMc($id)
    {

        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_type'), array('id' => $id));

        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(array('type'), array('name' => $server['i_type']));
        //变更类型的提案推送信息
        if ($treeInfo['type'] == 'change') {
            $postData = array('msg' => $server['i_title'], 'url' => $id, 'time' => time(), "from" => "pms");

            $pushRes = ExtDutyApi::getInstance()->finishMsg($postData);

            if ($pushRes === false || $pushRes['status'] != 200) {
                $error = json_encode($pushRes, JSON_UNESCAPED_UNICODE);
                $this->__sendErrorMsg("sendMsgToIdcMc,提案ID:{$id},发送消息失败,错误信息:{$error}");
            }
        }

    }

    /**
     * 处理提案的终止情况
     * @param $id string
     * @return array
     */
    private function __stopStatus($id)
    {
        $isStop = MysqlIssueNode::getInstance()->getOneNode(array('id'), array('action' => 'stop', 'server_id' => $id));
        if (empty($isStop)) {
            die;
        }

        $server = MysqlIssueServer::getInstance()->getOneServer(array('user', 'i_type', 'c_time', 'u_time'),
            array('id' => $id));

        if (!empty($server)) {

            $finish = array();
            $finish['server_id'] = $id;
            $finish['server_type'] = $server['i_type'];
            $finish['role'] = '系统';
            $finish['operator'] = 'pms';
            $finish['operator_name'] = '系统';
            $finish['action'] = 'finish';
            $finish['status'] = 1;
            $finish['remarks'] = '系统自动关闭';
            $finish['way'] = $this->_way;
            $finish['start_time'] = $server['c_time'];
            $finish['end_time'] = $server['u_time'];

            MysqlIssueNode::getInstance()->addNewNode($finish);

            $this->__sendMailMsg($id, $server['user'], 'stop', $server['i_type']);
//            $this->__statisticsData($id);

            $this->__checkStop($id);

        }
    }

    private function __checkStop($id)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/Autocreate/stop/id/{$id}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 检测等待任务
     */
    public function checkWaitingAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id']) && isset($param['type']) &&
            isset($param['exe']) && isset($param['user']) && isset($param['way'])
        ) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');

            $id = $param['id'];
            $type = $param['type'];
            $exe = $param['exe'];
            $user = $param['user'];
            $way = $param['way'];

            $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('operator_agent'),
                array('server_id' => $id, 'action' => 'exe', 'operator' => $user));
            if (!empty($nodeInfo['operator_agent'])) {
                $user = $nodeInfo['operator_agent'];
            }

            $exeRotateInfo = MysqlIssueExeRotate::getInstance()->getOneRotateInfoByWhere(
                array('name' => $type, 'exe' => $exe, 'user' => $user));

            if (!empty($exeRotateInfo)) {
                $num = $exeRotateInfo['now'] - 1;

                MysqlIssueExeRotate::getInstance()->updateRotateInfo(
                    array('now' => $num), array('name' => $type, 'exe' => $exe, 'user' => $user));

                $waitingServer = MysqlIssueWaitServer::getInstance()->getOneWaitingInfoByWhere(
                    array('name' => $type, 'exe' => $exe, 'status' => 0)
                );

                if (!empty($waitingServer)) {
                    $id = $waitingServer['server_id'];

                    $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(
                        array('exeJson'), array('id' => $id));

                    $exeArr = json_decode($serverInfo['exeJson'], true);

                    foreach ($exeArr['list'] as $key => $exeInfo) {
                        if ($key == $exe) {
                            $exeName = $exeInfo['name'];
                            $exeArr['list'][$key]['status'] = 2;
                        }
                    }
                    $updateInfo['exeJson'] = json_encode($exeArr);
                    MysqlIssueServerInfo::getInstance()->updateServerInfo($updateInfo, array('id' => $id));

                    MysqlIssueExeRotate::getInstance()->updateRotateInfo(
                        array('now' => $num + 1), array('name' => $type, 'exe' => $exe, 'user' => $user));

                    $update['status'] = 1;
                    $update['u_time'] = date('Y-m-d H:i:s');
                    MysqlIssueWaitServer::getInstance()->updateWaitingInfo($update,
                        array('server_id' => $id));
                    BusIssueServer::getInstance()->MysqlToRedisServer($id);
                    //邮件提示当前任务执行人
                    $data['id'] = $id;
                    $data['user'] = $user;
                    $data['role_id'] = $exe;
                    $data['role'] = $exeName;
                    $data['type'] = $type;
                    $data['way'] = $way;
                    $this->saveProcessStatus($data, 'exe');
                }
            }

        }
    }

    /**
     * 保存提案进度数据
     * @param $data array
     * @param $action string
     * @return array
     */
    private function saveProcessStatus($data, $action)
    {
        if (substr($data['user'], 0, 4) == 'api_') {
            $userInfo['cname'] = 'API';
        } else {
            $userInfo = MysqlIssueUser::getInstance()->getOneUserInfo(array('cname'), array('user' => $data['user']));
            if (empty($userInfo['cname'])) {
                $info = ExtLdapApi::getInstance()->getAllUserInfo($data['user']);
                $userInfo['cname'] = $info['name'];
            }
        }

        $agent = '';
        $operator = $data['user'];

        $restInfo = MysqlIssueUserRest::getInstance()->getOneRestInfo(array('check_agent', 'exe_agent'),
            array('user' => $data['user'], 'end >=' => date('Y-m-d H:i:s'), 'start <=' => date('Y-m-d H:i:s')), null, 'id desc');
        if (!empty($restInfo)) {
            if ($action == 'check') {
                $operator = $restInfo['check_agent'];
            } else {
                $operator = $restInfo['exe_agent'];
            }
            $agent = $data['user'];
        }

        $node['server_id'] = $data['id'];
        $node['server_type'] = $data['type'];
        $node['operator'] = $operator;
        $node['role_id'] = empty($data['role_id']) ? '' : $data['role_id'];
        $node['role'] = $data['role'];
        $node['action'] = $action;
        $node['status'] = 0;

        $exist = MysqlIssueNode::getInstance()->getOneNode(array('id', 'operator_agent'), $node);
        if (empty($exist)) {
            $node['operator_name'] = $userInfo['cname'];
            $node['operator_agent'] = $agent;
            $node['remarks'] = '';
            $node['way'] = $data['way'];
            $node['start_time'] = date('Y-m-d H:i:s');
            MysqlIssueNode::getInstance()->addNewNode($node);
            if (substr($operator, 0, 4) == 'api_') {
                $this->__setApi($data['id'], $operator);
            } else {
                $this->__sendMailMsg($data['id'], $operator, $action, $data['type']);
            }
        } else {
            if (!empty($exist['operator_agent'])) {
                $set['operator_name'] = $userInfo['cname'];
                $set['operator_agent'] = '';
                MysqlIssueNode::getInstance()->updateNodeInfo($set, $node);
            }
        }
    }

    /*********** 创建子,相关提案的模块 **********/
    /**
     * 审批后自动创建子提案和相关提案
     */
    public function createIssueAction()
    {
        $param = $this->getRequest()->getParams();

        if (!empty($param['id'])) {

            $this->__createRelateIssue($param['id']);

        } else {
            $msg = "创建相关提案或子提案时传入参数id为空";
            $this->__sendErrorMsg($msg);
        }
    }

    /**
     * 创建子,相关提案
     * @param $id string
     * @return array
     */
    private function __createRelateIssue($id)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "/usr/bin/nohup {$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/Autocreate/create/id/{$id}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 发送通知邮件
     * @param $id string
     * @param $user string
     * @param $action string
     * @param $type string
     */
    private function __sendMailMsg($id, $user, $action, $type)
    {
        //给IT系统发送邮件通知,不需要时直接删除该代码
        $email = 'email';
        if (in_array($type, array('installsystem', 'maintenance', 'privilege', 'softwaretransfer', 'staffleaving',
            'createaccount', 'createmailgroup', 'modifymailgroup', 'createpublicmail'))) {
            $email = 'itemail';
        }

        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "/usr/bin/nohup {$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/{$email}/sendMsgEmail/id/{$id}/user/{$user}/action/{$action}' &";
//        $cmd = " {$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/{$email}/sendMsgEmail/id/{$id}/user/{$user}/action/{$action}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 发送通知邮件
     * @param $id string
     */
    private function __statisticsData($id)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "/usr/bin/nohup {$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/Statistics/addIssueTime/id/{$id}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 发送通知邮件
     * @param $msg string
     * @return array
     */
    private function __sendErrorMsg($msg)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "/usr/bin/nohup {$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendErrorEmail/msg/{$msg}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 执行外部API
     * @param $id string
     * @param $api string
     * @return array
     */
    private function __setApi($id, $api)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "/usr/bin/nohup  {$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/extapi/setApi/id/{$id}/api/{$api}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 销毁类实例
     */
    function __destruct()
    {
    }


}