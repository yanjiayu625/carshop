<?php

use \Base\Log as Log;
use \Mysql\Issue\UserModel as MysqlIssueUserInfo;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\YumModel as MysqlIssueYum;
use \Mysql\Issue\TreeModel as MysqlIssueTree;
use \Mysql\Issue\RenewModel as MysqlIssueRenew;
use \Business\Issue\ServerModel as BusIssueServer;
use \ExtInterface\Ldap\ApiModel as ExtLdapApi;
use \ExtInterface\Sw\ApiModel as ExtSwApi;
use \Mysql\Issue\VsanInfoModel as MysqlIssueVsanInfo;
use \Mysql\Issue\VsanLogModel as MysqlIssueVsanLog;
use \Mysql\Issue\CustomInfoModel   as MysqlIssueCustomInfo;
use \Mysql\Issue\ServerRelateModel as MysqlIssueRelate;

/**
 * 进程处理模块
 */
class DailyController extends \Base\Controller_AbstractCli
{
    /**
     * 根据result和status判断提案进行 php cli/cli.php request_uri='/cli/Daily/createIssue'
     */
    public function createIssueAction()
    {
        $today = time();

        //每周一无线巡检,一周一次
        if (date('l', $today) == "Monday") {

            $wireInfo = MysqlIssueServer::getInstance()->getOneServer(array('c_time'),
                array('i_type' => 'wirelessnetwork'), null, 'id desc');

            if (empty($wireInfo)) {
                $this->__wirelessNetwork();
            } else {
                $day = (strtotime(date('Y-m-d')) - strtotime(substr($wireInfo['c_time'], 0, 10))) / 86400;
                if ($day == 7) {
                    $this->__wirelessNetwork();
                }
            }

            $intInfo = MysqlIssueServer::getInstance()->getOneServer(array('c_time'),
                array('i_type' => 'intnetwork'), null, 'id desc');

            if (empty($intInfo)) {
                $this->__intNetwork();
            } else {
                $day = (strtotime(date('Y-m-d')) - strtotime(substr($intInfo['c_time'], 0, 10))) / 86400;
                if ($day == 14) {
                    $this->__intNetwork();
                }
            }
        }

        //每周一,三,五 核心设备巡检
        if (in_array(date('l', $today), array("Monday"))) {
            $this->__coreEquipment();
        }

        //每月20,堡垒机日常巡检
        if (date('d', $today) == '20') {
            $this->__bastionMachine();
        }

        //每周二 创建用户注册及登陆日志
        if (date('l', $today) == "Tuesday") {
            $this->__userLogin();
        }

        //每月10,数据库巡检
//        if (date('d', $today) == '10') {
//            $this->__database();
//        }
    }

    /**
     * 手动创建提案 php cli/cli.php request_uri='/cli/Daily/manualCreateIssue'
     */
    public function manualCreateIssueAction()
    {
        $this->__bastionMachine();
    }

    /**
     * 创建无线网络巡检提案
     */
    private function __userLogin()
    {
        $user = 'pms';
        $serverType = 'userloginlog';
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

        $serverInfo['i_type'] = $serverType;
        $serverInfo['i_name'] = implode('>', array_values($treeInfo));

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

        $serverInfo['i_urgent'] = '1';
        $serverInfo['i_risk'] = '0';
        $serverInfo['i_countersigned'] = '';
        $serverInfo['i_applicant'] = $userName;
        $serverInfo['i_email'] = $user . '@ifeng.com';
        $serverInfo['i_department'] = $depart;
        $serverInfo['i_leader'] = '';
        $serverInfo['i_title'] = date('Y-m-d') . ' 用户注册及登陆日志巡检';

        $serverInfo['t_save'] = '需要执行人填写';
        $serverInfo['t_savetime'] = '需要执行人填写';


        $res = BusIssueServer::getInstance()->createServer($serverInfo, 'api', $user, false, true);

        if (!$res['status']) {
            $this->__sendErrorMsg(date('Y-m-d') . ' 用户注册及登陆日志巡检,创建失败');
        }
    }

    /**
     * 创建堡垒机巡检提案
     */
    private function __bastionMachine()
    {
        $user = 'pms';
        $serverType = 'bastionmachine';
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

        $serverInfo['i_type'] = $serverType;
        $serverInfo['i_name'] = implode('>', array_values($treeInfo));

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

        $serverInfo['i_urgent'] = '1';
        $serverInfo['i_risk'] = '0';
        $serverInfo['i_countersigned'] = '';
        $serverInfo['i_applicant'] = $userName;
        $serverInfo['i_email'] = $user . '@ifeng.com';
        $serverInfo['i_department'] = $depart;
        $serverInfo['i_leader'] = '';
        $serverInfo['i_title'] = date('Y-m-d') . ' 堡垒机例行巡检';

        $serverInfo['t_log_status'] = 1;
        $serverInfo['t_log_pic'] = '若有异常,请上传日志截图';
        $serverInfo['t_report_time'] = '若有异常,请填写报告时间';
        $serverInfo['t_reason'] = '若有异常,请异常原因';
        $serverInfo['t_solve'] = 1;

        $res = BusIssueServer::getInstance()->createServer($serverInfo, 'api', $user, false, true);

        if (!$res['status']) {
            $this->__sendErrorMsg(date('Y-m-d') . ' 堡垒机例行巡检提案,创建失败');
        }
    }

    /**
     * 创建数据库巡检提案
     */
    private function __database()
    {
        $user = 'pms';
        $serverType = 'database';
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

        $serverInfo['i_type'] = $serverType;
        $serverInfo['i_name'] = implode('>', array_values($treeInfo));

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

        $serverInfo['i_urgent'] = '1';
        $serverInfo['i_risk'] = '0';
        $serverInfo['i_countersigned'] = '';
        $serverInfo['i_applicant'] = $userName;
        $serverInfo['i_email'] = $user . '@ifeng.com';
        $serverInfo['i_department'] = $depart;
        $serverInfo['i_leader'] = '';
        $serverInfo['i_title'] = date('Y-m-d') . ' 数据库例行巡检';

        $serverInfo['t_business'] = '请填写数据库所属业务';
        $serverInfo['t_backup'] = '请填写数据库备份SQL大小';
        $serverInfo['t_recover_pic'] = '请上传数据库恢复过程截图';
        $serverInfo['t_recover_status'] = 1;
        $serverInfo['t_report_time'] = '若有异常,请填写报告时间';
        $serverInfo['t_reason'] = '若有异常,请填写异常原因';
        $serverInfo['t_solve'] = 1;

        $res = BusIssueServer::getInstance()->createServer($serverInfo, 'api', $user, false, true);

        if (!$res['status']) {
            $this->__sendErrorMsg(date('Y-m-d') . ' 数据库例行巡检提案,创建失败');
        }
    }

    /**
     * 创建无线网络巡检提案
     */
    private function __wirelessNetwork()
    {
        $user = 'pms';
        $serverType = 'wirelessnetwork';
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

        $serverInfo['i_type'] = $serverType;
        $serverInfo['i_name'] = implode('>', array_values($treeInfo));

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

        $serverInfo['i_urgent'] = '1';
        $serverInfo['i_risk'] = '0';
        $serverInfo['i_countersigned'] = '';
        $serverInfo['i_applicant'] = $userName;
        $serverInfo['i_email'] = $user . '@ifeng.com';
        $serverInfo['i_department'] = $depart;
        $serverInfo['i_leader'] = '';
        $serverInfo['i_title'] = date('Y-m-d') . ' 无线网络例行巡检';

        $serverInfo['t_ap_fault'] = '必填';
        $serverInfo['t_ac_health'] = 1;
        $serverInfo['t_ac_health_status'] = '若正常,则为空';
        $serverInfo['t_imc'] = 1;
        $serverInfo['t_imc_status'] = '若正常,则为空';
        $serverInfo['t_imc_sync'] = 1;
        $serverInfo['t_protal'] = 1;
        $serverInfo['t_ac_time'] = '必填';

        $res = BusIssueServer::getInstance()->createServer($serverInfo, 'api', $user, false, true);

        if (!$res['status']) {
            $this->__sendErrorMsg(date('Y-m-d') . ' 无线网络例行巡检提案,创建失败');
        }
    }

    /**
     * 创建内网网络巡检提案
     */
    private function __intNetwork()
    {
        $user = 'pms';
        $serverType = 'intnetwork';
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

        $serverInfo['i_type'] = $serverType;
        $serverInfo['i_name'] = implode('>', array_values($treeInfo));

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

        $serverInfo['i_urgent'] = '1';
        $serverInfo['i_risk'] = '0';
        $serverInfo['i_countersigned'] = '';
        $serverInfo['i_applicant'] = $userName;
        $serverInfo['i_email'] = $user . '@ifeng.com';
        $serverInfo['i_department'] = $depart;
        $serverInfo['i_leader'] = '';
        $serverInfo['i_title'] = date('Y-m-d') . ' 内网网络例行巡检';

        $serverInfo['t_shaft_num'] = '必填';
        $serverInfo['t_shaft_desc'] = '若有故障,则必填';
        $serverInfo['t_twolayer_num'] = '必填';
        $serverInfo['t_twolayer_desc'] = '若有故障,则必填';
        $serverInfo['t_ninelayer_num'] = '必填';
        $serverInfo['t_ninelayer_desc'] = '若有故障,则必填';
        $serverInfo['t_fifteenlayer_num'] = '必填';
        $serverInfo['t_fifteenlayer_desc'] = '若有故障,则必填';

        $res = BusIssueServer::getInstance()->createServer($serverInfo, 'api', $user, false, true);

        if (!$res['status']) {
            $this->__sendErrorMsg(date('Y-m-d') . ' 内网网络例行巡检提案,创建失败');
        }
    }

    /**
     * 创建核心设备巡检提案
     */
    private function __coreEquipment()
    {
        $user = 'pms';
        $serverType = 'coreequipment';
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));

        $serverInfo['i_type'] = $serverType;
        $serverInfo['i_name'] = implode('>', array_values($treeInfo));

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

        $serverInfo['i_urgent'] = '1';
        $serverInfo['i_risk'] = '0';
        $serverInfo['i_countersigned'] = '';
        $serverInfo['i_applicant'] = $userName;
        $serverInfo['i_email'] = $user . '@ifeng.com';
        $serverInfo['i_department'] = $depart;
        $serverInfo['i_leader'] = '';
        $serverInfo['i_title'] = date('Y-m-d') . ' 核心设备巡检';

        $html = "<table class=\"item_table_area\" width=\"100%\">" .
            "<thead><tr><th style=\"width: 50%;text-align: left;background: #ddd; padding-left: 20px;line-height: 2.0\">主机名</th>" .
            "<th style=\"width: 50%;text-align: left;background: #ddd; padding-left: 20px;line-height: 2.0\">IP</th>	</tr></thead>" .
            "<tbody><tr><td class=\"table_items\">PEK1-15F-COR-6608RT-M&nbsp;</td><td class=\"table_items\">172.30.254.246</td></tr>" .
            "<tr><td class=\"table_items\">PEK1-15F-COR-6608FW-M</td><td class=\"table_items\">172.30.254.244</td></tr>" .
            "<tr><td class=\"table_items\">PEK1-COR-10508SW-IRF </td><td class=\"table_items\">172.30.254.254</td></tr>" .
            "<tr><td class=\"table_items\">JN-CORE.ifeng-inc.com&nbsp; </td><td class=\"table_items\">172.16.0.254</td></tr>" .
            "<tr><td class=\"table_items\">YZ-cor-3750G-st.ifeng.com&nbsp;</td><td class=\"table_items\">10.0.21.254</td></tr>" .
            "<tr><td class=\"table_items\">sjs-R2289-NP-Cor-sw</td><td class=\"table_items\">10.50.0.254</td></tr>" .
            "<tr><td class=\"table_items\">3yq-np-cor-qfx-sw0</td><td class=\"table_items\">10.90.0.254</td></tr>" .
            "<tr><td class=\"table_items\">TJ-1F1-I01-SP-6820-44U</td><td class=\"table_items\">10.81.254.245</td></tr>" .
            "<tr><td class=\"table_items\">TJ-1F1-J01-SP-6820-44U</td><td class=\"table_items\">10.81.254.246</td></tr>" .
            "<tr><td class=\"table_items\">TJ-1F1-K01-SP-6800-51U</td><td class=\"table_items\">10.81.254.249</td></tr>" .
            "<tr><td class=\"table_items\">TJ-1F1-L01-SP-6800-51U</td><td class=\"table_items\">10.81.254.250</td></tr></tbody></table>";

        $serverInfo['t_device'] = $html;
        $serverInfo['t_device_error'] = 1;
        $serverInfo['t_os_error'] = 1;
        $serverInfo['t_time'] = 1;
        $serverInfo['t_oslog'] = 1;
        $serverInfo['t_config'] = 1;

        $res = BusIssueServer::getInstance()->createServer($serverInfo, 'api', $user, false, true);

        if (!$res['status']) {
            $this->__sendErrorMsg(date('Y-m-d') . ' 核心设备巡检提案,创建失败');
        }
    }

    /**
     * 获取Yum列表中的软件名称和版本
     */
    public function getYumListAction()
    {

        exec("/usr/bin/yum list", $outcome, $status);

        if (0 === $status) {
            $start = false;
            foreach ($outcome as $info) {
                if ($info == 'Installed Packages') {
                    $start = true;
                    continue;
                }
                if ($info == 'Available Packages') {
                    continue;
                }
                $yumBak = array();
                if ($start) {
                    $yumArr = array_filter(explode(" ", preg_replace('/\s+/', ' ', $info)));

                    if (count($yumArr) == 3) {
                        $soft = str_replace('.i686', '', str_replace('.x86_64', '', $yumArr[0]));

                        if (strstr($yumArr[1], '-')) {
                            $versionArr = explode('-', $yumArr[1]);
                            $version = $versionArr[0];
                        } else {
                            $version = $yumArr[1];
                        }

                        $yumInfo = MysqlIssueYum::getInstance()->getOneYum(array('id'),
                            array('name' => $soft, 'version' => $version));
                        if (empty($yumInfo)) {
                            $addRes = MysqlIssueYum::getInstance()->addNewYum(array('name' => $soft, 'version' => $version));
                            if (!$addRes) {
                                var_dump($yumArr, $info);
                                die;
                            }
                        }
                    } else {
                        $yumBak = array_merge($yumBak, $yumArr);
                        if (count($yumBak) == 3) {

                            $soft = str_replace('.i686', '', str_replace('.x86_64', '', $yumBak[0]));

                            if (strstr($yumArr[1], '-')) {
                                $versionArr = explode('-', $yumArr[1]);
                                $version = $versionArr[0];
                            } else {
                                $version = $yumArr[1];
                            }

                            $yumInfo = MysqlIssueYum::getInstance()->getOneYum(array('id'),
                                array('name' => $soft, 'version' => $version));
                            if (empty($yumInfo)) {
                                $addRes = MysqlIssueYum::getInstance()->addNewYum(array('name' => $soft, 'version' => $version));
                                if (!$addRes) {
                                    var_dump($yumBak, $info);
                                    die;
                                }
                            }
                            $yumBak = array();
                        }
                    }
                }
            }
        }
    }


    /**
     * 提案每日检查 php cli/cli.php request_uri='/cli/Daily/dailyCheck'
     */
    public function dailyCheckAction()
    {
        //更新Vsan信息
        $this->__updateVmInfo();

        //校验Vsan
        $this->__checkVsanUid();

        //校验Vsan
        $this->__checkVsanInfo();

        //校验Nat
        $this->__checkNat();
        //校验syqNat
        $this->__checkSyqNat();

        //校验网络访问权限
        $this->__checkNetworkAccess();
    }

    /**
     * 检测Vsan使用者是否离职
     */
    private function __checkVsanUid()
    {
        $vsanList = MysqlIssueVsanInfo::getInstance()->getVsanList(array('uid'), array('status' => array(0, 1)));
        $uidArr = [];
        foreach ($vsanList as $vasnInfo){
            if(!in_array($vasnInfo['uid'], $uidArr)){
                $uidArr[] = $vasnInfo['uid'];
            }
        }

        $uidA = [];
        foreach ($uidArr as $uid){
            $exist = ExtLdapApi::getInstance()->checkExist($uid);
            if(!$exist){
                $uidA[] = $uid;
            }
            sleep(2);
        }
        if(!empty($uidA)){
            $this->__sendCheckVsanUidMsg(implode('#',$uidA));
        }

    }
    /**
     * 网络访问权限到期续约/离职转移
     */
    private function __checkNetworkAccess()
    {
        //如果已经不续约,最后更新人为api
        $applyList = MysqlIssueServer::getInstance()->getServerList(array('id', 'user', 'info_id', 'exe_person'),
            array('i_type' => 'applynetworkaccess', 'i_status' => 4, 'i_result' => 1));
        
        foreach ($applyList as $applyInfo) {
            $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $applyInfo['info_id']));
            $serverInfo = json_decode($serverInfoJson['infoJson'], true);

            $endTime = $serverInfo['t_endtime'];

            if ($serverInfo['t_endtime'] == '永久') {
                continue;
            }

            $day = (strtotime(substr($endTime, 0, 10)) - strtotime(date('Y-m-d'))) / 86400;

            if ($applyInfo['exe_person'] != 'api') {
                if ($day <= 15 && $day >= 0) {
                    if (ExtLdapApi::getInstance()->checkExist($applyInfo['user'])) {
                        $renewInfo = MysqlIssueRenew::getInstance()->getOneRenewInfo(array('id'),
                            array('server_id' => $applyInfo['id'], 'status' => 0));
                        if (empty($renewInfo)) {
                            $renew['server_id'] = $applyInfo['id'];
                            $renew['status'] = 0;
                            MysqlIssueRenew::getInstance()->addNewRenew($renew);
                        }
                        $this->__sendMailMsg($applyInfo['id'], $applyInfo['user'], 'renew');

                    } else {
                        //人已离职
                        if (empty($serverInfo['t_transfer'])) {
                            //检查是否已经创建交接提案
                            $transferServer = MysqlIssueRelate::getInstance()->getOneServerRelate(['slave'],
                                ['master'=>$applyInfo['id'], 's_type'=>'applytransfer']);
                            if (empty($transferServer)) {
                                $this->__sendTransferIssue($applyInfo['id'], 'network');
                            }
                        }
                    }
                }
            }

            if ($day == -1) {

                if (!empty($serverInfo['t_transfer'])) {
                    continue;
                }

                $server = array();

                $user = 'pms';
                $serverType = 'cancelnetworkaccess';
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

                if ($applyInfo['exe_person'] == 'api') {
                    $server['i_title'] = "【{$applyInfo['id']} 申请人已选不续约】提案时间到期,取消网络访问权限";
                } else {
                    $server['i_title'] = "{$applyInfo['id']}提案时间到期,取消网络访问权限";
                }

                $server['i_urgent'] = '1';
                $server['i_risk'] = '0';
                $server['i_countersigned'] = '';
                $server['i_applicant'] = $userName;
                $server['i_email'] = $user . '@ifeng.com';
                $server['i_department'] = $depart;
                $server['i_leader'] = '';
                $server['i_related'] = $applyInfo['id'];
                $server['t_type_name'] = $serverInfo['t_type_name'];

                $custom = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                    array('type'=>'t_custom_apply_network_access','sid'=>$applyInfo['id']));
                $server['t_custom_apply_network_access'] = $custom['info'];

                $res = BusIssueServer::getInstance()->createServer($server, 'api', $user, false, true);
                if (!$res['status']) {
                    $this->__sendErrorMsg(date('Y-m-d') . ' 取消网络访问权限提案,创建失败');
                }
            }

            sleep(1);
        }
    }

    /**
     * 太极公网访问权限到期续约/离职转移
     */
    private function __checkNat()
    {
        //如果已经不续约,最后更新人为api
        $natList = MysqlIssueServer::getInstance()->getServerList(array('id', 'user', 'info_id', 'exe_person'),
            array('i_type' => 'applynat', 'i_status' => 4, 'i_result' => 1));

        foreach ($natList as $natInfo) {

            $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $natInfo['info_id']));
            $serverInfo = json_decode($serverInfoJson['infoJson'], true);

            $endTime = $serverInfo['t_endtime'];

            $day = (strtotime(substr($endTime, 0, 10)) - strtotime(date('Y-m-d'))) / 86400;

            //是否已经点击'不续约'
            if ($natInfo['exe_person'] != 'api') {
                if ($day <= 15 && $day >= 0) {
                    if (ExtLdapApi::getInstance()->checkExist($natInfo['user'])) {
                        $renewInfo = MysqlIssueRenew::getInstance()->getOneRenewInfo(array('id'),
                            array('server_id' => $natInfo['id'], 'status' => 0));
                        if (empty($renewInfo)) {
                            $this->__sendMailMsg($natInfo['id'], $natInfo['user'], 'renew');
                            $renew['server_id'] = $natInfo['id'];
                            $renew['status'] = 0;
                            MysqlIssueRenew::getInstance()->addNewRenew($renew);
                        } else {
                            $this->__sendMailMsg($natInfo['id'], $natInfo['user'], 'renew');
                        }
                    } else {
                        //人已离职
                        if (empty($serverInfo['t_transfer'])) {
                            //检查是否已经创建交接提案
                            $transferServer = MysqlIssueRelate::getInstance()->getOneServerRelate(['slave'],
                                ['master'=>$natInfo['id'], 's_type'=>'applytransfer']);
                            if (empty($transferServer)) {
                                $this->__sendTransferIssue($natInfo['id'], 'nat');
                            }

                        }
                    }
                }
            }

            if ($day == -1) {

                if (!empty($serverInfo['t_transfer'])) {
                    continue;
                }

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

                if ($natInfo['exe_person'] == 'api') {
                    $server['i_title'] = "【{$natInfo['id']} 申请人已选不续约】提案时间到期,取消公网访问权限";
                } else {
                    $server['i_title'] = "{$natInfo['id']}提案时间到期,取消公网访问权限";
                }

                $server['i_urgent'] = '1';
                $server['i_risk'] = '0';
                $server['i_countersigned'] = '';
                $server['i_applicant'] = $userName;
                $server['i_email'] = $user . '@ifeng.com';
                $server['i_department'] = $depart;
                $server['i_leader'] = '';

                $server['i_related'] = $natInfo['id'];

                if (isset($serverInfo['t_custom_nat_ip'])) {
                    $ip = $serverInfo['t_custom_nat_ip'];
                } else {
                    $ip = $serverInfo['t_ip'];
                }

                $server['t_iplist'] = $ip;

                $res = BusIssueServer::getInstance()->createServer($server, 'api', $user, false, true);
                if (!$res['status']) {
                    $this->__sendErrorMsg(date('Y-m-d') . ' 取消公网访问权限提案,创建失败');
                }
            }

            sleep(1);
        }
    }

    /**
     * 三元桥公网访问权限到期续约/离职转移
     */
    private function __checkSyqNat()
    {
        //如果已经不续约,最后更新人为api
        $natList = MysqlIssueServer::getInstance()->getServerList(array('id', 'user', 'info_id', 'exe_person'),
            array('i_type' => 'syqapplynat', 'i_status' => 4, 'i_result' => 1));

        foreach ($natList as $natInfo) {

            $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $natInfo['info_id']));
            $serverInfo = json_decode($serverInfoJson['infoJson'], true);

            $endTime = $serverInfo['t_endtime'];

            $day = (strtotime(substr($endTime, 0, 10)) - strtotime(date('Y-m-d'))) / 86400;

            //是否已经点击'不续约'
            if ($natInfo['exe_person'] != 'api') {
                if ($day <= 15 && $day >= 0) {
                    if (ExtLdapApi::getInstance()->checkExist($natInfo['user'])) {
                        $renewInfo = MysqlIssueRenew::getInstance()->getOneRenewInfo(array('id'),
                            array('server_id' => $natInfo['id'], 'status' => 0));
                        if (empty($renewInfo)) {
                            $this->__sendMailMsg($natInfo['id'], $natInfo['user'], 'renew');
                            $renew['server_id'] = $natInfo['id'];
                            $renew['status'] = 0;
                            MysqlIssueRenew::getInstance()->addNewRenew($renew);
                        } else {
                            $this->__sendMailMsg($natInfo['id'], $natInfo['user'], 'renew');
                        }

                    } else {
                        //人已离职
                        if (empty($serverInfo['t_transfer'])) {
                            //检查是否已经创建交接提案
                            $transferServer = MysqlIssueRelate::getInstance()->getOneServerRelate(['slave'],
                                ['master'=>$natInfo['id'], 's_type'=>'applytransfer']);
                            if (empty($transferServer)) {
                                $this->__sendTransferIssue($natInfo['id'], 'syqnat');
                            }
                        }
                    }
                }
            }

            if ($day == -1) {

                if (!empty($serverInfo['t_transfer'])) {
                    continue;
                }

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

                if ($natInfo['exe_person'] == 'api') {
                    $server['i_title'] = "【{$natInfo['id']} 申请人已选不续约】提案时间到期,取消公网访问权限";
                } else {
                    $server['i_title'] = "{$natInfo['id']}提案时间到期,取消公网访问权限";
                }

                $server['i_urgent'] = '1';
                $server['i_risk'] = '0';
                $server['i_countersigned'] = '';
                $server['i_applicant'] = $userName;
                $server['i_email'] = $user . '@ifeng.com';
                $server['i_department'] = $depart;
                $server['i_leader'] = '';

                $server['i_related'] = $natInfo['id'];
                $server['t_iplist'] = $serverInfo['t_custom_nat_ip_syq'];

                $res = BusIssueServer::getInstance()->createServer($server, 'api', $user, false, true);
                if (!$res['status']) {
                    $this->__sendErrorMsg(date('Y-m-d') . ' 取消公网访问权限提案,创建失败');
                }
            }

            sleep(1);
        }
    }

    /**
     * 创建权限交接人提案
     * @param $id string
     * @param $type string
     */
    private function __sendTransferIssue($id, $type)
    {
        try {
            $typeArr = array('nat'=>'太极公网访问权限', 'syqnat'=>'三元桥公网访问权限', 'network'=>'申请网络访问权限');
    
            $server = array();
    
            $user = 'pms';
            $serverType = 'applytransfer';
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
            $server['i_title'] = "{$typeArr[$type]}申请人转移:提案{$id}申请人已离职!";
            $server['i_related'] = $id;
            $server['t_url'] = "";
    
            $res = BusIssueServer::getInstance()->createServer($server, 'api', $user, false, true);
            if (!$res['status']) {
                throw new \Exception(date('Y-m-d') . " <网络访问权限转移申请人>提案创建失败,原提案ID:{$id}");
            } else {
                //更新提案中链接,增加该提案ID,用以转移操作完成后关闭该提案
                $serverInfoMaster = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),array('id' => $res['data']));
                $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                    array('id' => $serverInfoMaster['info_id']));
                $serverInfo = json_decode($serverInfoJson['infoJson'], true);
                
                $host = \Yaf\Registry::get('config')->get('email.host');
                switch ($type) {
                    case 'nat':
                    case 'syqnat':
                        $renewHtml = "<a href='{$host}/Operate/nattransfer?id={$id}&tid={$res['data']}' target='_blank' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>点击处理</a>";
                        break;
                    case 'network':
                        $renewHtml = "<a href='{$host}/Operate/networkaccesstransfer?id={$id}&tid={$res['data']}' target='_blank' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>点击处理</a>";
                        break;
                    default:
                        $renewHtml = "";
                        break;
                }
                
                $serverInfo['t_url'] = $renewHtml;
                $updateInfo = MysqlIssueServerInfo::getInstance()->updateServerInfo(
                    array('infoJson' => json_encode($serverInfo)), array('id' => $serverInfoMaster['info_id']));
                if (!$updateInfo) {
                    throw new \Exception(date('Y-m-d') . " <网络访问权限转移申请人>提案ID:{$res['data']},更新转移操作链接时错误");
                }
            }
            
            BusIssueServer::getInstance()->MysqlToRedisServer($res['data']);
            
        } catch (\Exception $e) {
            $this->__sendErrorMsg($e->getMessage());
        }
    }


    /**
     * 公网访问权限提案统计
     */
    private $_emailTplInfo = '';
    public function countNatInfoAction()
    {
        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统管理员';
        $tos[] = array('address'=>'likx1@ifeng.com', 'name'=>'李可心');
        $tos[] = array('address'=>'wangwc1@ifeng.com', 'name'=>'王文超');
        $title = date('Y-m-d')." PMS公网访问权限提案统计";

        $this->_emailTplInfo = file_get_contents(TPL_NAT_ISSUE);
        $NoTransferHtml = "";

        $serverList = MysqlIssueServer::getInstance()->getServerList(['id', 'user', 'i_applicant', 'info_id', 'exe_person'],
            ['i_type' => 'applynat', 'i_status' => 4, 'i_result' => 1]);

        $n = 1;
        foreach ($serverList as $k => $natInfo) {
            $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'), array('id' => $natInfo['info_id']));
            $serverInfo = json_decode($serverInfo['infoJson'], true);
            $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'), array('sid' => $natInfo['id']));
            $intIpArr = json_decode($customInfo['info'], true);
            $day = (strtotime(substr($serverInfo['t_endtime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;

            $msg = '';
            //如果已经不续约,最后更新人为api
            if ($natInfo['exe_person'] != 'api') {
                //申请人已离职
                if (!ExtLdapApi::getInstance()->checkExist($natInfo['user'])) {
                    //权限未转移
                    if (empty($serverInfo['t_transfer'])) {
                        //是否创建取消提案
                        $cancelServer = MysqlIssueRelate::getInstance()->getOneServerRelate(['slave'], ['master'=>$natInfo['id'], 's_type'=>'cancelnat']);
                        if (!empty($cancelServer)) {
                            //取消提案是否完结
                            $cancelServerInfo = MysqlIssueServer::getInstance()->getOneServer(['exe_person'], ['id' => $cancelServer['slave'], 'i_status' => 4, 'i_result' => 1]);
                            if (empty($cancelServerInfo)) {
                                $msg = '离职后权限不转移,创建取消提案,但未完成';
                            }
                        } else {
                            $msg = '离职后权限不转移,但未创建取消提案';
                        }

                        if (empty($msg)) continue;
                        foreach ($intIpArr as $key => $value) {

                            $NoTransferHtml .= "<tr><td>{$n}</td>
                            <td>{$natInfo['id']}</td>
                            <td>{$natInfo['i_applicant']}</td>
                            <td>{$natInfo['user']}</td>
                            <td>{$value['ip']}</td>
                            <td>{$value['port']}</td>
                            <td>{$value['type']}</td>
                            <td>{$msg}</td></tr>";
                            $n++;
                        }
                        unset ($cancelServer, $cancelServerInfo);
                    }

                } else {
                    //未离职 权限到期 无'取消提案'
                    if ($day < -1) {
                        $cancelServer = MysqlIssueRelate::getInstance()->getOneServerRelate(['slave'], ['master'=>$natInfo['id'], 's_type'=>'cancelnat']);
                        if (!empty($cancelServer)) {
                            //取消提案是否完结
                            $cancelServerInfo = MysqlIssueServer::getInstance()->getOneServer(['exe_person'], ['id' => $cancelServer['slave'], 'i_status' => 4, 'i_result' => 1]);
                            if (empty($cancelServerInfo)) {
                                $msg = '到期创建取消提案,但未完成';
                            }
                        } else {
                            $msg = '到期未创建取消提案';
                        }

                        if (empty($msg)) continue;
                        foreach ($intIpArr as $key => $value) {

                            $NoTransferHtml .= "<tr><td>{$n}</td>
                                <td>{$natInfo['id']}</td>
                                <td>{$natInfo['i_applicant']}</td>
                                <td>{$natInfo['user']}</td>
                                <td>{$value['ip']}</td>
                                <td>{$value['port']}</td>
                                <td>{$value['type']}</td>
                                <td>{$msg}</td></tr>";
                            $n++;
                        }
                        unset ($cancelServer, $cancelServerInfo);
                    }
                }

            } else {
                //不续约 权限到期两天仍未创建取消提案
                if ($day < -1) {
                    $cancelServer = MysqlIssueRelate::getInstance()->getOneServerRelate(['slave'], ['master'=>$natInfo['id'], 's_type'=>'cancelnat']);
                    if (!empty($cancelServer)) {
                        //取消提案是否完结
                        $cancelServerInfo = MysqlIssueServer::getInstance()->getOneServer(['exe_person'], ['id' => $cancelServer['slave'], 'i_status' => 4, 'i_result' => 1]);
                        if (empty($cancelServerInfo)) {
                            $msg = '已选择不续约,到期创建取消提案,但未完成';
                        }
                    } else {
                        $msg = '已选择不续约,但到期未创建取消提案';
                    }

                    if (empty($msg)) continue;
                    foreach ($intIpArr as $key => $value) {
                        $NoTransferHtml .= "<tr><td>{$n}</td>
                                <td>{$natInfo['id']}</td>
                                <td>{$natInfo['i_applicant']}</td>
                                <td>{$natInfo['user']}</td>
                                <td>{$value['ip']}</td>
                                <td>{$value['port']}</td>
                                <td>{$value['type']}</td>
                                <td>{$msg}</td></tr>";
                        $n++;
                    }
                    unset ($cancelServer, $cancelServerInfo);
                }
            }
        }

        $this->__replaceTemplate('NoTransferNum',$n-1);
        $this->__replaceTemplate('NoTransferHtml',$NoTransferHtml);

        $mail = new \SendMail\MailModel;
        $mail->sendHtmlMail($title, $tos, $from, $from_name, $this->_emailTplInfo);

    }

    /**
     * 通用替换邮件内容
     * @param $flag string 通配符
     * @param $val string  替换值
     * @return null
     */
    private function __replaceTemplate($flag, $val)
    {
        $this->_emailTplInfo = str_replace('%{' . $flag . '}', $val, $this->_emailTplInfo);
    }


    /**
     * 检查Vsan虚拟机时间
     */
    private function __checkVsanInfo()
    {
        $vsanList = MysqlIssueVsanInfo::getInstance()->getVsanList(array('id', 'server_id', 'vname', 'ip', 'expire', 'delay',
            'uid'), array('status' => array(0, 1)));

        $msgUserArr = array();
        foreach ($vsanList as $vsanInfo) {
            //永不过期
            if ($vsanInfo['expire'] == '0000-00-00') continue;

            $day = (strtotime($vsanInfo['expire']) - strtotime(date('Y-m-d'))) / 86400;
            if ($day > 0 && $day <= 10 && $vsanInfo['delay'] == 0) {
                $msgUserArr['delay'][] = $vsanInfo['uid'];
            }

            if ($day == 0) {
                $res = ExtSwApi::getInstance()->operateVsan(array('vname' => $vsanInfo['vname'], 'type' => 'PowerOff'));
                if ($res['code'] == 400) {
                    $udpateRes = MysqlIssueVsanInfo::getInstance()->updateVsanInfo(array('result' => $res['msg']),
                        array('id' => $vsanInfo['id']));
                    if (!$udpateRes) {
                        $this->__sendErrorMsg($vsanInfo['vname']." 保存Vsan PowerOff命令执行结果信息失败");
                    }
                    $this->__sendErrorMsg("Vsan主机远程操作错误:{$vsanInfo['vname']} PowerOff失败");
                } else {
                    MysqlIssueVsanInfo::getInstance()->updateVsanInfo(array('status' => 1), array('id' => $vsanInfo['id']));
                    MysqlIssueVsanLog::getInstance()->addNewLog(array('server_id' => $vsanInfo['server_id'], 'ip' =>
                        $vsanInfo['ip'], 'vname' => $vsanInfo['vname'], 'action' => 'PowerOff'));
                }
                $msgUserArr['poweroff'][] = $vsanInfo['uid'];
            }

            if ($day == -8) {
                $res = ExtSwApi::getInstance()->operateVsan(array('vname' => $vsanInfo['vname'], 'type' => 'Destroy'));
                if ($res['code'] == 400) {
                    $udpateRes = MysqlIssueVsanInfo::getInstance()->updateVsanInfo(array('result' => $res['msg']),
                        array('id' => $vsanInfo['id']));
                    if (!$udpateRes) {
                        $this->__sendErrorMsg($vsanInfo['vname']." 保存Vsan Destroy命令执行结果信息");
                    }
                    $this->__sendErrorMsg("Vsan主机远程操作错误:{$vsanInfo['vname']} Destroy失败");
                } else {
                    MysqlIssueVsanInfo::getInstance()->updateVsanInfo(array('status' => 2),
                        array('id' => $vsanInfo['id']));
                    MysqlIssueVsanLog::getInstance()->addNewLog(array('server_id' => $vsanInfo['server_id'], 'ip' =>
                        $vsanInfo['ip'], 'vname' => $vsanInfo['vname'], 'action' => 'Destroy'));

                    // 销毁虚机的同时 删除CMDB信息、监控信息
                    $serverInfo = ExtInterface\Cmdb\NewapiModel::getInstance()->getVirtualServerInfoByIp($vsanInfo['ip']);

                    // 去除端口号
                    if (($pos = strpos($vsanInfo['ip'], ':')) !== false) {
                        $vsanInfo['ip'] = substr($vsanInfo['ip'], 0, $pos);
                    }

                    if ($serverInfo['code'] == 200 && !empty($serverInfo['data']['sn'])) {
                        // 删CMDB
                        $cmdbRes = ExtInterface\Cmdb\NewapiModel::getInstance()->delVirtualServerInfoBySn($serverInfo['data']['sn'], '到期销毁');
                        if ($cmdbRes['code'] != 200) {
                            $this->__sendErrorMsg( "Vsan:{$vsanInfo['vname']} CMDB删除失败,错误信息:{$cmdbRes['msg']}");
                        }
                        // 删monitor
                        if (empty(trim($serverInfo['data']['hostname']))) continue;
                        $serverArr[] = array('ns' => '', 'type' => 'machine', 'resourceid' => $serverInfo['data']['hostname']);
                        $monitorRes = ExtInterface\Monitor\ApiModel::getInstance()->delServer(json_encode($serverArr));
                        if ($monitorRes['httpstatus'] != 200) {
                            $this->__sendErrorMsg( "Vsan:{$vsanInfo['vname']} 监控删除失败,错误信息:{$monitorRes['msg']}");
                        }
                    }
                }

                $msgUserArr['destroy'][] = $vsanInfo['uid'];
            }
        }

        //发送邮件
        foreach ($msgUserArr as $type => $userArr) {
            if (!empty($userArr)) {
                $userArr = array_unique($userArr);
                foreach ($userArr as $user) {
                    $this->__sendVsanMsg($user, $type);
                    sleep(1);
                }
            }
        }

    }

    /**
     * 发送通知邮件
     * @param $id string
     * @param $type string
     */
    public function operateVmAction($id, $type)
    {
        $vsanInfo = MysqlIssueVsanInfo::getInstance()->getOneVsanInfo(array('vname'), array('id' => $id));

        $res = ExtSwApi::getInstance()->operateVsan(array('vname' => $vsanInfo['vname'], 'type' => $type));
        if ($res['code'] == 400) {
            $udpateRes = MysqlIssueVsanInfo::getInstance()->updateVsanInfo(array('result' => $res['msg']),
                array('id' => $id));
            if (!$udpateRes) {
                $this->__sendErrorMsg("保存Vsan命令执行结果信息");
            }
        }
    }

    /**
     * 更新VM信息,uid变化
     */
    private function __updateVmInfo()
    {
        $vmList = ExtSwApi::getInstance()->getVmList(array());

        foreach ($vmList['data'] as $vm) {
            $vmArr = explode('-', $vm['vname']);
            $ipInfo = MysqlIssueVsanInfo::getInstance()->getOneVsanInfo(array('id'), array('ip' => $vm['ip']));
            if (!empty($ipInfo)) {
                $set['vname'] = $vm['vname'];
                $set['uid'] = $vmArr[1];
                MysqlIssueVsanInfo::getInstance()->updateVsanInfo($set, array('id' => $ipInfo['id']));
            } else {
                MysqlIssueVsanInfo::getInstance()->addNewVsan(array(
                    'vname' => $vm['vname'],
                    'ip' => $vm['ip'],
                    'uid' => $vmArr[1],
                    'state' => 1,
                    'status' => 0,
                ));
            }

        }

        ExtSwApi::getInstance()->updateVmState(array());
    }


    /**
     * 根据result和status判断提案进行 php cli/cli.php request_uri='/cli/Daily/updateUserInfo'
     */
    public function updateUserInfoAction()
    {
        $userList = MysqlIssueUserInfo::getInstance()->getUserList(array('user'),null);

        foreach ($userList as $user){
            $ldapInfo = ExtLdapApi::getInstance()->getAllUserInfo($user['user']);

            MysqlIssueUserInfo::getInstance()->updateUserInfo(array('cname'=>$ldapInfo['name'],
                'department'=>$ldapInfo['org']['department'],'center'=>$ldapInfo['org']['center'],
                'group'=>$ldapInfo['org']['group']),array('user'=>$user['user']));

            sleep(3);
        }

    }
    /**
     * 发送通知邮件
     * @param $user string
     * @param $type string
     */
    private function __sendVsanMsg($user, $type)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendVsanEmail/user/{$user}/type/{$type}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 发送通知邮件
     * @param $id string
     * @param $user string
     * @param $action string
     */
    private function __sendMailMsg($id, $user, $action)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendMsgEmail/id/{$id}/user/{$user}/action/{$action}' &";
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
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendErrorEmail/msg/{$msg}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }


    /**
     * 发送通知邮件
     * @param $uid string
     * @return array
     */
    private function __sendCheckVsanUidMsg($uid)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendCheckVsanUidCheck/uid/{$uid}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }
}
