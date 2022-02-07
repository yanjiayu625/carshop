<?php

use \Mysql\Issue\UserModel as MysqlUserInfo;
use \Mysql\Issue\MobileModel as MysqlUserMobile;
use \Mysql\Issue\UserModel as MysqlIssueUserInfo;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\TreeModel as MysqlIssueTree;
use \Business\Issue\ServerModel as BusIssueServer;
use \ExtInterface\Ldap\ApiModel as ExtLdapApi;
use \Mysql\Issue\ServerRelateModel as MysqlIssueRelate;
use \Mysql\Issue\CliModel  as MysqlIssueCLi;
use \Mysql\Issue\CommonModel as MysqlCommon;


class ManualController extends \Base\Controller_AbstractCli
{
    public $_infoArr = [];
    public $_php = '';

    /**
     * 手动创建取消NAT提案 php cli/cli.php request_uri='/cli/Manual/cancelNat/id/16330'
     */
    public function cancelNatAction()
    {

        $param = $this->getRequest()->getParams();

        if (empty($param['id'])) {
            echo '提案ID为空';die;
        }

        //查询主提案信息
        $Mainserver = MysqlIssueServer::getInstance()->getOneServer(array('i_type', 'user', 'info_id', 'exe_person'), array('id' => $param['id'], 'i_status' => 4, 'i_result' => 1));

        if (empty($Mainserver)) {
            echo '提案ID非法';die;
        }

        $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $Mainserver['info_id']));
        $serverInfo = json_decode($serverInfoJson['infoJson'], true);

        $endTime = $serverInfo['t_endtime'];
        $day = (strtotime(substr($endTime, 0, 10)) - strtotime(date('Y-m-d'))) / 86400;

        //已到期,并且无交接人
        if (($day < 0) && empty($serverInfo['t_transfer'])) {
            $server = array();

            $user = 'pms';
            $serverType = 'cancelnat';
            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

            $server['i_type'] = $serverType;
            $server['i_name'] = implode('>', array_values($treeInfo));

            $userInfo = MysqlIssueUserInfo::getInstance()->getOneUserInfo(
                array('cname', 'department', 'center', 'group'), array('user' => $user));
            if (empty($userInfo)) {
                $ldapInfo = ExtLdapApi::getInstance()->getAllUserInfo($user);
                $userName = $ldapInfo['name'];
                $depart = implode(' - ', array_filter(array_values($ldapInfo['org'])));
            } else {
                $userName = $userInfo['cname'];
                unset($userInfo['cname']);
                $depart = implode(' - ', array_filter(array_values($userInfo)));
            }

            $server['i_urgent'] = '1';
            $server['i_risk'] = '0';
            $server['i_countersigned'] = '';
            $server['i_applicant'] = $userName;
            $server['i_email'] = $user . '@ifeng.com';
            $server['i_department'] = $depart;
            $server['i_leader'] = '';
            $server['i_title'] = "{$param['id']}提案时间到期,取消公网访问权限";

            $server['i_related'] = $param['id'];

            if ($Mainserver['i_type'] == 'syqapplynat') {
                $server['t_iplist'] = $serverInfo['t_custom_nat_ip_syq'];
            } elseif ($Mainserver['i_type'] == 'applynat') {
                if(isset($serverInfo['t_custom_nat_ip'])) {
                    $server['t_iplist'] = $serverInfo['t_custom_nat_ip'];
                } else {
                    $server['t_iplist'] = $serverInfo['t_ip'];
                }
            }

            $res = BusIssueServer::getInstance()->createServer($server, 'api', $user, false, true);
            if (!$res['status']) {
                echo '取消公网访问权限提案,创建失败';
            }
        } else {
            echo '提案尚未到期 或 提案已有交接人';
        }

    }


    /**
     * 修改网路设备模块提案到期时间 php cli/cli.php request_uri='/cli/Manual/changeIssueTime/id/16330'
     */
    public function changeIssueTimeAction()
    {
        $param = $this->getRequest()->getParams();

        if (empty($param['id'])) {
            echo '提案ID为空';die;
        }

        //查询主提案信息
        $server = MysqlIssueServer::getInstance()->getOneServer(array('info_id'), array('id' => $param['id']));

        if (empty($server)) {
            echo '提案ID非法';die;
        }

        $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $serverInfo = json_decode($serverInfoJson['infoJson'], true);

        $serverInfo['t_endtime'] = date("Y-m-d", strtotime("3 month"));

        $updateInfo = MysqlIssueServerInfo::getInstance()->updateServerInfo(
            array('infoJson' => json_encode($serverInfo)), array('id' => $server['info_id']));
        if (!$updateInfo) {
            echo '更新提案详细内容时错误';
        }

        BusIssueServer::getInstance()->MysqlToRedisServer($param['id']);
    }


    /**
     * 直接结束当前api所在node,不重复执行
     * php cli/cli.php request_uri='/cli/Manual/finishApiNode/id/16330/type/api_ext_setNat'
     */
    public function finishApiNodeAction()
    {
        $param = $this->getRequest()->getParams();

        if (empty($param['id'])) {
            echo '提案ID为空';die;
        }
        if (empty($param['type'])) {
            echo '接口类型为空';die;
        }

        $remarks = 'API接口操作成功!';
        $exeId = '';
        $add = '';

        $informs = '';
        BusIssueServer::getInstance()->exeServer($param['id'], $remarks, $informs, 'api', $param['type'], $exeId, $add);
    }

    /**
     * 更新指定提案redis
     * php cli/cli.php request_uri='/cli/Manual/updateIssueRedis/id/26180'
     */
    public function updateIssueRedisAction()
    {
        $param = $this->getRequest()->getParams();
        BusIssueServer::getInstance()->MysqlToRedisServer($param['id']);
    }


    /**
     * 修改relate表type=relate,master<->slave(值交换)
     * php cli/cli.php request_uri='/cli/Manual/exchangeTypeValues'
     */
    public function exchangeTypeValuesAction()
    {
        $list = MysqlIssueRelate::getInstance()->getServerRelateList(['id', 'master', 'slave'],
            ['type'=>'relate', 'm_type !='=>'', 's_type !=' => '', 'c_time >'=>'2018-09-06']);

        $i = 0;
        $str = '';
        foreach ($list as $one) {
                $up['master'] = $one['slave'];
                $up['slave']  = $one['master'];

                $upRes = MysqlIssueRelate::getInstance()->update($up, ['id'=>$one['id']]);

                if (!$upRes) {
                    $str .=  'fail -- '.$one['id']."\n";
                } else {
                    $i++;
                }
        }
        echo $str;
        echo "\n\n";
        echo $i;
    }

    /**
     * 离职交接API失败时,手动创建交接提案
     * php cli/cli.php request_uri='/cli/Manual/createTransferIssue/id/23312/uid/likx1/transfer/yanhong'
     */
    public function createTransferIssueAction()
    {
        $param = $this->getRequest()->getParams();

        try {
        
            if (empty($param['id'])) {
                throw new \Exception('提案ID为空');
            } else {
                $id = $param['id'];
            }

            if (empty($param['uid'])) {
                throw new \Exception('离职人为空');
            } else {
                $uid = $param['uid'];
            }

            if (empty($param['transfer'])) {
                throw new \Exception('交接人为空');
            } else {
                $transfer = $param['transfer'];
            }
            if (!ExtLdapApi::getInstance()->checkExist($transfer)) {
                throw new \Exception("参数错误:交接人域账号:{$transfer}非法");
            }

            $server = MysqlIssueServer::getInstance()->getOneServer(
                ['id', 'i_type', 'i_title', 'info_id', 'exe_person'], ['id'=>$id, 'user'=>$uid]);
            if (empty($server)) {
                throw new \Exception("参数错误:交接提案ID:{$id}非法");
            }
            if ($server['exe_person'] == 'api') {
                throw new \Exception("参数错误:交接提案ID:{$id},已选择不续约");
            }

            //创建交接提案
            $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id'=>$server['info_id']));
            $infoArr = json_decode($serverInfo['infoJson'],true);

            if (!empty($infoArr['t_transfer'])) {
                throw new \Exception("参数错误:提案ID:{$id},已有交接人");
            }

            $user = $transfer;
            $serverType = $server['i_type'];
            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

            $newServer['i_type'] = $serverType;
            $newServer['i_name'] = implode('>', array_values($treeInfo));

            $userInfo = MysqlUserInfo::getInstance()->getOneUserInfo(
                array('cname', 'department', 'center', 'group'), array('user' => $user));
            if (empty($userInfo)) {
                $ldapInfo = ExtLdapApi::getInstance()->getAllUserInfo($user);
                $userName = $ldapInfo['name'];
                $depart = implode(' - ', array_filter(array_values($ldapInfo['org'])));
            } else {
                $userName = $userInfo['cname'];
                unset($userInfo['cname']);
                $depart = implode(' - ', array_filter(array_values($userInfo)));
            }

            $newServer['i_urgent'] = '1';
            $newServer['i_risk'] = '0';
            $newServer['i_countersigned'] = '';
            $newServer['i_applicant'] = $userName;
            $newServer['i_email'] = $user . '@ifeng.com';
            $newServer['i_department'] = $depart;
            $newServer['i_title'] = "{$server['i_title']},因人员离职公网访问权限交接";

            $newServer['i_related'] = $id;

            $mobileInfo = MysqlUserMobile::getInstance()->getOneMobileByWhere(array('user' => $user));
            $mobile = (empty($mobileInfo))?'':$mobileInfo['mobile'];

            switch ($serverType) {
                case 'applynat':
                    $newServer['t_phone'] = $mobile;
                    $newServer['t_needdesc'] = $infoArr['t_needdesc'];
                    $newServer['t_starttime'] = $infoArr['t_starttime'];
                    $newServer['t_endtime'] = date("Y-m-d", strtotime('+3 month'));

                    if(isset($infoArr['t_custom_nat_ip'])) {
                        $newServer['t_custom_nat_ip'] = $infoArr['t_custom_nat_ip'];
                    } else {
                        $newServer['t_custom_nat_ip'] = $infoArr['t_ip'];
                    }
                    break;

                case 'syqapplynat':
                    $newServer['t_phone'] = $mobile;
                    $newServer['t_needdesc'] = $infoArr['t_needdesc'];
                    $newServer['t_starttime'] = $infoArr['t_starttime'];
                    $newServer['t_endtime'] = date("Y-m-d", strtotime('+3 month'));

                    $newServer['t_custom_nat_ip_syq'] = $infoArr['t_custom_nat_ip_syq'];
                    break;

                case 'networkmapping':
                    $newServer['t_mobile'] = $mobile;
                    $newServer['t_description'] = $infoArr['t_description'];
                    $newServer['t_use_startTime'] = $infoArr['t_use_startTime'];
                    $newServer['t_use_endTime'] = date("Y-m-d", strtotime('+3 month'));

                    $newServer['t_int_ip'] = $infoArr['t_int_ip'];
                    $newServer['t_int_port'] = $infoArr['t_int_port'];
                    $newServer['t_ext_port'] = $infoArr['t_ext_port'];
                    break;

                case 'applynetworkaccess':
                    $newServer['t_mobile'] = $mobile;
                    $newServer['t_type_name'] = $infoArr['t_type_name'];
                    $newServer['t_desc'] = $infoArr['t_desc'];
                    $newServer['t_starttime'] = $infoArr['t_starttime'];
                    $newServer['t_endtime'] = date("Y-m-d", strtotime('+3 month'));

                    $newServer['t_custom_apply_network_access'] = $infoArr['t_custom_apply_network_access'];
                    break;

                default:
                    break;
            }

            $newServer['t_comment'] = empty($infoArr['t_comment'])
                ? "{$uid}离职,提案{$id}访问权限移交" : $infoArr['t_comment']."<br>{$uid}离职,提案{$id}访问权限移交";

            $creatRes = BusIssueServer::getInstance()->createFinish($newServer, 'api', $user, 'pms');
            if (!$creatRes['status']) {
                throw new \Exception('移交公网访问权限提案,创建失败');
            } else {
                $infoArr['t_transfer'] = $user;
                MysqlIssueServerInfo::getInstance()->updateServerInfo(['infoJson' => json_encode($infoArr)], ['id' => $server['info_id']]);

                BusIssueServer::getInstance()->MysqlToRedisServer($id);
            }

            echo '交接完成';

        } catch (\Exception $e) {

            echo $e->getMessage();
        }
    }

    /**
     * 录入可用性模块,系统可用性指标信息 php cli/cli.php request_uri='/cli/Manual/initAvaNorm'
     */
    public function initAvaNormAction()
    {
        $norms = [
            0 => ['master'=>'AI技术中心', 'user'=>'马迪'],
            1 => ['master'=>'平台研发中心', 'user'=>'严伟锋'],
            2 => ['master'=>'数据中心', 'user'=>'潘峰'],
            3 => ['master'=>'移动技术中心', 'user'=>'李洪丹'],
            4 => ['master'=>'应用开发中心', 'user'=>'胡中道'],
            5 => ['master'=>'运维中心', 'user'=>'荣乾'],
        ];

        $fileDir = APPLICATION_PATH . '/data/excel/技术部各中心可用性承诺-20190325.xlsx';

        $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
        $PHPExcel = $reader->load($fileDir); // 文档名称

        for ($i=0; $i<6; $i++) {
            $i = 5;

            $sheet = $PHPExcel->getSheet($i); // 读取第一个工作表(编号从 0 开始)

            $row = $sheet->getHighestRow(); // 取得总行数
            $column = $sheet->getHighestColumn(); // 取得总列数

            $dataList = $newList = [];

            if (in_array($i, [0,1,2,3])) {
                for ($r = 3; $r <= $row; $r++) {
                    for ($c = 0; $c <= ord($column) - 65; $c++) {
                        $dataList[$r][$c] = trim($sheet->getCellByColumnAndRow($c, $r)->getValue());
                    }
                }

                foreach ($dataList as $k => $data) {
                    if (empty($data[0])) {
                        echo "sheet".$i."--".$k . "业务名称为空\n";
                        continue;
                    }

                    if(empty($data[2])) {
                        echo "sheet".$i."--".$k . "MTTR为空\n";
                        continue;
                    }

                    $newList[$k]['master_user'] = $norms[$i]['user'];
                    $newList[$k]['master'] = $norms[$i]['master'];
                    $newList[$k]['slave'] = $data[0];
                    $newList[$k]['grade'] = $data[1];
                    $newList[$k]['mttr'] = $data[2];
                    $newList[$k]['bug'] = $data[3];
                    $newList[$k]['slave_user'] = $data[4];
                    $newList[$k]['penalize'] = '重要程度x基数(300元)';
                    $newList[$k]['feedback'] = $data[6];

                    if (empty($data[1])) {
                        $newList[$k]['status'] = 1;
                    } else {
                        $newList[$k]['status'] = 0;
                    }
                }
                echo count($newList)."\n";
                $addRes = MysqlCommon::getInstance()->addMultData('AvaNormNow', $newList);
                if (!$addRes) {
                    echo "sheet".$i." 批量添加失败\n";
                } else {
                    echo "sheet".$i." OK\n";
                }

            } elseif ($i == 4) {
//                echo "应用开发中心 特殊处理\n";die;
                for ($r = 3; $r <= $row; $r++) {
                    for ($c = 0; $c <= ord($column) - 65; $c++) {
                        $dataList[$r][$c] = trim($sheet->getCellByColumnAndRow($c, $r)->getValue());
                    }
                }

                foreach ($dataList as $k => $data) {
                    if (empty($data[1])) {
                        echo "sheet".$i."--".$k . "业务名称为空\n";
                        continue;
                    }

                    if(empty($data[3])) {
                        echo "sheet".$i."--".$k . "MTTR为空\n";
                        continue;
                    }

                    $newList[$k]['master_user'] = $norms[$i]['user'];
                    $newList[$k]['master'] = $norms[$i]['master'];
                    $newList[$k]['slave'] = $data[1];
                    $newList[$k]['grade'] = $data[2];
                    $newList[$k]['mttr'] = $data[3];
                    $newList[$k]['bug'] = $data[4];
                    $newList[$k]['slave_user'] = $data[5];
                    $newList[$k]['penalize'] = '重要程度x基数(300元)';
                    $newList[$k]['feedback'] = $data[7];

                    if (empty($data[2])) {
                        $newList[$k]['status'] = 1;
                    } else {
                        $newList[$k]['status'] = 0;
                    }
                }
                echo count($newList)."\n";
                $addRes = MysqlCommon::getInstance()->addMultData('AvaNormNow', $newList);
                if (!$addRes) {
                    echo "sheet".$i." 批量添加失败\n";
                } else {
                    echo "sheet".$i." OK\n";
                }

            } elseif ($i == 5) {
//                echo "运维中心 特殊处理\n";die;
                for ($r = 3; $r <= $row; $r++) {
                    for ($c = 0; $c <= ord($column) - 65; $c++) {
                        $dataList[$r][$c] = trim($sheet->getCellByColumnAndRow($c, $r)->getValue());
                    }
                }

                foreach ($dataList as $k => $data) {
                    if (empty($data[0])) {
                        echo "sheet".$i."--".$k . "业务名称为空\n";
                        continue;
                    }

                    if(empty($data[2])) {
                        echo "sheet".$i."--".$k . "MTTR为空\n";
                        continue;
                    }

                    $newList[$k]['master_user'] = $norms[$i]['user'];
                    $newList[$k]['master'] = $norms[$i]['master'];
                    $newList[$k]['slave'] = $data[0];
                    $newList[$k]['grade'] = $data[1];
                    $newList[$k]['mttr'] = $data[2];
                    $newList[$k]['bug'] = '';
                    $newList[$k]['slave_user'] = $data[3];
                    $newList[$k]['penalize'] = '重要程度x基数(300元)';
                    $newList[$k]['feedback'] = $data[5];

                    if (empty($data[1])) {
                        $newList[$k]['status'] = 1;
                    } else {
                        $newList[$k]['status'] = 0;
                    }

                    var_dump($newList);die;
                }
                echo count($newList)."\n";
                $addRes = MysqlCommon::getInstance()->addMultData('AvaNormNow', $newList);
                if (!$addRes) {
                    echo "sheet".$i." 批量添加失败\n";
                } else {
                    echo "sheet".$i." OK\n";
                }
            } else {
                echo "sheet".$i." 未匹配到处理流程\n";
            }
        }
    }

    private function __exeCli($cli,$pArr){

        $paramArr = array();
        foreach ($pArr as $key => $val){
            $paramArr[] = "{$key}/{$val}";
        }
        $paramStr = implode('/', $paramArr);

        $addRes = MysqlIssueCLi::getInstance()->addNewCli(array('cli_name'=>$cli,'param'=>$paramStr));
        if($addRes){
            $cmd = "/usr/bin/nohup /usr/local/Cellar/php@7.0/7.0.22_14/bin/php ".APPLICATION_PATH."/cli/cli.php request_uri='/cli/{$cli}{$paramStr}/cid/{$addRes}' &";
            $r = popen( $cmd, 'r');
            pclose($r);
        }

    }

    /*
     * 可用性指标-业务负责人信息    php cli/cli.php request_uri='/cli/Manual/updateNormUser'
     */
    public function updateNormUserAction()
    {
        $norms = MysqlCommon::getInstance()->getList('AvaNormNow', ['id', 'slave_user'], null);
        foreach ($norms as $norm) {
            if (strpos($norm['slave_user'], ',')) {
                $usesrs = explode(',', $norm['slave_user']);
            } elseif(strpos($norm['slave_user'], '，')) {
                $usesrs = explode('，', $norm['slave_user']);
            } else {
                $usesrs = explode('、', $norm['slave_user']);
            }
            foreach ($usesrs as $user) {
                $userInfo = MysqlCommon::getInstance()->getOneInfo('IssueUserInfo', ['user'], ['cname'=>$user]);
                if (empty($userInfo)) {
                    echo "未匹配到user: ". $norm['id']."\n";
                    $userInfo['user'] = "";
                }
                $userArr[] = ['user'=>$userInfo['user'], 'name'=>$user];
            }
            $userJson = json_encode($userArr, JSON_UNESCAPED_UNICODE);
//            var_dump($userJson);
            $up = MysqlCommon::getInstance()->updateInfo('AvaNormNow', ['slave_user'=>$userJson], ['id'=>$norm['id']]);
            unset($userArr, $userJson);
        }
    }

    /*
     * php cli/cli.php request_uri='/cli/Manual/test'
     */
    public function testAction()
    {
        $ldapInfo = ExtLdapApi::getInstance()->getAllUserInfo('lilf1(disabled)');
        var_dump($ldapInfo);die;
    }

    /* 
     * 延长vsan到期时间   php cli/cli.php request_uri='/cli/Manual/delayVsanExpire'
     */
    public function delayVsanExpireAction()
    {

        $whereExpireS = "AND `VsanInfo`.`expire` >= '2019-04-21' ";
        $whereExpireE = "AND `VsanInfo`.`expire` <= '2019-05-21' ";
        $whereDelay = "AND `VsanInfo`.`delay` = '0' ";

        $sql = "SELECT
                    `VsanInfo`.`id`,
                    `VsanInfo`.`expire`,
                    `VsanInfo`.`delay`
                FROM
                    `VsanInfo` 
                WHERE 1=1 {$whereExpireS} {$whereExpireE} {$whereDelay}";

        $dataArr = MysqlCommon::getInstance()->querySQL($sql, array());

        foreach ($dataArr as $data) {
            $old = strtotime($data['expire']);
            $new = date('Y-m-d', $old + 3600*24*30);

            $upRes = MysqlCommon::getInstance()->updateInfo('VsanInfo', ['expire'=>$new] ,['id'=>$data['id']]);
            echo $data['id'].'--'.$upRes."\n";
        }
    }

    /*
     * 手动创建ifengidc.com提案   php cli/cli.php request_uri='/cli/Manual/createIfengidcIssue'
     */
    public function createIfengidcIssueAction()
    {
        // .ifengIdc.com域名申请提案
        $fileDir = '/Users/likexin/Desktop/批量添加域名.xlsx';

        $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
        $PHPExcel = $reader->load($fileDir); // 文档名称

        $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表(编号从 0 开始)

        $row = $sheet->getHighestRow(); // 取得总行数
        $column = $sheet->getHighestColumn(); // 取得总列数

        $dataList = $domainList = [];

        for ($r = 2; $r <= $row; $r++) {
            for ($c = 0; $c <= ord($column) - 65; $c++) {
                $dataList[$r][$c] = trim($sheet->getCellByColumnAndRow($c, $r)->getValue());
            }
        }

        foreach ($dataList as $k => $data) {
            $ops = strpos($data[0], '.ifengidc.com');
            $domain['isp'] = "默认";
            $domain['hostname'] = substr($data[0], 0 , $ops);
            $domain['secondLevelDomain'] = '.ifengidc.com';
            $domain['AC'] = $data[1]."记录";
            $domain['addr'] = $data[2];

            $domainList[$k] = $domain;
        }
        $server['i_type'] = 'ifengidccom';
        $server['i_name'] = '技术部>运维中心>域名相关>申请ifengidc.com域名';
        $server['i_urgent'] = '1';
        $server['i_risk'] = '0';
        $server['i_countersigned'] = '';
        $server['i_applicant'] = '贾文浩';
        $server['i_email'] = 'jiawh@ifeng.com';
        $server['i_department'] = '技术部 - 运维中心 - SRE组';
        $server['i_leader'] = '';
        $server['i_title'] = "申请ifengidc.com域名解析(394)";
        $server['i_related'] = '';


        $server['t_custom_ifengidc_domain'] = json_encode($domainList, JSON_UNESCAPED_UNICODE);
        $server['t_end_time'] = '';
        $server['t_detail'] = '将ifenghadoop，ifenghbase，kernelhbase，cm-test总共394台机器加入DNS';
//        var_dump($server);die;

        $res = BusIssueServer::getInstance()->createServer($server, 'api', 'jiawh', false, true);
        if (!$res['status']) {
            echo "创建失败\n";
        } else {
            echo "ok\n";
        }

    }
    

}

