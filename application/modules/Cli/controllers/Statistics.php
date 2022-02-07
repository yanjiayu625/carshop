<?php

use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\CommonModel as MysqlIssueCommon;
use \Mysql\Issue\NodeModel as MysqlIssueNode;
use \Mysql\Issue\TreeModel as MysqlIssueTree;
use \Mysql\Issue\StatisticsModel as MysqlIssueStatistics;
use \ExtInterface\Cmdb\NewapiModel as ExtCmdbApi;

/**
 * 常用功能,
 */
class StatisticsController extends \Base\Controller_AbstractCli
{

    private $_issueType = array('batchonline', 'batchoffline', 'batchmigrate', 'serverfailures', 'serverosrefresh',
        'yidianaccessifeng', 'firmwarechanges', 'networkmapping', 'applynat', 'batchexe', 'wirelessnetwork',
        'intnetwork', 'coreequipment', 'batchonlineimplement', 'batchonlineshelves', 'batchonlinenewwork', 'batchonlinevsan',
        'batchonlineidcos', 'batchonlineservercheck', 'batchonlinevsancheck', 'batchofflineserver', 'batchofflinecmdb',
        'batchofflinenetwork', 'batchmigrateonlinereinstall', 'batchmigrateonline', 'batchmigrateserver');

    /**
     * 初始化审批和执行时间 php cli/cli.php request_uri='/cli/Statistics/checkAndExeTime'
     */
    public function checkAndExeTimeAction()
    {
        $treeArr = array();
        $treeList = MysqlIssueTree::getInstance()->getTreeList(array('name', 'level3', 'level4'),
            array('name' => $this->_issueType));
        foreach ($treeList as $tree) {
            $treeArr[$tree['name']] = $tree['level3'] . ' - ' . $tree['level4'];
        }

        $serverList = MysqlIssueServer::getInstance()->getServerList(array('id', 'i_type', 'i_title', 'c_time', 'u_time'),
            array('i_type' => $this->_issueType, 'i_status' => 4, 'i_result' => 1));

        foreach ($serverList as $server) {

            $nodeList = MysqlIssueNode::getInstance()->getNodeList(array('operator', 'operator_name', 'action', 'start_time', 'end_time'),
                array('server_id' => $server['id'], 'status' => 1));

            $noCheck = true;
            $checkEndTime = 0;
            $exeStartTime = 0;
            $checkPersonArr = array();
            $exePersonArr = array();
            $startExe = true;

            foreach ($nodeList as $node) {

                if ($node['action'] == 'check') {
                    if ($checkEndTime < strtotime($node['end_time'])) {
                        $checkEndTime = strtotime($node['end_time']);
                        $noCheck = false;
                    }
                    $checkT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                    $checkPersonArr[] = array('user' => "{$node['operator_name']}({$node['operator']})",
                        'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $checkT);
                }
                if (in_array($node['action'], array('appoint', 'receive', 'exe')) && $startExe) {
                    $exeStartTime = strtotime($node['start_time']);
                    $startExe = false;
                }
                if ($node['action'] == 'appoint') {
                    $appointT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                    $exePersonArr[] = array('user' => "[指派]{$node['operator_name']}({$node['operator']})",
                        'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $appointT);
                }
                if ($node['action'] == 'receive') {
                    $receiveT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                    $exePersonArr[] = array('user' => "[领取]{$node['operator_name']}({$node['operator']})",
                        'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $receiveT);
                }
                if ($node['action'] == 'exe' && substr($node['operator'], 0, 4) != 'api_' && $node['operator'] != 'pms') {
                    $exeT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                    $exePersonArr[] = array('user' => "[执行]{$node['operator_name']}({$node['operator']})",
                        'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $exeT);
                }

            }
            if ($noCheck) {
                $checkEndTime = strtotime($server['c_time']);
            }

            $checkTime = $this->__setSpanTime($checkEndTime - strtotime($server['c_time']));
            $exeTime = $this->__setSpanTime(strtotime($server['u_time']) - $exeStartTime);

            $add = MysqlIssueStatistics::getInstance()->addNewStatistics(
                array(
                    'server_id' => $server['id'],
                    'server_type' => $server['i_type'],
                    'type_name' => $treeArr[$server['i_type']],
                    'server_title' => $server['i_title'],
                    'check_time' => $checkEndTime - strtotime($server['c_time']),
                    'check_time_str' => $checkTime,
                    'check_person' => json_encode($checkPersonArr, JSON_UNESCAPED_UNICODE),
                    'exe_time' => strtotime($server['u_time']) - $exeStartTime,
                    'exe_time_str' => $exeTime,
                    'exe_person' => json_encode($exePersonArr, JSON_UNESCAPED_UNICODE),
                    'c_time' => $server['c_time'],
                    'status' => 0,
                )
            );
            if (!$add) {
                echo "记录ID:{$server['id']}有误\n";
            }
        }

    }

    /**
     * 提案完成时,添加审批和执行时间 php cli/cli.php request_uri='/cli/Statistics/addIssueTime/id/22'
     */

    public function addIssueTimeAction()
    {
        $param = $this->getRequest()->getParams();
        if (empty($param['id'])) {
            die;
        }

        $serverInfo = MysqlIssueServer::getInstance()->getOneServer(array('id', 'i_type', 'i_title', 'c_time', 'u_time'),
            array('i_type' => $this->_issueType, 'i_status' => 4, 'i_result' => 1, 'id' => $param['id']));
        if (empty($serverInfo)) {
            die;
        }

        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(array('level3', 'level4'),
            array('name' => $serverInfo['i_type']));
        $typeName = $treeInfo['level3'] . ' - ' . $treeInfo['level4'];

        $nodeList = MysqlIssueNode::getInstance()->getNodeList(array('operator', 'operator_name', 'action',
            'start_time', 'end_time'), array('server_id' => $serverInfo['id'], 'status' => 1));

        $noCheck = true;
        $checkEndTime = 0;
        $exeStartTime = 0;
        $checkPersonArr = array();
        $exePersonArr = array();
        $startExe = true;

        foreach ($nodeList as $node) {

            if ($node['action'] == 'check') {
                if ($checkEndTime < strtotime($node['end_time'])) {
                    $checkEndTime = strtotime($node['end_time']);
                    $noCheck = false;
                }
                $checkT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                $checkPersonArr[] = array('user' => "{$node['operator_name']}({$node['operator']})",
                    'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $checkT);
            }
            if (in_array($node['action'], array('appoint', 'receive', 'exe')) && $startExe) {
                $exeStartTime = strtotime($node['start_time']);
                $startExe = false;
            }
            if ($node['action'] == 'appoint') {
                $appointT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                $exePersonArr[] = array('user' => "[指派]{$node['operator_name']}({$node['operator']})",
                    'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $appointT);
            }
            if ($node['action'] == 'receive') {
                $receiveT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                $exePersonArr[] = array('user' => "[领取]{$node['operator_name']}({$node['operator']})",
                    'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $receiveT);
            }
            if ($node['action'] == 'exe' && substr($node['operator'], 0, 4) != 'api_' && $node['operator'] != 'pms') {
                $exeT = $this->__setSpanTime(strtotime($node['end_time']) - strtotime($node['start_time']));
                $exePersonArr[] = array('user' => "[执行]{$node['operator_name']}({$node['operator']})",
                    'second' => strtotime($node['end_time']) - strtotime($node['start_time']), 'time' => $exeT);
            }

        }
        if ($noCheck) {
            $checkEndTime = strtotime($serverInfo['c_time']);
        }

        $checkTime = $this->__setSpanTime($checkEndTime - strtotime($serverInfo['c_time']));
        $exeTime = $this->__setSpanTime(strtotime($serverInfo['u_time']) - $exeStartTime);

        $add = MysqlIssueStatistics::getInstance()->addNewStatistics(
            array(
                'server_id' => $param['id'],
                'server_type' => $serverInfo['i_type'],
                'type_name' => $typeName,
                'server_title' => $serverInfo['i_title'],
                'check_time' => $checkEndTime - strtotime($serverInfo['c_time']),
                'check_time_str' => $checkTime,
                'check_person' => json_encode($checkPersonArr, JSON_UNESCAPED_UNICODE),
                'exe_time' => strtotime($serverInfo['u_time']) - $exeStartTime,
                'exe_time_str' => $exeTime,
                'exe_person' => json_encode($exePersonArr, JSON_UNESCAPED_UNICODE),
                'c_time' => $serverInfo['c_time'],
                'status' => 0,
            )
        );
        if (!$add) {
            $this->__sendErrorMsg("ID:{$serverInfo['id']} 的统计信息保存错误");
        }


        //各种分类提案数据的统计处理

        switch ($serverInfo['i_type']) {
            case 'serverfailures':
                $this->__repairInfo($param['id']);
                break;

            default:

                break;
        }

    }


    /**
     * 处理完成报修提案的数据
     * @param $id string
     */
    private function __repairInfo($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(array('id', 'info_id', 'c_time', 'u_time'),
            array('i_type' => 'serverfailures', 'i_status' => 4, 'i_result' => 1, 'id' => $id));

        $ipArr = array();

        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);
        $machineInfo = $infoArr['t_custom_machine'];
        $intArr = array();
        preg_match_all('#<td>(.+?)</td>#', $machineInfo, $intArr);
        preg_match_all('#<td colspan=\'3\'>(.+?)</td>#', $machineInfo, $typeArr);

        $type = array();
        foreach ($typeArr[1] as $k => $typeInfo) {
            if ($k % 3 == 0) $type[] = $typeInfo;
        }

        foreach ($intArr[1] as $k => $tdInfo) {
            if ($k % 5 == 0) {
                $ipArr[] = array('id' => $server['id'], 'ip' => $tdInfo,
                    'date' => substr($server['c_time'], 0, 10), 'type' => $type[$k / 5]);
            }
        }

        foreach ($ipArr as $intIp) {
            $cmdbInfo = ExtCmdbApi::getInstance()->getPhysicsServerInfoByIp($intIp['ip']);
            $repair = array();
            $tArr = explode(',', $intIp['type']);
            foreach ($tArr as $t) {
                $repair['server_id'] = $intIp['id'];

                $repair['sn'] = $cmdbInfo['data']['sn']??'';
                $repair['ip'] = $intIp['ip'];
                $repair['type'] = $t;
                $repair['brand'] = $cmdbInfo['data']['brand']??'';
                $repair['model'] = $cmdbInfo['data']['model']??'';
                $repair['date'] = $intIp['date'];

                MysqlIssueCommon::getInstance()->addNewData('IssueStatisticsRepair', $repair);
            }
        }
    }


    /**
     * 初始化服务器固件报修信息 php cli/cli.php request_uri='/cli/Statistics/initRepairInfo'
     */
    public function initRepairInfoAction()
    {
        $serverList = MysqlIssueServer::getInstance()->getServerList(array('id', 'info_id', 'c_time', 'u_time'),
            array('i_type' => 'serverfailures', 'i_status' => 4, 'i_result' => 1,));


        $ipArr = array();
        foreach ($serverList as $server) {
            $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $server['info_id']));
            $infoArr = json_decode($serverInfo['infoJson'], true);
            $machineInfo = $infoArr['t_custom_machine'];
            $intArr = array();
            preg_match_all('#<td>(.+?)</td>#', $machineInfo, $intArr);
            preg_match_all('#<td colspan=\'3\'>(.+?)</td>#', $machineInfo, $typeArr);

            $type = array();
            foreach ($typeArr[1] as $k => $typeInfo) {
                if ($k % 3 == 0) $type[] = $typeInfo;
            }

            foreach ($intArr[1] as $k => $tdInfo) {
                if ($k % 5 == 0) {
                    $ipArr[] = array('id' => $server['id'], 'ip' => $tdInfo,
                        'date' => substr($server['c_time'], 0, 10), 'type' => $type[$k / 5]);
                }
            }
        }
        foreach ($ipArr as $intIp) {
            $cmdbInfo = ExtCmdbApi::getInstance()->getPhysicsServerInfoByIp($intIp['ip']);
            $repair = array();
            $tArr = explode(',', $intIp['type']);
            foreach ($tArr as $t) {
                $repair['server_id'] = $intIp['id'];

                $repair['sn'] = $cmdbInfo['data']['sn']??'';
                $repair['ip'] = $intIp['ip'];
                $repair['type'] = $t;
                $repair['brand'] = $cmdbInfo['data']['brand']??'';
                $repair['model'] = $cmdbInfo['data']['model']??'';
                $repair['date'] = $intIp['date'];

                MysqlIssueCommon::getInstance()->addNewData('IssueStatisticsRepair', $repair);
            }

        }

    }

    /**
     * 处理秒的换算
     * @param $second string
     * @return string
     */
    private function __setSpanTime($second)
    {
        $day = 86400;
        if ($second > $day) {
            $d = floor($second / $day);
            $h = floor(($second - $d * $day) / 3600);
            $time = $d . '天' . $h . '小时';
        } else {
            $h = floor($second / 3600);
            $m = floor(($second - $h * 3600) / 60);
            $time = $h . '小时' . $m . '分钟';
        }
        return $time;
    }

    /**
     * 发送通知邮件
     * @param $msg string
     * @return array
     */
    private function __sendErrorMsg($msg)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendErrorEmail/msg/{$msg}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }


}
