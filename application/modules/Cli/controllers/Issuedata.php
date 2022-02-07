<?php

use \Base\Log as Log;
use \ExtInterface\Ldap\ApiModel as ExtLdapApi;
use \Mysql\Dm\DomainModel as MysqlDomain;
use \Mysql\Issue\NodeModel as MysqlIssueNode;
use \Mysql\Issue\UserModel as MysqlIssueUser;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\MobileModel as MysqlIssueMobile;
use \Mysql\Issue\ExecutorModel as MysqlIssueExecutor;
use \Mysql\Issue\ProcessModel as MysqlIssueProcess;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\ExeRotateModel as MysqlIssueExeRotate;
use \Mysql\Issue\UserModel as MysalIssueUserInfo;
use \Mysql\Issue\VsanInfoModel as MysalIssueVsan;
use \Business\Issue\ServerModel as BusIssueServer;

use \Mysql\Issue\MobileModel as MysalIssueMobile;
use \ExtInterface\Msg\ApiModel as ExtMsgApi;


/**
 * Issue事务跟踪系统 工具类
 */
class IssuedataController extends \Base\Controller_AbstractCli
{

    /**
     * 从RXT系统获取用户手机号
     */
    public function getRtxUserMobileAction()
    {
        $ch = curl_init("http://172.30.200.39:8012/getAllUserInfo.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);

        $userInfo = json_decode($output, true);
        MysqlIssueMobile::getInstance()->exeSql('Truncate table `IssueUserMobile`');
        $allUser = array();
        foreach ($userInfo as $user) {
            if (!empty($user['email'])) {
                $infoArr = explode('@', $user['email']);
                $mobileArr = explode(',', $user['mobile']);
                $cname = $user['cn'];
                foreach ($mobileArr as $mobile) {
                    $data = array();
                    $data['user'] = $infoArr[0];
                    $data['mobile'] = $mobile;
                    $data['cname'] = $cname;
                    $data['status'] = 1;
                    $allUser[] = $data;
                }
            }
        }
        MysqlIssueMobile::getInstance()->addMultMobile($allUser);
    }

    /**
     * 从RXT系统每天凌晨三点更新用户手机号
     */
    public function updateRtxUserMobileAction()
    {
        $ch = curl_init("http://172.30.225.55:8012/getAllUserInfo.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);

        $userInfo = json_decode($output, true);

        foreach ($userInfo as $user) {
            if (!empty($user['email'])) {
                $infoArr = explode('@', $user['email']);
                $mobileArr = explode(',', $user['mobile']);
                $cname = $user['cn'];
                $usrInfo = MysqlIssueMobile::getInstance()->getOneMobileByWhere(array('user' => $infoArr[0]));
                if (empty($usrInfo)) {
                    $allUser = array();
                    foreach ($mobileArr as $mobile) {
                        $data = array();
                        $data['user'] = $infoArr[0];
                        $data['mobile'] = $mobile;
                        $data['status'] = 1;
                        $data['cname'] = $cname;
                        $allUser[] = $data;
                    }
                    $res = MysqlIssueMobile::getInstance()->addMultMobile($allUser);
                    if ($res) {
                        $msg = "导入新用户手机号:" . json_encode($allUser);
                        Log::getInstance('rtx')->write($msg);
                    }
                }
            }
        }

        $msg = "今天同步RTX系统手机号任务结束!";
        Log::getInstance('rtx')->write($msg);
    }


    /**
     * 从txt文件中整理并导出ifeng.com域名的所有dns信息
     */
    public function getIfengComDataAction()
    {
        $fileName = APPLICATION_PATH . '/data/ifengcom/ifeng.com.txt';

        $file = fopen($fileName, "r");
        $domainArr = array();

        $i = 0;
        while (!feof($file)) {
            $singleArr = explode('	', fgets($file));
            if (count($singleArr) == 4) {
                $domainArr[] = $singleArr;
            }
            $i++;
        }
        fclose($file);

        $dataList = array();
        foreach ($domainArr as $domain) {
            $data = array();
            $data['domain'] = $domain[0];
            $data['ttl'] = $domain[1];
            $data['type'] = $domain[2];
            $data['ip'] = $domain[3];
            $data['c_time'] = date('Y-m-d H:i:s');
            $dataList[] = $data;
        }
        MysqlDomain::getInstance()->execTableSql('Truncate table `IssueIfengComDomain`');
        $addRes = MysqlDomain::getInstance()->addMultDomian($dataList);

        var_dump($addRes);
        die;
    }

    /**
     * 常用  删除错误提案
     */
    public function delErrorIssueInfoAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id'])) {

            try {
                MysqlIssueServer::getInstance()->beginTransaction();

                $serverInfo = MysqlIssueServer::getInstance()->getOneServer(array('info_id'), array('id' => $param['id']));

                if (empty($serverInfo)) {
                    throw new \PDOException("删除错误提案信息时出错,提案ID不存在");
                }
                $id = $param['id'];
                $infoId = $serverInfo['info_id'];

                $delServerRes = MysqlIssueServer::getInstance()->deleteServer(array('id' => $id));
                if (!$delServerRes) {
                    throw new \PDOException("删除错误提案信息时出错,表IssueServer");
                }

                $delServerInfoRes = MysqlIssueServerInfo::getInstance()->deleteServerInfo(array('id' => $infoId));
                if (!$delServerInfoRes) {
                    throw new \PDOException("删除错误提案信息时出错,表IssueServerInfo");
                }

                $delNodeInfoRes = MysqlIssueNode::getInstance()->deleteNode(array('server_id' => $id));
                if (!$delNodeInfoRes) {
                    throw new \PDOException("删除错误提案信息时出错,表IssueNodeInfo");
                }

                $redisInfo = \Redis\Db0\Issue\ServerModel::getInstance()->getAllInfo($id);
                if (!empty($redisInfo)) {
                    $delRedis = \Redis\Db0\Issue\ServerModel::getInstance()->deleteInfo($id);
                    if (!$delRedis) {
                        throw new \PDOException("删除错误提案信息时出错,Redis数据IssueServerInfo:{$id}");
                    }
                }

                MysqlIssueServer::getInstance()->commitTransaction();

                echo "删除ID:{$id}成功";

            } catch (\PDOException $e) {
                MysqlIssueServer::getInstance()->rollbackTransaction();
                echo $e->getMessage();
            }

        } else {
            echo '输入参数错误';
        }
    }

    /**
     * 更新申请凤凰令牌信息,包括默认VPN和手机号
     */
    public function updateTokenInfoAction()
    {
        $type = 'token';
        $sql = 'SELECT
                        `List`.`id`,
                        `List`.`i_status`,
                        `List`.`i_result`,
                        `List`.`u_time`,
                        `List`.`user`,
                        `Info`.`id` as infoId,
                        `Info`.`infoJson`
                    FROM
                        `IssueServer` AS `List`
                    JOIN `IssueServerInfo` AS `Info` ON (`Info`.`id` = `List`.`info_id`)
                    WHERE `List`.`i_type` = :type AND `List`.`i_status` = 4  AND `List`.`i_result` = 1
                    ORDER BY `List`.`c_time` DESC';

        $serverMysqlInfoList = MysqlIssueServer::getInstance()->querySQL($sql,
            array('type' => $type));

        foreach ($serverMysqlInfoList as $serverInfo) {

            $info = json_decode($serverInfo['infoJson'], true);

            $mobile = $info['t_mobile'];
            $user = $serverInfo['user'];
            $update = MysqlIssueMobile::getInstance()->updateMobileInfo(
                array('mobile' => $mobile, 'u_time' => date('Y-m-d H:i:s')), array('user' => $user));
            if ($update) {
                echo "{$user}手机号更新完毕\n";
            }

            if ($info['t_use'][0] == '2') {

                $info['t_use'][0] = 1;
                MysqlIssueServerInfo::getInstance()->updateServerInfo(
                    array('infoJson' => json_encode($info)), array('id' => $serverInfo['infoId']));
                \Business\Issue\ServerModel::getInstance()->MysqlToRedisServer($serverInfo['id']);
            }

        }

    }

    /**
     * 清理Redis数据
     */
    public function clearMemoryAction()
    {
        $serverList = MysqlIssueServer::getInstance()->getServerList(null, array('i_status' => 4, 'i_result' => 1));

        foreach ($serverList as $server) {
            $delRedis = \Redis\Db0\Issue\ServerModel::getInstance()->deleteInfo($server['id']);
            if (!$delRedis) {
                echo "删除Redis" . $server['id'] . "出错";
            }
            echo "删除Redis" . $server['id'] . "成功";
        }

    }

    //升级至V1版本,所需格式化脚本
    public function upgradeV1Action()
    {
        $this->__changeExecutor();
        echo '修改执行组Json格式格式,结束!' . "\n";

        $this->__changeProcess();
        echo '修改流程顺序Json格式,结束!' . "\n";

        $this->__changeServerInfo();
        echo '修改详细信息中执行组Json,结束!' . "\n";

        $this->__addUserCname();
        echo 'serverUser表添加中文名称字段,并更新数据,结束!' . "\n";

        $this->__addNodeTableCname();
        echo 'node表添加中文名称字段,并更新数据,结束!' . "\n";

        $this->__addNodeTableCnameAgent();
        echo 'node表添加代理人字段,结束!' . "\n";

        $this->__changeRotateInfo();
        echo '修改任务轮训表中的执行组名称,结束!' . "\n";

    }

    /**
     * 修改执行组Json格式,一次性
     */
    private function __changeExecutor()
    {
        $exeList = MysqlIssueExecutor::getInstance()->getExecutorList(array('id', 'exeJson'), null);

        foreach ($exeList as $exeInfo) {
            $exeArr = json_decode($exeInfo['exeJson'], true);

            $newExeArr = array();
            foreach ($exeArr as $exe) {

                foreach ($exe as $k => $v) {
                    $newExeArr['e' . $k] = $v;
                }
            }

            MysqlIssueExecutor::getInstance()->updateExecutorInfo(array('exeJson' => json_encode($newExeArr)),
                array('id' => $exeInfo['id']));
        }

    }

    /**
     * 修改流程顺序Json格式,一次性
     */
    private function __changeProcess()
    {
        $processList = MysqlIssueProcess::getInstance()->getProcessList(array('id', 'exeJson'), null);

        foreach ($processList as $processInfo) {
            $processArr = json_decode($processInfo['exeJson'], true);

            $newProArr = array();
            foreach ($processArr as $pro) {

                $key = $pro['val'];
                unset($pro['val']);
                $newProArr['e' . $key] = $pro;
            }

            MysqlIssueProcess::getInstance()->updateProcessInfo(array('exeJson' => json_encode($newProArr)),
                array('id' => $processInfo['id']));
        }

    }

    /**
     * 修改详细信息中执行组Json,一次性
     */
    private function __changeServerInfo()
    {
        $infoList = MysqlIssueServerInfo::getInstance()->getServerList(array('id', 'exeJson'), null);

        foreach ($infoList as $info) {
            $exeArr = json_decode($info['exeJson'], true);
            if (empty($exeArr['status'])) {
                $status = 1;
            } else {
                $status = $exeArr['status'];
            }
            $newExeArr = array('status' => $status);
            foreach ($exeArr['list'] as $key => $exe) {
                if (substr($key, 0, 1) != 'e') {
                    $num = $exe['val'];
                    unset($exe['val']);
                    $newExeArr['list']['e' . $num] = $exe;
                }
            }

            MysqlIssueServerInfo::getInstance()->updateServerInfo(array('exeJson' => json_encode($newExeArr)),
                array('id' => $info['id']));
        }

    }

    /**
     * serverUser表添加中文名称字段,并更新数据,一次性
     */
    private function __addUserCname()
    {
        $addSql = "ALTER TABLE `IssueUserInfo` ";
        $addSql .= "ADD COLUMN `cname` ";
        $addSql .= "varchar(20) NOT NULL DEFAULT ''";
        $addSql .= "COMMENT '用户中文名' ";
        $addSql .= "AFTER `user` ";
        MysqlIssueUser::getInstance()->exeSql($addSql);

        $userList = MysqlIssueUser::getInstance()->getUserList(array('id', 'user'), null);

        foreach ($userList as $user) {
            $userInfo = ExtLdapApi::getInstance()->getAllUserInfo($user['user']);

            if (empty($userInfo)) {
                $cname = '';
            } else {
                $cname = $userInfo['name'];
            }

            $res = MysqlIssueUser::getInstance()->updateUserInfo(array('cname' => $cname), array('id' => $user['id']));

        }

    }

    /**
     * node表添加中文名称字段,并更新数据,一次性
     */
    private function __addNodeTableCname()
    {
        $addSql = "ALTER TABLE `IssueNodeInfo` ";
        $addSql .= "ADD COLUMN `operator_name` ";
        $addSql .= "varchar(20) NOT NULL DEFAULT ''";
        $addSql .= "COMMENT '操作人员中文名' ";
        $addSql .= "AFTER `operator` ";
        MysqlIssueNode::getInstance()->exeSql($addSql);


        $userList = MysqlIssueNode::getInstance()->getNodeList(array('id', 'operator'), null);

        foreach ($userList as $user) {
            $userInfo = ExtLdapApi::getInstance()->getAllUserInfo($user['operator']);

            if (empty($userInfo)) {
                $cname = '';
            } else {
                $cname = $userInfo['name'];
            }

            $res = MysqlIssueNode::getInstance()->updateNodeInfo(array('operator_name' => $cname), array('id' => $user['id']));

        }

    }

    /**
     * node表添加代理人字段,一次性
     */
    private function __addNodeTableCnameAgent()
    {
        $addSql = "ALTER TABLE `IssueNodeInfo` ";
        $addSql .= "ADD COLUMN `operator_agent` ";
        $addSql .= "varchar(20) NOT NULL DEFAULT ''";
        $addSql .= "COMMENT '代理操作人员' ";
        $addSql .= "AFTER `operator_name` ";
        MysqlIssueNode::getInstance()->exeSql($addSql);

    }

    /**
     * 修改任务轮训表的exe组名,+e,一次性
     */
    private function __changeRotateInfo()
    {
        $exeRotateList = MysqlIssueExeRotate::getInstance()->getRotateListByWhere(null);

        foreach ($exeRotateList as $rotate) {
            $newExe = 'e' . $rotate['exe'];
            MysqlIssueExeRotate::getInstance()->updateRotateInfo(array('exe' => $newExe),
                array('id' => $rotate['id']));
        }
    }

    /**
     * 清理Redis数据
     */
    public function addUserInfoAction()
    {
        $info = ExtLdapApi::getInstance()->getAllUserInfo('zhangying5');

        $usr = $info['uid'];
        $name = $info['name'];
        $org = $info['org'];

        $userInfo = MysalIssueUserInfo::getInstance()->getOneUserInfo(array('id'), array('user' => $usr));

        if (empty($userInfo)) {
            $data['user'] = $usr;
            $data['cname'] = $name;
            $data['department'] = $org['department'] ?? '';
            $data['center'] = $org['center'] ?? '';
            $data['group'] = $org['group'] ?? '';
            $data['leader'] = '';
            MysalIssueUserInfo::getInstance()->addNewUser($data);
        } else {
            $set['department'] = $org['department'] ?? '';
            $set['center'] = $org['center'] ?? '';
            $set['group'] = $org['group'] ?? '';
            MysalIssueUserInfo::getInstance()->updateUserInfo($set, array('user' => $usr));
        }

    }

    /**
     * 删除IT测试数据
     */
    public function delItIssueInfoAction()
    {

        $serverList = MysqlIssueServer::getInstance()->getServerList(array('id', 'info_id'),
//            array('i_type' => array('staffleaving')));
            array('i_type' => array('batchonlinevsonreport', 'batchonlineserverreport', 'batchonlinevsoncheck',
                'batchonlineservercheck', 'batchonlineidcos', 'batchonlinevsan', 'batchonlinenewwork',
                'batchonlineshelves', 'batchonlineimplement', 'batchonlineneed', 'batchonline')));

        if (!empty($serverList)) {
            foreach ($serverList as $server) {

                $id = $server['id'];
                $infoId = $server['info_id'];

                try {
                    MysqlIssueServer::getInstance()->beginTransaction();

                    $delServerRes = MysqlIssueServer::getInstance()->deleteServer(array('id' => $id));
                    if (!$delServerRes) {
                        throw new \PDOException("删除错误提案信息时出错,表IssueServer");
                    }

                    $delServerInfoRes = MysqlIssueServerInfo::getInstance()->deleteServerInfo(array('id' => $infoId));
                    if (!$delServerInfoRes) {
                        throw new \PDOException("删除错误提案信息时出错,表IssueServerInfo");
                    }

                    $delNodeInfoRes = MysqlIssueNode::getInstance()->deleteNode(array('server_id' => $id));
                    if (!$delNodeInfoRes) {
                        throw new \PDOException("删除错误提案信息时出错,表IssueNodeInfo");
                    }

                    \Redis\Db0\Issue\ServerModel::getInstance()->deleteInfo($id);

                    MysqlIssueServer::getInstance()->commitTransaction();

                } catch (\PDOException $e) {
                    MysqlIssueServer::getInstance()->rollbackTransaction();
                    echo $e->getMessage();
                }

                echo "删除ID:{$id}成功\n";
            }
        }

    }

    public function getVmInfoAction()
    {

        $file = APPLICATION_PATH . "/data/vm/vm_host.xlsx";
        $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
        $PHPExcel = $reader->load($file); // 文档名称

        $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表(编号从 0 开始)

        $row = $sheet->getHighestRow(); // 取得总行数
        $column = $sheet->getHighestColumn(); // 取得总列数

        $dataArr = array();
        for ($r = 2; $r <= $row; $r++) {
            $cArr = array();

            for ($c = 0; $c <= ord($column) - 65; $c++) {
                $cArr[] = $sheet->getCellByColumnAndRow($c, $r)->getValue();
            }

            $data = array();
            $data['vname'] = $cArr[0];
            $data['os'] = $cArr[1];
            $data['ip'] = $cArr[2];
            $data['ext_ip'] = empty($cArr[3]) ? '' : $cArr[3];
            $data['state'] = $cArr[4] == 'UP' ? 1 : 0;
            $data['uid'] = $cArr[5];
            $data['operator'] = empty($cArr[6]) ? '' : $cArr[6];
            $data['expire'] = $cArr[8] == '不做到期处理' ? '0000-00-00' : date('Y-m-d', strtotime($cArr[8]));
            $data['remarks'] = empty($cArr[9]) ? '' : $cArr[9];;

            $addRes = MysalIssueVsan::getInstance()->addNewVsan($data);
            if (!$addRes) {
                var_dump($data);
            }
        }
    }

    /**
     * 执行中断提案 php cli/cli.php request_uri='/cli/issuedata/exeIssue/id/14913/user/pms'
     */
    public function exeIssueAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id']) && isset($param['user'])) {

            $remarks = '所有子提案已完成!';

            BusIssueServer::getInstance()->exeServer($param['id'], $remarks, '', 'api', $param['user'], '', '');

        }

    }

    /**
     * 执行中断提案 php cli/cli.php request_uri='/cli/issuedata/sendRtx'
     */
    public function sendRtxAction()
    {

        $file = fopen(APPLICATION_PATH . "/docs/jumpbox_users.txt", "r");
        $userList=array();
        $i=0;
        while(! feof($file))
        {
            $userList[$i]= fgets($file);//fgets()函数从文件指针中读取一行
            $i++;
        }
        fclose($file);
        $userList=array_filter($userList);

//        $userList = array('zhangyang7');


        foreach ($userList as $user){

            $userInfo = MysalIssueMobile::getInstance()->getOneMobileByWhere(array('user' => trim($user)));

            if (!empty($userInfo['cname'])) {
                $rtx['account'] = $userInfo['cname'];
                $rtx['title'] = 'PMS系统消息通知';
                $rtx['content'] =
                    "hi all：" . "\n"
                    . "     我们计划于 明天周六 2018.08.18日 早上 6:00–8:00 对跳板机服务进行维护，在期间您无法使用跳板机登陆服务器"  . "\n"
                    . "     完成后，将通过邮件通知各位。" . "\n"
                    . "     若有紧急情况请联系 卞新栋 bianxd@ifeng.com (18840830540)"
                ;
                echo $userInfo['cname'] . '发送结果:' .  ExtMsgApi::getInstance()->sendRtx($rtx) ."\n";
            }else{
                echo $user . "无中文名称\n";
            }

            sleep(1);
        }



    }


}