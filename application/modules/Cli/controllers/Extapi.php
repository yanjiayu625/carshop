<?php

if (ini_get('yaf.environ') === 'product') {
    error_reporting(E_ALL ^ E_NOTICE);
}

use \Tools\Tools;
use \Base\Log as Log;
use \Mysql\Issue\ReloadModel as MysqlIssueReload;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\CustomInfoModel as MysqlIssueCustom;
use \Mysql\Issue\ServerRelateModel as MysqlIssueRelate;
use \Mysql\Issue\MobileModel as MysqlIssueMobile;
use \Mysql\Issue\TimingModel as MysqlIssueTiming;
use \ExtInterface\IdcOs\ApiModel as ExtIdcOsApi;
use \ExtInterface\Jumpbox\ApiModel as ExtJumpboxApi;
use \ExtInterface\Ansible\ApiModel as ExtAnsibleApi;
use \ExtInterface\Ansible\NewapiModel as ExtAnsibleNewApi;
use \ExtInterface\Sw\ApiModel as ExtSwitchApi;
use \ExtInterface\Auto\ApiModel as ExtOobApi;
use \Business\Issue\ServerModel as BusIssueServer;
use \ExtInterface\Yun\ApiModel as ExtYun;
use \ExtInterface\Monitor\ApiModel as ExtMonitor;
use \ExtInterface\Cmdb\NewapiModel as ExtCmdbApi;
use \ExtInterface\Vsan\ApiModel as ExtVsanApi;
use \ExtInterface\Puppet\ApiModel as ExtPuppetApi;
use \ExtInterface\IdcMc\ApiModel as ExtIdcMcApi;
use \ExtInterface\MaxClouds\ApiModel as ExtMaxCloudsApi;
use \ExtInterface\Msg\NewapiModel as ExtMessageApi;
use \Mysql\Issue\NodeModel as MysqlIssueNode;
use \Mysql\Issue\VsanInfoModel as MysqlIssueVsan;
use \Mysql\Issue\CommonModel as MysqlIssueCommon;
use \ExtInterface\Adms\ApiModel as ExtAdmsApi;
use \ExtInterface\Msg\WechatoauthModel as WechatoauthApi;

/**
 * Issue事务跟踪系统 外部API接口处理模块
 */
class ExtapiController extends \Base\Controller_AbstractCli
{

    private $_php = '';

    /**
     * 执行外部接口   php cli/cli.php request_uri='/cli/Extapi/setApi/id/31030/api/api_ext_cmdb_repair_end'
     */
    public function setApiAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id']) && isset($param['api'])) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');

            switch ($param['api']) {
                case 'api_ext_no_executor':
                    $this->__noExecutor($param['id']);
                    break;
                case 'api_ext_installsys':
                    $this->__installSys($param['id']);
                    break;
                case 'api_ext_setDhcp':
                    $this->__setMacToDhcp($param['id']);
                    break;
                case 'api_ext_setPxe':
                    $this->__setOob($param['id']);
                    break;
                case 'api_ext_checkStatus':
                    $this->__checkInsitallStatus($param['id']);
                    break;
//                case 'api_ext_pushSshKey':
//                    $this->__resetPushKey($param['id']);
                    break;
                case 'api_ext_yunCdn':
                    $this->__yunCdn($param['id']);
                    break;
                case 'api_ext_token':
                    $this->__tokenKey($param['id']);
                    break;
                case 'api_ext_jumpbox':
                    $this->__jumpBox($param['id']);
                    break;
                case 'api_ext_sPermission':
                    $this->__serverPermissions($param['id']);
                    break;
                case 'api_ext_yumInstall':
                    $this->__yumInstall($param['id']);
                    break;
                case 'api_ext_pushKey':
                    $this->__pushKey($param['id']);
                    break;
                case 'api_ext_setNat':
                    $this->__setNat($param['id']);
                    break;
                case 'api_ext_checkFinish':
                    $this->__checkFinishIssue($param['id']);
                    break;

                case 'api_ext_monitor_offline':
                    $this->__monitor($param['id'], 'offline', 'api_ext_monitor_offline');
                    break;
                case 'api_ext_monitor_online':
                    $this->__monitor($param['id'], 'online', 'api_ext_monitor_online');
                    break;
                case 'api_ext_monitor_del':
                    $this->__monitor($param['id'], 'delete', 'api_ext_monitor_del');
                    break;

                case 'api_ext_cmdb_offline':
                    $this->__cmdb($param['id'], 'offline', 'api_ext_cmdb_offline');
                    break;

                case 'api_ext_cmdb_online':
                    $this->__cmdb($param['id'], 'online', 'api_ext_cmdb_online');
                    break;

                case 'api_ext_jumpbox_del':
                    $this->__delJumpbox($param['id']);
                    break;

                case 'api_ext_jumpbox_change':
                    $this->__changeJumpbox($param['id']);
                    break;

                case 'api_ext_vsan_batch_clone':
                    $this->__vsanBatchClone($param['id']);
                    break;

                case 'api_ext_cmdb_idc_change':
                    $this->__cmdbIdcChange($param['id']);
                    break;

                case 'api_ext_cmdb_virtual_server_del':
                    $this->__cmdbVirtualServerDel($param['id']);
                    break;

                case 'api_ext_cmdb_storage_device_del':
                    $this->__cmdbStorageDeviceDel($param['id']);
                    break;

                case 'api_ext_cmdb_physical_server_update':
                    $this->__cmdbPhysicalServerUpdate($param['id']);
                    break;

                case 'api_ext_cmdb_manual_info_add':
                    $this->__cmdbManualInfoAdd($param['id']);
                    break;

                case 'api_ext_cmdb_repair_start':
                    $this->__changeCmdbServerState($param['id'], 'repair_start');
                    break;
                case 'api_ext_cmdb_repair_end':
                    $this->__changeCmdbServerState($param['id'], 'repair_end');
                    break;

                case 'api_ext_cmdb_online_switch':
                    $this->__cmdbSwitchChange($param['id'], 'online', 'api_ext_cmdb_online_switch');
                    break;
                case 'api_ext_cmdb_offline_switch':
                    $this->__cmdbSwitchChange($param['id'], 'offline', 'api_ext_cmdb_offline_switch');
                    break;
                case 'api_ext_switch_offline_relationship':
                    $this->__switchRelationshipChange($param['id'], 'offline', 'api_ext_switch_offline_relationship');
                    break;

                case 'api_ext_suspend_puppet':
                    $this->__suspendPuppet($param['id']);
                    break;

                case 'api_ext_server_offline_ip_change':
                    $this->__serverOfflineIpChange($param['id']);
                    break;

                case 'api_ext_offline_ip_recovery':
                    $this->__offlineIpRecovery($param['id']);
                    break;

                case 'api_ext_vsan_auto_create':
                      $this->__vsanAutoCreate($param['id']);
                    break;

                case 'api_ext_virtual_machine_auto_change':
                    $this->__virtualMachineAutoChange($param['id']);
                    break;

                case 'api_ext_save_sys_name':
                    $this->__saveSysName($param['id']);
                    break;
                case 'api_ext_maxclouds_add_user':
                    $this->__addMaxCloudsUser($param['id']);
                    break;

                case 'api_ext_msg_wechat':
                    $this->__messageSys($param['id'], 'wechat');
                    break;
                case 'api_ext_msg_mail':
                    $this->__messageSys($param['id'], 'mail');
                    break;

                case 'api_ext_add_user_to_group':
                    $this->__changeUserGroup($param['id'], 'add', 'api_ext_add_user_to_group');
                    break;

                case 'api_ext_disable_user':
                    $this->__disableUser($param['id']);
                    break;

                case 'api_ext_firework_acl':
                    $this->__saveFireworkAcl($param['id']);
                    break;

                case 'api_ext_wechat_oauth':
                    $this->__setWechatOauth($param['id']);
                    break;

                case 'api_ext_video_stream_system_permission':
                    $this->__setVideoStreamSystemPermission($param['id']);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 提案无执行节点时使用(即,审批通过即结束的提案)    php cli/cli.php request_uri='/cli/Extapi/setApi/id/25966/api/api_ext_no_executor'
     */
    private function __noExecutor($id)
    {
        $remarks = "该提案无执行节点,审批通过即完成.";
        $exeId = '';
        $add = '';

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_no_executor', $exeId, $add);
    }

    /**
     * 内容部-编辑权限申请-广电视频流管理系统权限申请   php cli/cli.php request_uri='/cli/Extapi/setApi/id/25966/api/api_ext_video_stream_system_permission'
     */
    private function __setVideoStreamSystemPermission($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(['user'], ['id'=>$id]);

        $hostUrl = 'http://v.cmpp.ifeng.com/Cmpp/runtime/interface_300591.jhtml?userId=' . $server['user'];
        $res = Tools::curl($hostUrl, 'GET', [], []);
        $res = json_decode($res, true);

        if ($res['code'] != 200) {
            $remarks = "API接口调用返回错误:{$res['msg']}";
            $exeId = 'e2';
            $add = '0';
        } else {
            $remarks = "执行成功";
            $exeId = '';
            $add = '';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_video_stream_system_permission', $exeId, $add);
    }

    /*
     * 企业微信oauth2认证服务  php cli/cli.php request_uri='/cli/Extapi/setApi/id/25966/api/api_ext_wechat_oauth'
     */
    private function __setWechatOauth($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(['user'], ['id'=>$id]);
        $infoArr = $this->__getServerInfo($id);

        $res = WechatoauthApi::getInstance()->setWechatOauth($infoArr['t_domain'], $server['user'], $infoArr['t_remark']);

        if ($res['code'] !== 200) {
            $remarks = "API接口调用返回错误:{$res['msg']}";
            $exeId = 'e2';
            $add = '0';
        } else {
            $remarks = "执行成功";
            $exeId = '';
            $add = '';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_wechat_oauth', $exeId, $add);
    }

    /**
     * 人员离职,禁用域账号 php cli/cli.php request_uri='/cli/Extapi/setApi/id/25963/api/api_ext_firework_acl'
     */
    private function __saveFireworkAcl($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'),
            array('sid'=>$id,'type'=>'t_custom_server_list'));
        $dataArr = json_decode($customInfo['info'], true);

        $addRes = MysqlIssueCommon::getInstance()->addMultData('Y_firewall_acl',$dataArr);

        if ($addRes) {
            $remarks = "保存信息成功";
            $exeId = '';
            $add = '';
        } else {
            $remarks = "保存信息失败";
            $exeId = 'e3';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_firework_acl', $exeId, $add);
    }

    /**
     * 人员离职,禁用域账号 php cli/cli.php request_uri='/cli/Extapi/setApi/id/1/api/api_ext_disable_user'
     */
    private function __disableUser($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $uid = $infoArr['t_leaver'];
        $ops = stripos($uid, '(');
        if ($ops !== false) {
            $uid = substr($uid, 0, $ops);
        }

        $res = ExtAdmsApi::getInstance()->disableUser($uid);
        if ($res['code'] !== 200) {
            $error = "API接口调用返回错误:{$res['msg']}";
        }

        if (empty($error)) {
            $remarks = "禁用账号成功";
            $exeId = '';
            $add = '';
        } else {
            $remarks = $error;
            $exeId = 'e3';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_disable_user', $exeId, $add);
    }

    /**
     * 虚拟机变更自动修改CPU/内存
     * @param $id
     */
    private function __virtualMachineAutoChange($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $vmArr = json_decode($customInfo['info'], true);

        $autoArr = $manualArr = [];
        foreach ($vmArr as $key => $vm) {
            if (!empty($vm[1]['val'])) {
                $autoArr['ip'] = $vm[0]['val'];
                $autoArr['cpu'] = (int)$vm[1]['val'];
            }
            if (!empty($vm[2]['val'])) {
                $autoArr['ip'] = $vm[0]['val'];
                $autoArr['memory'] = (int)$vm[2]['val'] * 1024;
            }
            if (!empty($vm[3]['val'])) {
                $manualArr[$key]['network_card'] = $vm[3]['val'];
            }
            if (!empty($vm[4]['val'])) {
                $manualArr[$key]['hard_disk'] = $vm[4]['val'];
            }

            // CPU/内存的变更,调用综合管理平台接口自动变更
            if (!empty($autoArr)) {
                $res = ExtVsanApi::getInstance()->vsanAutoChange($autoArr);

                if ($res !== false) {
                    if ($res['httpstatus'] != 200) {
                        $error[] = "<br>【IP:{$vm[0]['val']}】{$res['msg']}";
                    }
                } else {
                    $error[] = "<br>【IP:{$vm[0]['val']}】API出错!";
                }
            }
        }

        if (empty($error)) {
            $infoArr = $this->__getServerInfo($id);

            if (!empty($infoArr['t_needdesc']) || !empty($manualArr)) {
                // 网卡/硬盘/特殊需求的变更,转执行组手动执行
                $remarks = "自动变更完成,等待执行组对其他变更进行人工处理!";
                $exeId = 'e2';
                $add = '0';
            } else {
                $remarks = "变更已完成";
                $exeId = '';
                $add = '';
            }

        } else {
            $remarks = "自动变更时API出错:" . json_encode($error, JSON_UNESCAPED_UNICODE);
            $exeId = 'e2';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_virtual_machine_auto_change', $exeId, $add);

    }


    /**
     * 自动创建虚拟机
     * @param $id
     */
    private function __vsanAutoCreate($id)
    {
        $infoArr = $this->__getServerInfo($id);
        $vmArr = MysqlIssueVsan::getInstance()->getVsanList(['vname', 'ip', 'hostname', 'os'], ['server_id'=>$id]);

        $version    = [1=>'Centos6.5', 2=>'Centos7.2', 3=>'Windows2008R2', 4=>'Windows2012', 5=>'Ubuntu16.04'];
        $cpu        = [1=>'2', 2=>'4', 3=>'6', 4=>'8', 5=>'16'];
        $memory     = [1=>'2048', 2=>'4096', 3=>'6144', 4=>'8192', 5=>'16384'];
        
        $dataArr = [];
        foreach ($vmArr as $key => $value) {
            if ($infoArr['t_system_version'] == 3 || $infoArr['t_system_version'] == 4) {
                $remarks = "Windows操作系统主机需手动创建!";
                $exeId = 'e3';
                $add = '0';

                BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_vsan_auto_create', $exeId, $add);
                die;
            }
            $dataArr[$key] = array_merge([
                'pms'=>$id,
                'version'=>$version[$infoArr['t_system_version']],
                'memory'=>$memory[$infoArr['t_memory']],
                'cpu'=>$cpu[$infoArr['t_cpu']],
            ], $value);
        }

        $res = ExtVsanApi::getInstance()->vsanAutoCreate($dataArr);
        
        if ($res !== false) {
            if ($res['httpstatus'] != 200) {
                $error = "{$res['msg']}";
            }
        } else {
            $error = "API调用出错!";
        }
        
        if (!empty($error)) {
            $remarks = "调用自动创建API时出错:" . $error;
            $exeId = 'e3';
            $add = '0';

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_vsan_auto_create', $exeId, $add);
        }

    }

    /**
     * 暂停Puppet服务
     * @param $id string
     */
    private function __suspendPuppet($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $sn = $infoArr['t_sn'];
        $mac = $infoArr['t_mac'];

        $res = ExtPuppetApi::getInstance()->action($sn, $mac);
        if($res == 'success'){
            $msg = 'API接口执行成功!';
            $exeId = '';
            $add = '';
        }else{
            $msg = $res;
            $exeId = 'e2';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $msg, '','api', 'api_ext_suspend_puppet', $exeId, $add);
    }

    /**
     * 服务器下线 IP回收
     * @param $id string
     */
    private function __serverOfflineIpChange($id)
    {
        $masterInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'), array('slave' => $id));

        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $masterInfo['master']));
        $dataArr = json_decode($customInfo['info'], true);

        $ipArr = [];
        foreach ($dataArr as $data){
            if(!empty($data[2]['val'])){
                $ipArr[] = $data[2]['val'];
            }
            if(!empty($data[3]['val'])){
                $ipArr[] = $data[3]['val'];
            }
            if(!empty($data[4]['val'])){
                $ipArr[] = $data[4]['val'];
            }
        }

        if(!empty($ipArr)){

            $postFields['ip_name'] = implode(',',$ipArr);
            $postFields['op'] = 'offline';

            $res = ExtIdcMcApi::getInstance()->ipSource($postFields);
            if($res !== false && $res['httpstatus'] == 200){
                $msg = 'API接口执行成功!';
                $exeId = '';
                $add = '';
                BusIssueServer::getInstance()->exeServer($id, $msg, '','api', 'api_ext_server_offline_ip_change', $exeId, $add);

            }else{
                $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_server_offline_ip_change', json_encode($res));
            }
        }else{
            $msg = 'API接口执行成功!';
            $exeId = '';
            $add = '';

            BusIssueServer::getInstance()->exeServer($id, $msg, '','api', 'api_ext_server_offline_ip_change', $exeId, $add);

        }

    }

    /**
     * 设备下线 IP回收
     * @param $id string
     */
    private function __offlineIpRecovery($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(['type', 'info'], ['sid' => $id]);
        $dataArr = json_decode($customInfo['info'], true);
        $ipArr = [];

        switch($customInfo['type']) {
            case 't_custom_switch_batch_offline':
                foreach ($dataArr as $data){
                    if(!empty($data[0]['val'])){
                        $switchInfo = ExtCmdbApi::getInstance()->getSwitchInfo($data[0]['val']);
                        if(!empty($switchInfo) && $switchInfo['code'] == 200){
                            if(!empty($switchInfo['data']['int_ip'])){
                                $ipArr[] = $switchInfo['data']['int_ip'];
                            }
                            if(!empty($switchInfo['data']['oob_ip'])){
                                $ipArr[] = $switchInfo['data']['oob_ip'];
                            }
                        }

                    }
                }
                break;

            case 't_custom_batch_offline_storage_device':
                foreach ($dataArr as $data) {
                    $storageInfo = ExtCmdbApi::getInstance()->getStorageInfoBySn($data['sn']);
                    if ($storageInfo['code'] == 200 && !empty($storageInfo['data']['int_ip'])) {
                        $ipArr[] = $storageInfo['data']['int_ip'];
                    }
                }
                break;

            case 't_custom_batch_offline_virtual_server':
                foreach ($dataArr as $data) {
                    if (!empty($data['ip'])) {
                        $ipArr[] = $data['ip'];
                    }
                }
                break;

            default:
                break;
        }

        if(!empty($ipArr)){

            $postFields['ip_name'] = implode(',',$ipArr);
            $postFields['op'] = 'offline';

            $res = ExtIdcMcApi::getInstance()->ipSource($postFields);

            if($res !== false && $res['httpstatus'] == 200){
                $msg    = 'API接口执行成功!';
                $exeId  = '';
                $add    = '';
                BusIssueServer::getInstance()->exeServer($id, $msg, '','api', 'api_ext_offline_ip_recovery', $exeId, $add);

            }else{
                $this->__sendExtApiErrorMsg('zhangyang7,likx1', $id, 'api_ext_offline_ip_recovery', json_encode($res));
            }
        }else{
            $msg    = 'API接口执行成功!';
            $exeId  = '';
            $add    = '';

            BusIssueServer::getInstance()->exeServer($id, $msg, '','api', 'api_ext_offline_ip_recovery', $exeId, $add);
        }

    }

    /**
     * 修改CMDB的服务器状态信息
     * @param $id string
     * @param $type string
     */
    public function __changeCmdbServerState($id, $type)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $dataArr = json_decode($customInfo['info'], true);

        //目前是单个,以后有可能成多个
        $intIp = explode(',',$dataArr[0]['int_ip'])[0];

        $remarks = '';
        switch ($type) {
            case 'repair_start':
                $state = 3;
                $remarks = "CMDB系统中该服务器的状态已改为报修中!";
                break;
            case 'repair_end':
                $state = 1;
                $remarks = "CMDB系统中该服务器的状态已改为报修完成!";
                break;
            default:
                break;
        }

        try {
            if (empty($intIp)) {
                throw new Exception("修改服务器状态的内网IP参数为空");
            }
            if (empty($state)) {
                throw new Exception("修改服务器状态的state参数为空");
            }
            $changeRes = ExtCmdbApi::getInstance()->changeServerState($intIp, $state,
                json_encode(['pms_id' => $id, 'type' => 'pms']));
            if ($changeRes === NULL) {
                throw new Exception("调用 CMDB修改状态 接口出错");
            }
            if ($changeRes['code'] != 200) {
                throw new Exception("调用 CMDB修改状态 ,返回结果出错");
            }

            $exeId = '';
            $add = '';

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', "api_ext_cmdb_{$type}", $exeId, $add);

        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('likx1', $id, "api_ext_cmdb_{$type}", $e->getMessage());
        }

    }

    /**
     * 向CMDB系统批量录入手动维护信息
     * @param $id string
     */
    public function __cmdbManualInfoAdd($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $dataArr = json_decode($customInfo['info'], true);

        $error = array();
        switch (key($dataArr)) {
            case 'vcenter':
                foreach ($dataArr[key($dataArr)] as $single) {
                    $data['sn'] = $single[0]['val'];
                    $data['hostname'] = $single[1]['val'];
                    $data['int_ip'] = $single[2]['val'];
                    $data['cpu'] = $single[3]['val'];
                    $data['memory'] = $single[4]['val'];
                    $data['disk'] = $single[5]['val'];
                    $data['os'] = $single[6]['val'];

//                  调用CMDB接口
                    $addRes = ExtCmdbApi::getInstance()->addVmInfo($data, json_encode(['pms_id' => $id, 'type' => 'pms']));

                    if ($addRes !== false) {
                        if ($addRes['code'] != 200) {
                            $error[] = "{$addRes['msg']}! 【SN: " . $data['sn'] . "】<br>";
                        }
                    } else {
                        $error[] = "API出错! 【SN: " . $data['sn'] . "】<br>";
                    }
                    unset($data);
                }
                break;
            default:
                break;
        }

        if (empty($error)) {
            $remarks = "批量录入手动维护信息成功!";
            $exeId = '';
            $add = '';
        } else {
            $remarks = "批量录入手动维护信息时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
            $exeId = 'e2';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_cmdb_manual_info_add', $exeId, $add);
    }

    /**
     * 存储设备下线
     * @param $id
     */
    private function __cmdbStorageDeviceDel($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('type', 'info'),
            array('sid' => $id));
        $InfoArr = json_decode($customInfo['info'], true);
        $error = array();

        foreach ($InfoArr as $key => $single) {
            //调用CMDB接口
            $delRes = ExtCmdbApi::getInstance()->delStorageInfoBySn(['sn'=>$single['sn'],'idc_id'=>$single['idc_id']], json_encode(['pms_id' => $id, 'type' => 'pms']));

            if ($delRes !== false) {
                if ($delRes['code'] != 200) {
                    $error[] = "{$delRes['msg']}!【SN: " . $single['sn'] . "】<br>";
                }
            } else {
                $error[] = "API出错! 【SN: " . $single['sn'] . "】<br>";
            }
        }

        if (empty($error)) {
            $remarks = "更新CMDB存储设备信息成功";
            $exeId = '';
            $add = '';
        } else {
            $remarks = "更新CMDB存储设备信息时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
            $exeId = 'e4';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_cmdb_storage_device_del', $exeId, $add);
        
    }

    /**
     * 提案完结时,根据SN删除CMDB旧数据,等待自动上报,避免重复
     * @param $id string
     */
    private function __cmdbVirtualServerDel($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('type', 'info'),
            array('sid' => $id));
        $refreshArr = json_decode($customInfo['info'], true);
        $error = array();

        if (in_array($customInfo['type'], ['t_custom_batchosrefreshexsi', 't_custom_batchosrefreshvsan', 't_custom_batch_offline_virtual_server'])) {
            foreach ($refreshArr as $key => $single) {
                //调用CMDB接口
                $delRes = ExtCmdbApi::getInstance()->delVirtualServerInfoBySn($single['sn'], json_encode(['pms_id' => $id, 'type' => 'pms']));

                if ($delRes !== false) {
                    if ($delRes['code'] != 200) {
                        $error[] = "{$delRes['msg']}!【SN: " . $single['sn'] . "】<br>";
                    }
                } else {
                    $error[] = "API出错! 【SN: " . $single['sn'] . "】<br>";
                }
            }

            if (empty($error)) {
                $remarks = "删除CMDB旧虚机信息成功";
                $exeId = '';
                $add = '';
            } else {
                $remarks = "删除CMDB旧虚机信息时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                $exeId = 'e4';
                $add = '0';
                //'虚拟机批量下线提案'
                if ($customInfo['type'] == 't_custom_batch_offline_virtual_server') {
                    $exeId = 'e4';
                }
            }

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_cmdb_virtual_server_del', $exeId, $add);
        }

    }

    /**
     * 提案完结时,根据SN更新CMDB物理服务器数据
     * @param $id string
     */
    private function __cmdbPhysicalServerUpdate($id)
    {
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('type', 'info'),
            array('sid' => $id));
        $refreshArr = json_decode($customInfo['info'], true);
        $error = array();

        if (in_array($customInfo['type'], ['t_custom_batchosrefreshphysicalout', 't_custom_batchosrefreshphysicalin'])) {
            foreach ($refreshArr as $key => $single) {
                //调用CMDB接口
                if ($single['os'] == 'Windows-2008R2') {
                    $win = '23';
                } elseif ($single['os'] == 'Windows-2012R2') {
                    $win = '24';
                } else {
                    $win = '';
                }
                $upRes = ExtCmdbApi::getInstance()->updatePhysicalServerInfoBySn($single['sn'], $win, json_encode(['pms_id' => $id, 'type' => 'pms']));

                if ($upRes !== false) {
                    if ($upRes['code'] != 200) {
                        $error[] = "{$upRes['msg']}!【SN: " . $single['sn'] . "】<br>";
                    }
                } else {
                    $error[] = "API出错! 【SN: " . $single['sn'] . "】<br>";
                }
            }

            if (empty($error)) {
                $remarks = "更新CMDB物理机信息成功";
                $exeId = '';
                $add = '';
            } else {
                $remarks = "更新CMDB物理机信息时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                $exeId = 'e4';
                $add = '0';
            }

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_cmdb_physical_server_update', $exeId, $add);
        }
    }


    /**
     * 提案完结时,检测是否创建相应子提案
     * @param $id string
     */
    private function __checkFinishIssue($id)
    {
        $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('id', 'server_type', 'operator_agent'),
            array('status' => 0, 'action' => 'exe', 'server_id' => $id, 'operator' => 'api_ext_checkFinish'), null, 'id desc');

        if (!empty($nodeInfo)) {
            $remarks = '提案完成脚本执行完成';
            $informs = '';
            BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_checkFinish', '', '');

            $php = \Yaf\Registry::get('config')->get('php.path');
            $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/Autocreate/checkFinish/id/{$id}' &";
            $r = popen($cmd, 'r');
            pclose($r);
        }

    }

    /**
     * 装机系统API,导入配置文件,生成安装信息
     * @param $id string
     */
    private function __installSys($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $data['sn'] = $infoArr['t_SN'];
        $data['ip'] = $infoArr['t_ipaddr'];
        $ipArr = explode('.', $data['ip']);
        $data['hostname'] = 'fromifos-' . strtolower($infoArr['t_SN'] . date('YmdHi') . '-' . $ipArr[3] . 'v' . $ipArr[2]);
        $data['hardwarename'] = $infoArr['t_HardwareList'];
        $data['hardwareid'] = $infoArr['t_HardwareListid'];
        $data['ostmpname'] = $infoArr['t_ostp'];
        $data['ostmpid'] = $infoArr['t_ostpid'];
        $data['oobip'] = '192.168.83.71';

        $idcInfo = array('10.32.' => array('cn' => '石景山', 'code' => 'sjs'),
            '10.50.' => array('cn' => '石景山', 'code' => 'sjs'),
            '10.90.' => array('cn' => '三元桥', 'code' => 'syq')
        );

        if (!empty($idcInfo[substr($data['ip'], 0, 6)])) {
            $data['idc'] = $idcInfo[substr($data['ip'], 0, 6)]['cn'];
            $data['idccode'] = $idcInfo[substr($data['ip'], 0, 6)]['code'];
        } else {
            die('非法IP');
        }

        $device = '[' . json_encode($data) . ']';

        $token = ExtIdcOsApi::getInstance()->getToken('zhangyang7', 'Jx3tVm$C372s2xxkjJ3=');
        ExtIdcOsApi::getInstance()->setToken($token);
//        $res = ExtIdcOsApi::getInstance()->postDevice($device);

        $exeRes = false;
//        if ($res[0]['success']) {
        if (true) {
            $remarks = 'API执行成功,具体信息可在装机系统中查看';
            $informs = '';
            $exeRes = true;
            BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_installsys', '', '');
        }
        $apiUrl = urlencode("http://loda.devops.ifengidc.com:9999/api/device");
        $this->__exeApiResult($id, 'api_ext_installsys', 'zhangyang7', 1, $exeRes, $apiUrl, '添加装机数据失败!');

    }

    /**
     * 设置MAC到DHCP配置文件中
     * @param $id string 提案ID
     * @param $type string 添加或删除
     */
    private function __setMacToDhcp($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $mac = $infoArr['t_macaddr'];

        $idcInfo = array('10.32.' => array('cn' => '石景山', 'code' => 'sjs'),
            '10.50.' => array('cn' => '石景山', 'code' => 'sjs'), '10.90.' => array('cn' => '三元桥', 'code' => 'syq')
        );

        if (!empty($idcInfo[substr($infoArr['t_ipaddr'], 0, 6)])) {
            $idc = $idcInfo[substr($infoArr['t_ipaddr'], 0, 6)]['code'];
        } else {
            die('非法IP');
        }
        $dhcp['mac'] = $mac;
        $dhcp['idc'] = $idc;
        $dhcp['type'] = 'add';
        $apiRes = ExtSwitchApi::getInstance()->setMacToDhcp($dhcp);

        $exeRes = false;
        if ($apiRes) {
            $remarks = 'API执行成功,MAC已添加到HDCP配置文件并Reload;';
            $informs = '';
            $exeRes = true;
            BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_setDhcp', '', '');
        }
        $apiUrl = urlencode("http://switch.ifos.ifengidc.com:8081/Api/Control/setMacToDhcp/mac/{$mac}/idc/{$idc}/type/add");
        $this->__exeApiResult($id, 'api_ext_setDhcp', 'zhangyang7', 1, $exeRes, $apiUrl, '设置DHCP服务器配置文件失败!');
    }


    /**
     * 通过OOB设置服务器重启方式
     * @param $id string 提案ID
     * @param $type string 添加或删除
     */
    private function __setOob($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $ip = $infoArr['t_ipaddr'];


        $apiRes = ExtOobApi::getInstance()->setPxeReset('192.168.83.71', 'DELL');

        $exeRes = false;
        if ($apiRes['status']) {
            $remarks = 'API执行成功,服务器已设置为PXE重启,并硬重启';
            $informs = '';
            $exeRes = true;
            BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_setPxe', '', '');
        }
        $apiUrl = urlencode("http://auto.ifos.ifengidc.com/api/Oob_Control/setPexRest/ip/{$ip}/type/add");
        $this->__exeApiResult($id, 'api_ext_setPxe', 'zhangyang7', 1, $exeRes, $apiUrl, "设置服务器PXE重启失败!{$apiRes['msg']}");
    }

    /**
     * 检测重装进度
     * @param $id string 提案ID
     */
    private function __checkInsitallStatus($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $query['status'] = 1;
        $query['sn'] = $infoArr['t_SN'];

        $token = ExtIdcOsApi::getInstance()->getToken('zhangyang7', 'Jx3tVm$C372s2xxkjJ3=');
        ExtIdcOsApi::getInstance()->setToken($token);

        $get = true;

        while ($get) {
            sleep(10);
            $devices = ExtIdcOsApi::getInstance()->getDeviceList($query);
            if ($devices !== false) {
                $get = false;
            }
        }

        $issueInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('id'),
            array('server_id' => $id, 'api' => 'api_ext_checkStatus'));

        if (empty($issueInfo)) {
            $data['server_id'] = $id;
            $data['api'] = 'api_ext_checkStatus';
            $data['status'] = 0;
            $data['result'] = json_encode(array('state' => 'PENDING', 'install_id' => $devices[0]['id']));
            MysqlIssueTiming::getInstance()->addNewTiming($data);
        }

    }

    /**
     * 网宿清理CDN接口
     * @param $id string
     */
    private function __yunCdn($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $urlArr = explode('<br>', $infoArr['t_url']);
        $cpUrlArr = [];
        foreach ($urlArr as $url) {
            $url = trim($url);
            $cpUrlArr[] = $url;
        }
        
        $uriStr = implode(',', $cpUrlArr);
        
        $res['CpCdn'] = ExtYun::getInstance()->cpCdn($uriStr);
        $res['QingCdn'] = ExtYun::getInstance()->Qingcdn($uriStr);
        $res['Ksyun'] = ExtYun::getInstance()->Ksyun($uriStr);
        $res['AliYun'] = ExtYun::getInstance()->AliYun($uriStr);
        $res['TencentYun'] = ExtYun::getInstance()->TencentYun($uriStr);
        $res['PinganYun'] = ExtYun::getInstance()->PinganYun($uriStr);

        $informs = '';
        BusIssueServer::getInstance()->exeServer($id, json_encode($res, JSON_UNESCAPED_UNICODE), $informs, 'api', 'api_ext_yunCdn', '', '');
    }

    /**
     * 凤凰令牌申请自动生成Key
     * @param $id string
     */
    private function __tokenKey($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(array('user'), array('id' => $id));
        $user = strtolower($server['user']);
        $ch = curl_init("https://bastion.ifeng.com/create/{$user}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $output = curl_exec($ch);

        if ($output == 'done') {
            $remarks = '操作成功,令牌已生成';

            //将提案中的手机号更新到用户手机信息
            $info = $this->__getServerInfo($id);
            $mobile = $info['t_mobile'];
            $user = $server['user'];

            $usrInfo = MysqlIssueMobile::getInstance()->getOneMobileByWhere(array('user' => $user));
            if (empty($usrInfo)) {
                MysqlIssueMobile::getInstance()->addNewMobile(
                    array('user' => $user, 'mobile' => $mobile, 'status' => 1));
            } else {
                MysqlIssueMobile::getInstance()->updateMobileInfo(
                    array('mobile' => $mobile, 'u_time' => date('Y-m-d H:i:s')), array('user' => $user));
            }

        } else {
            $remarks = '操作失败,请联系weichuan(魏川)';
        }

        $informs = '';
        BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_token', '', '');
    }

    /**
     * 跳板机发送通知
     * @param $id string
     */
    private function __jumpBox($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(array('user'), array('id' => $id));
        $user = strtolower($server['user']);

        $ch = curl_init("http://admin.jumpbox.ifengidc.com/api/email?uid={$user}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $output = json_decode(curl_exec($ch), true);

        $exeRes = false;
        if ($output !== null && $output['code'] == 200 && $output['data']) {
            $remarks = '请按收到的邮件提示进行注册!';
            $informs = '';
            $exeRes = true;
            BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_jumpbox', '', '');
        }
        $apiUrl = urlencode("http://admin.jumpbox.ifengidc.com/api/email?uid={$user}");
        $this->__exeApiResult($id, 'api_ext_jumpbox', 'zhaosy,lisai', 1, $exeRes, $apiUrl, '接口执行失败');
    }

    /**
     * 服务器下线时,跳板机删除IP
     * @param $id string
     */
    private function __delJumpbox($id)
    {
        $masterInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master', 'm_type'),
            array('slave' => $id, 'type' => 'son'));

        if ($masterInfo['m_type'] == 'serverosrefresh') {
            $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'),
                array('sid' => $id));
            $offlineArr = json_decode($customInfo['info'], true);
        } else {
            $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'),
                array('sid' => $masterInfo['master']));
            $offlineArr = json_decode($customInfo['info'], true);
        }

        $ipArr = array();
        foreach ($offlineArr as $offline) {
            if(!empty($offline)){
                switch ($masterInfo['m_type']) {
                    case 'batchoffline':
                    case 'serveroffline':
                        if (!empty($offline[2]['val'])) {
                            $ipArr[] = $offline[2]['val'];
                        }
                        break;
                    
                    case 'batchmigrate':
                    case 'servermigrate':
                        if ($offline[10]['val'] == '是') {
                            if (!empty($offline[2]['val'])) {
                                $ipArr[] = $offline[2]['val'];
                            }
                        }
                        break;
                    
                    case 'serverosrefresh':
                        if (!empty($offline['ip'])) {
                            $ipArr[] = $offline['ip'];
                        }
                        break;
                    
                    default:
                        break;
                }
            }
        }

        try {
            if (empty($ipArr)) {
                $remarks = '无需要删除跳板机权限的服务器!';
            } else {
                $delRes = ExtJumpboxApi::getInstance()->delIp($ipArr);
                if ($delRes === false) {
                    throw new Exception("调用删除跳板机权限接口出错");
                }
                if ($delRes['code'] != 200) {
                    throw new Exception("调用删除跳板机权限接口时,返回结果出错");
                }
                $remarks = implode('<br>', $ipArr) . "<br>已删除";
            }
            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_jumpbox_del', '', '');

        } catch (\Exception $e) {
//            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_jumpbox_del', $e->getMessage());
            $this->__sendExtApiErrorMsg('likx1', $id, 'api_ext_jumpbox_del', $e->getMessage());
        }

    }

    /**
     * 服务器下线时,跳板机删除IP
     * @param $id string
     */
    private function __changeJumpbox($id)
    {
        $info = $this->__getServerInfo($id);

        try {
            if (empty($info['t_change_server_ip'])) {
                throw new Exception('提案详情中无IP变更信息!');
            }

//            $changeIpArr = explode('<br>', $info['t_change_server_ip']);
//
//            $error = array();
//            foreach ($changeIpArr as $ipString) {
//                $ipArr = explode(' -> ', $ipString);
//                $migrateRes = ExtJumpboxApi::getInstance()->migrateIp($ipArr[0], $ipArr[1]);
//                if ($migrateRes === false) {
//                    $error[] = "{$ipString}调用跳板机接口时出错";
//                } else {
//                    if ($migrateRes['code'] != 200) {
//                        $error[] = "{$ipString}调用接口时,返回结果出错:{$migrateRes['msg']}";
//                    }
//                }
//            }

//            if (!empty($error)) {
//                $remarks = "错误信息:" . implode('<br>', $error);
//                BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_jumpbox_change', 'e8', 0);
//            }else{
                $remarks = $info['t_change_server_ip'] . "<br>已全部迁移成功";
                BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_jumpbox_change', '', '');
//            }

        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_jumpbox_change', $e->getMessage());
        }

    }

    /**
     * 批量克隆虚拟机
     * @param $id string
     */
    private function __vsanBatchClone($id)
    {
        try {
            $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'),
                array('sid' => $id, 'type' => 't_vsan_info'));
            if (empty($customInfo['info'])) {
                throw new Exception("错误信息:批量创建虚拟机信息为空");
            }
            $batchCloneArr = json_decode($customInfo['info'], true);
            if (empty($batchCloneArr)) {
                throw new Exception("错误信息:批量创建虚拟机信息格式非法");
            }

//            $hosts = array();
//            $hosts[] = array('ip'=>'172.27.205.10','init'=>'172.27.30.10','username'=>'pms@11fvc.local',
//                'posi'=>'I03',"name"=>"vsan-1");
//            $system = array('moban'=>1);
//            $custom = json_encode(array('hosts'=>$hosts,'system'=>$system));

            $res = ExtVsanApi::getInstance()->batchClone($customInfo['info']);
            if ($res['httpstatus'] == 200) {
                $remarks = "虚拟机已批量创建完毕,详细信息请查看提案中的EXCEL文件";

                //更新提案内容
                $server = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
                    array('id' => $id));
                $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                    array('id' => $server['info_id']));
                $infoArr = json_decode($serverInfo['infoJson'], true);

                $infoArr['t_batch_clone_file'] = "<a href=\"{$res['data']['href']}\">批量克隆虚拟机结果信息文件,点击下载</a>";
                $updateServerInfoRes = MysqlIssueServerinfo::getInstance()->updateServerInfo(
                    array('infoJson' => json_encode($infoArr, JSON_UNESCAPED_SLASHES)), array('id' => $server['info_id']));
                if (!$updateServerInfoRes) {
                    $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_vsan_batch_clone', "更新文档时错误");
                } else {
                    BusIssueServer::getInstance()->MysqlToRedisServer($id);
                }

                $exeId = $add = '';
            } else {
                $remarks = "虚拟机创建过程中出错,错误信息:{$res['msg']}";
                $exeId = 'e3';
                $add = '0';
            }

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_vsan_batch_clone', $exeId, $add);

        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_vsan_batch_clone', $e->getMessage());
        }

    }

    /**
     * 申请服务器权限
     * @param $id string
     */
    private function __serverPermissions($id)
    {
        $infoArr = $this->__getServerInfo($id);
        $sudo = ($infoArr['t_sudo'] == '1') ? 0 : 1;
        $server = MysqlIssueServer::getInstance()->getOneServer(array('user'), array('id' => $id));
        $user = strtolower($server['user']);
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $ipGruopArr = json_decode($customInfo['info'], true);

        $errorArr = array();
        foreach ($ipGruopArr as $group) {
            $ipList = array();
            foreach ($group['IPlist'] as $ipStr) {
                if (strstr($ipStr, '-') !== false) {
                    $ipStr = str_replace(',', "\n", $ipStr);
                    $ipRes = $this->__checkIp($ipStr);
                    if ($ipRes['status'] != true) {
                        $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_sPermission', 'IP段格式错误');
                        die;
                    } else {
                        $ipList = array_merge($ipList, $ipRes['data']);
                    }
                } else {
                    $ipList = array_merge($ipList, explode(',', $ipStr));
                }
            }
            $modifyRes = ExtJumpboxApi::getInstance()->modifyIpgroup(
                $user, $group['name'], implode("\n", $ipList), $sudo, $id);

            if ($modifyRes === false) {
                $errorArr[] = 'PMS系统接口调用失败';
            } else {
                if ($modifyRes['code'] != 200) {
                    $errorArr[] = $group['name'] . ' 错误信息:' . $modifyRes['msg'];
                }
            }
        }

        if (empty($errorArr)) {
            $remarks = 'API接口操作成功,IP组已添加完毕!';
            $exeId = '';
            $add = '';
        } else {
            $exeId = 'e2';
            $add = '0';
            $errorStr = implode('<br>', $errorArr);
            $remarks = "API接口操作失败:<br>{$errorStr}";
        }

        $informs = '';
        BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_sPermission', $exeId, $add);
    }

    /**
     * Yum安装软件
     * @param $id string
     * @return array
     */
    public function __yumInstall($id)
    {
        $infoArr = $this->__getServerInfo($id);
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));

        $ipRes = $this->__checkIp($infoArr['t_host']);
        if ($ipRes['status']) {
            $ipArr = $ipRes['data'];
            $softArr = json_decode($customInfo['info'], true);

            $resultArr = array();
            foreach ($softArr as $soft) {
                $yumRes = ExtAnsibleApi::getInstance()->yumInstall($ipArr, $soft['name'], $soft['version']);
                if ($yumRes['status']) {
                    $resultArr["{$soft['name']}:{$soft['version']}"] = array('state' => 'PENDING', 'task-id' => $yumRes['data']['task-id']);
                } else {
                    $resultArr["{$soft['name']}:{$soft['version']}"] = array('state' => 'FAILED', 'task-id' => '');
                }
            }

            $issueInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('id'),
                array('server_id' => $id, 'api' => 'api_ext_yumInstall'));

            if (empty($issueInfo)) {

                $data['server_id'] = $id;
                $data['api'] = 'api_ext_yumInstall';
                $data['status'] = 0;
                $data['result'] = json_encode($resultArr);
                MysqlIssueTiming::getInstance()->addNewTiming($data);
            } else {

                MysqlIssueTiming::getInstance()->updateTimingInfo(array('result' => json_encode($resultArr)),
                    array('server_id' => $id, 'api' => 'api_ext_yumInstall'));
            }
        }
    }

    /**
     * 保存技术系统信息
     * @param $id string
     * @return array
     */
    public function __saveSysName($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(['i_department', 'info_id'], array('id' => $id));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);

        $deptArr = explode(' - ',$server['i_department']);

        $data = [];
        $data['sys_name'] = $infoArr['t_sysname'];
        $data['admin'] = $infoArr['t_sysadmin'];
        $data['reviewer'] = $infoArr['t_sysauditor'];
        $data['department'] = $deptArr[0];
        $data['center'] = $deptArr[1];
        $data['domain'] = $infoArr['t_sysdomain'];
        $data['introduction'] = $infoArr['t_sysdesc'];

        $addRes = MysqlIssueCommon::getInstance()->addNewData('TechnologyDepartSystem',$data);
        if(!$addRes){
            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_save_sys_name', json_encode($addRes, JSON_UNESCAPED_UNICODE));
        }else{
            BusIssueServer::getInstance()->exeServer($id, '数据保存成功', '', 'api', 'api_ext_save_sys_name', '', '');
        }

    }

    /**
     * 混合云系统添加用户
     * @param $id string
     * @return array
     */
    public function __addMaxCloudsUser($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(['user', 'info_id'], array('id' => $id));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);

        $uid = $server['user'];
        $rid = $infoArr['t_sysroleid'];

        $addRes = ExtMaxCloudsApi::getInstance()->addSystemUser($rid, $uid);

        $api = "api_ext_maxclouds_add_user";
        if(!empty($addRes) && $addRes['code'] == 200){
            $msg = "用户添加成功";
            $exeId = $add = '';
        }else{
            $msg = "用户添加失败";
            $exeId = 'e2';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', $api, $exeId, $add);
    }

    /**
     * 消息系统权限申请
     * @param $id string
     * @param $type string
     * @return array
     */
    public function __messageSys($id, $type)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(['user', 'info_id'], array('id' => $id));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);

        switch ($type) {

            case 'wechat':
                $data = [];
                $data['owners'] = $server['user'];
                $data['appidname'] = $infoArr['t_sysname'];
                $data['ratedlimit'] = $infoArr['t_daylimit'];
                $data['remark'] = $infoArr['t_desc'];

                $addRes = ExtMessageApi::getInstance()->wechart($data);
                $api = "api_ext_msg_wechat";
                if(!empty($addRes) && $addRes['code'] == 200){
                    $msg = "权限添加成功";
                    $exeId = $add = '';
                }else{
                    $msg = "权限添加失败,错误信息:".json_decode($addRes);
                    $exeId = 'e2';
                    $add = '0';
                }
                break;

            case 'mail':
                $data = [];
                $data['owners'] = $server['user'];
                $data['appidname'] = $infoArr['t_sysname'];
                $data['ratedlimit'] = $infoArr['t_daylimit'];
                $data['remark'] = $infoArr['t_desc'];

                $addRes = ExtMessageApi::getInstance()->mail($data);
                $api = "api_ext_msg_mail";
                if(!empty($addRes) && $addRes['code'] == 200){
                    $msg = "权限添加成功";
                    $exeId = $add = '';
                }else{
                    $msg = "权限添加失败";
                    $exeId = 'e2';
                    $add = '0';
                }
                break;

            default:
                die;
                break;
        }

        BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', $api, $exeId, $add);
    }

    /**
     * 推送KEY
     * @param $id string
     * @return array
     */
    public function __pushKey($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(array('user', 'i_type', 'info_id'), array('id' => $id));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);

        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $ipStr = json_decode($customInfo['info'], true);


        $ipRes['status'] = false;

        if ($server['i_type'] == 'pushkey') {
//            $ipRes = $this->__checkIp($infoArr['t_host']);
            $ipRes['status'] = true;
            $ipRes['data'] = explode("\n", $ipStr['ip']);
        }

        //else 由外部接口把该提案关闭

        if ($ipRes['status']) {

            $addRes = ExtAnsibleNewApi::getInstance()->pushKey($server['user'], $ipRes['data'], '1');

            if ($addRes !== false && $addRes['code'] == 200) {
                if (empty($addRes['data']['fail'])) {
                    $msg = 'Key推送完成, 结果:全部成功';
                    $exeId = $add = '';
                } else {
                    $exeId = 'e2';
                    $add = '0';
                    $msg = json_encode($addRes['data']['fail'], JSON_UNESCAPED_UNICODE);
                }

                BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_pushKey', $exeId, $add);

            } else {
                $this->__sendExtApiErrorMsg('likx1', $id, 'api_ext_pushKey', json_encode($addRes, JSON_UNESCAPED_UNICODE));
            }
        }

    }

    /**
     * 设置公网NAT
     * @param $id string
     * @return array
     */
    public function __setNat($id)
    {
        Log::getInstance('api')->log('', "{$id} __setNat",'START');
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info', 'type'), array('sid' => $id));
        $intIpArr = json_decode($customInfo['info'], true);

        if (!empty($intIpArr)) {
            $error = array();
            $area = substr($customInfo['type'],strrpos($customInfo['type'], '_')+1) == 'syq' ? 'syqapplynat' : 'applynat';

            foreach ($intIpArr as $intIp) {
                if (empty($intIp['type'])) {
                    $type = '';
                } else {
                    $type = $intIp['type'];
                }

                Log::getInstance('api')->log('', "{$id} __setNat","开始调用{$intIp['ip']}");

                $setRs = ExtSwitchApi::getInstance()->setIpToNat(array('ip' => $intIp['ip'], 'type' => $type,
                    'port' => $intIp['port'], 'area'=>$area));

                Log::getInstance('api')->log('', "{$id} __setNat",'调用结果'.json_encode($setRs));
                
                if ($setRs) {
                    if ($setRs['code'] != 200) {
                        $error[$intIp['ip']] = '设置公网NAT提案API接口,执行返回400';
                    } else {
                        if (!$setRs['data']['status']) {
                            $errStr = '';
                            foreach ($setRs['data']['msg'] as $v) {
                                $errStr .= '<br>'.$v['ip'].'--'.$v['err'].'';
                            }

                            $error[$intIp['ip']] = "API接口操作失败,错误信息:{$errStr}";
                        }
                    }
                } else {
                    $error[$intIp['ip']] = '设置公网NAT提案API接口,执行无返回值';
                }
            }

            Log::getInstance('api')->log('', "{$id} __setNat",'循环结束'.json_encode($error));

            if (empty($error)) {
                $remarks = 'API接口操作成功,公网NAT设置完毕!';
                $exeId = '';
                $add = '';
            } else {
                $exeId = 'e2';
                $add = '0';
                $errorStr = '';
                foreach ($error as $ip => $e) {
                    $errorStr .= $ip . ":" . $e . "<br>";
                }
                $remarks = $errorStr;
            }

            $informs = '';
            BusIssueServer::getInstance()->exeServer($id, $remarks, $informs, 'api', 'api_ext_setNat', $exeId, $add);

            Log::getInstance('api')->log('', '__setNat','END');

        } else {
            $error[] = '设置公网NAT提案无内网IP值';
        }

        if (!empty($error)) {
//            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'api_ext_setNat', $error);
            $this->__sendExtApiErrorMsg('likx1', $id, 'api_ext_setNat', json_encode($error));
        }

    }


    /**
     * 服务器重装推KEY
     * @param $id string
     * @return array
     */
    public function __resetPushKey($id)
    {
        $infoArr = $this->__getServerInfo($id);

        $server = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('checkJson'), array('id' => $id));
        $checkArr = json_decode($server['checkJson'], true);

        $ipArr = array($infoArr['t_ipaddr']);
        $user = $checkArr['operator'];

        $addRes = ExtAnsibleApi::getInstance()->addUser($ipArr, $user);

        if ($addRes['status']) {
            $result = array('state' => 'PENDING', 'task-id' => $addRes['data']['task-id']);
        } else {
            $result = array('state' => 'FAILED', 'task-id' => '');
        }
        $issueInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('id'),
            array('server_id' => $id, 'api' => 'api_ext_pushSshKey'));

        if (empty($issueInfo)) {
            $data['server_id'] = $id;
            $data['api'] = 'api_ext_pushSshKey';
            $data['status'] = 0;
            $data['result'] = json_encode($result);
            MysqlIssueTiming::getInstance()->addNewTiming($data);
        } else {
            MysqlIssueTiming::getInstance()->updateTimingInfo(array('result' => json_encode($result)),
                array('server_id' => $id, 'api' => 'api_ext_pushSshKey'));
        }


    }


    /**
     * 将API直接结果保存,并处理
     * @param $result bool 执行结果
     * @param $id string 提案ID
     * @param $api string 接口名称
     * @param $uid string 邮件通知人
     * @param $interval int 脚本执行间隔
     * @param $apiUrl string 接口URL
     * @param $msg string 错误提示
     */
    private function __exeApiResult($id, $api, $uid, $interval, $result, $apiUrl, $msg)
    {

        $reloadInfo = MysqlIssueReload::getInstance()->getOneReload(array('id'),
            array('status' => 0, 'server_id' => $id, 'api' => $api));

        if ($result) {
            if (!empty($reloadInfo)) {
                MysqlIssueReload::getInstance()->update(array('status' => 1), array('server_id' => $id, 'api' => $api));
            }
        } else {
            if (empty($reloadInfo)) {
                $data['server_id'] = $id;
                $data['api'] = $api;
                $data['interval'] = $interval;
                $data['status'] = 0;
                $data['email'] = date('Y-m-d H:i:s');
                MysqlIssueReload::getInstance()->addNewReload($data);
                $this->__sendExtApiErrorMsg($uid, $id, $apiUrl, $msg);
            } else {
                $emailInfo = MysqlIssueReload::getInstance()->getOneReload(array('email'),
                    array('status' => 0, 'api' => $api), null, 'email desc');
                //是否发送报警邮件,每小时发送一次
                if (time() - strtotime($emailInfo['email']) >= 3600) {
                    $this->__sendExtApiErrorMsg($uid, $id, $apiUrl, $msg);
                    MysqlIssueReload::getInstance()->update(array('email' => date('Y-m-d H:i:s')),
                        array('status' => 0, 'api' => $api));
                }
            }
        }
    }


    /**
     * 监控操作函数
     * @param $id string
     * @param $type string
     * @param $api string
     */
    private function __monitor($id, $type, $api)
    {
        try {
            if (!in_array($type, array('offline', 'online', 'delete'))) {
                throw new Exception('输入参数错误:非法动作');
            }

            $hostnameArr = $serverArr = $resError = [];
            $serverTypeArr = [
                'batchofflinevirtualserver',
                'batchosrefreshphysicalin',
                'batchosrefreshphysicalout',
                'batchosrefreshvsan',
                'batchosrefreshexsi',
            ];

            $server = MysqlIssueServer::getInstance()->getOneServer(array('user', 'i_type'), array('id' => $id));
            if (in_array($server['i_type'], $serverTypeArr)) {
                $info = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
                foreach (json_decode($info['info'], true) as $single) {
                    $hostnameArr[] = $single['hostname'];
                }
            } else {
                $info = $this->__getServerInfo($id);
                $hostnameArr = explode('<br>', $info['t_hostname_list']);
            }

            switch ($type) {
                case 'offline':
                    foreach ($hostnameArr as $hostname) {
                        if (empty(trim($hostname))) continue;
                        sleep(2);
                        $serverArr[] = array('ns' => 'api.loda', 'type' => 'machine',
                            'resourceid' => $hostname, 'update' => array('status' => 'offline'));
                        $result = ExtMonitor::getInstance()->modifyStatus(json_encode($serverArr));
                        if ($result['httpstatus'] != 200) {
                            $resError[] = "主机名:{$hostname}的服务器监控下线失败,错误信息:{$result['msg']}";
                        }
                    }
                    $action = '下线';
                    break;
                case 'online':
                    foreach ($hostnameArr as $hostname) {
                        if (empty(trim($hostname))) continue;
                        sleep(2);
                        $serverArr[] = array('ns' => 'api.loda', 'type' => 'machine',
                            'resourceid' => $hostname, 'update' => array('status' => 'online'));
                        $result = ExtMonitor::getInstance()->modifyStatus(json_encode($serverArr));
                        if ($result['httpstatus'] != 200) {
                            $resError[] = "主机名:{$hostname}的服务器监控上线失败,错误信息:{$result['msg']}";
                        }
                    }
                    $action = '上线';
                    break;
                case 'delete':
                    foreach ($hostnameArr as $hostname) {
                        if (empty(trim($hostname))) continue;
                        sleep(2);
                        $serverArr[] = array('ns' => '', 'type' => 'machine', 'resourceid' => $hostname);
                        $result = ExtMonitor::getInstance()->delServer(json_encode($serverArr));
                        if ($result['httpstatus'] != 200) {
                            $resError[] = "主机名:{$hostname}的服务器监控删除失败,错误信息:{$result['msg']}";
                        }
                    }
                    $action = '删除';
                    break;
                default:
                    $action = ' ';
                    break;
            }

            if (!empty($resError)) {
                throw new Exception("服务器监控{$action}失败,错误信息:" . json_encode($resError, JSON_UNESCAPED_UNICODE));
            }

            $remarks = json_encode($hostnameArr) . "<br>以上服务器的监控已{$action}!";
            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', $api, '', '');

        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('likx1', $id, $api, $e->getMessage());
        }

    }


    /**
     * Adms系统 改变用户所在群组
     * @param $id string
     * @param $type string
     * @param $api string
     */
    private function __changeUserGroup($id, $type, $api)
    {
        $error = '';
        if (!in_array($type, array('add', 'delete'))) {
            $error = "输入参数错误:{$id}非法动作:{$type}";
        }

        $server = MysqlIssueServer::getInstance()->getOneServer(array('user', 'i_type'), array('id' => $id));

        switch ($server['i_type']) {
            case 'kubernetesmonitor':
                $gid = 'K8s-grafana';
                $res = ExtAdmsApi::getInstance()->addUserToGroup($gid, $server['user']);
                if ($res['code'] !== 200) {
                    $error = "API接口调用返回错误:添加用户至群组'K8s-grafana'时出错:{$res['msg']} {$res['data'][0]}";
                }
                break;

            default:
                break;
        }

        if (empty($error)) {
            $remarks = "API接口调用成功!";
            $exeId = '';
            $add = '';
        } else {
            $remarks = $error;
            $exeId = 'e3';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', $api, $exeId, $add);
    }

    /**
     * CMDB系统
     * @param $id string
     * @param $type string
     * @param $api string
     */
    private function __cmdb($id, $type, $api)
    {
        try {
            if (!in_array($type, array('offline', 'online'))) {
                throw new Exception('输入参数错误:非法动作');
            }
            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_type', 'info_id'), array('id' => $id));
            $serverInfo = MysqlIssueServerinfo::getInstance()->getOneServerInfo(
                array('infoJson'), array('id' => $server['info_id']));
            $infoArr = json_decode($serverInfo['infoJson'], true);

            if ($type == 'offline') {
                $masterInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('m_type'), array('slave' => $id,
                    's_type' => 'batchofflinecmdb'));
                if ($masterInfo['m_type'] == 'batchmigrate' || $masterInfo['m_type'] == 'servermigrate') {
                    $type = 'migrateOffline';
                }
            }

            if (in_array($server['i_type'], array('batchmigrateonlinereinstall', 'batchmigrateonline'))) {
                $type = 'migrateOnline';
            }

            switch ($type) {
                case 'offline':
                    $preg2 = "/<td.*?>(.*?)<\/td>/";
                    preg_match_all($preg2, $infoArr['t_offline_info'], $data);
                    $error = array();

                    foreach ($data[1] as $k => $td) {
                        if ($k % 12 == 0) {
                            $updateCmdb = ExtCmdbApi::getInstance()->udpateOfflineInfo(
                                $data[1][$k], $data[1][$k + 9], $data[1][$k + 10], $data[1][$k + 11],
                                json_encode(array('pms_id' => $id, 'type' => 'pms')));
                            if ($updateCmdb === false) {
                                $error[] = "服务器SN:{$data[1][$k]},更新CMDB信息时,接口调用失败!";
                            } elseif ($updateCmdb['code'] != 200) {
                                $error[] = "服务器SN:{$data[1][$k]},更新CMDB信息时,{$updateCmdb['msg']}!";
                            }
                        }
                    }

                    if (empty($error)) {
                        $remarks = "以上服务器信息,更新CMDB全部成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "更新CMDB信息时:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e2';
                        $add = '0';
                    }

                    break;
                case 'migrateOffline':
                    $preg2 = "/<td.*?>(.*?)<\/td>/";
                    preg_match_all($preg2, $infoArr['t_offline_info'], $data);

                    $error = array();
                    foreach ($data[1] as $k => $td) {
                        if ($k % 11 == 0) {
                            $updateCmdb = ExtCmdbApi::getInstance()->udpateMigrateOffline($data[1][$k],
                                json_encode(array('pms_id' => $id, 'type' => 'pms')));
                            if ($updateCmdb === false) {
                                $error[] = "服务器SN:{$data[1][$k]},更新CMDB信息时,接口调用失败!";
                            } elseif ($updateCmdb['code'] != 200) {
                                $error[] = "服务器SN:{$data[1][$k]},更新CMDB信息时,{$updateCmdb['msg']}!";
                            }
                        }
                    }

                    if (empty($error)) {
                        $remarks = "以上服务器信息,更新CMDB全部成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "更新CMDB信息时:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e2';
                        $add = '0';
                    }

                    break;
                case 'migrateOnline':
                    $href = array();
                    preg_match_all('/<a .*?href=\"(.*?)\".*?>/is', $infoArr['t_excel_file'], $href);

                    $baseDir = APPLICATION_PATH . '/public';
                    $fileDir = $baseDir . $href[1][0];

                    $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
                    $PHPExcel = $reader->load($fileDir); // 文档名称

                    $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表(编号从 0 开始)

                    $row = $sheet->getHighestRow(); // 取得总行数
                    $column = $sheet->getHighestColumn(); // 取得总列数

                    $error = array();
                    for ($r = 2; $r <= $row; $r++) {
                        $cArr = array();
                        for ($c = 0; $c <= ord($column) - 65; $c++) {
                            $cArr[] = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
                        }

                        $addRes = ExtCmdbApi::getInstance()->udpateMigrateOnline($cArr[0],$cArr[9],$cArr[10],$cArr[12],
                            $cArr[13],$cArr[14], $cArr[15], json_encode(array('pms_id' => $id, 'type' => 'pms')));
                        if ($addRes !== false) {
                            if ($addRes['code'] != 200) {
                                $error[] = "SN:{$cArr[0]},更新CMDB出错:{$addRes['msg']}!";
                            }
                        } else {
                            $error[] = "SN:{$cArr[0]},更新CMDB时API出错!";
                        }
                    }

                    if (empty($error)) {
                        $remarks = "以上服务器迁移上线信息,更新CMDB全部成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "迁移上线信息,更新CMDB时:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e3';
                        $add = '0';
                    }

                    break;

                case 'online':
                    //通过装机信息,获取服务器安装的操作系统,是否为ESXi
                    $href = array();
                    preg_match_all('/<a .*?href=\\"(.*?)\\".*?>/is', $infoArr['t_onlinedoc'], $href);

                    $baseDir = APPLICATION_PATH . '/public';
                    $fileDir = $baseDir . $href[1][0];

                    $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
                    $PHPExcel = $reader->load($fileDir); // 文档名称

                    $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表(编号从 0 开始)

                    $row = $sheet->getHighestRow(); // 取得总行数
                    $column = $sheet->getHighestColumn(); // 取得总列数

                    $systemArr = array();
                    for ($r = 2; $r <= $row; $r++) {
                        $cArr = array();
                        for ($c = 0; $c <= ord($column) - 65; $c++) {
                            $cArr[] = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
                        }
                        $systemArr[$cArr[0]] = stripos($cArr[4], 'esxi') === false ? 3 : 2;
                    }

                    $href = array();
                    preg_match_all('/<a .*?href=\\"(.*?)\\".*?>/is', $infoArr['t_deliverables_doc'], $href);

                    $baseDir = APPLICATION_PATH . '/public';
                    $fileDir = $baseDir . $href[1][0];

                    $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
                    $PHPExcel = $reader->load($fileDir); // 文档名称

                    $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表(编号从 0 开始)

                    $row = $sheet->getHighestRow(); // 取得总行数
                    $column = $sheet->getHighestColumn(); // 取得总列数

                    $error = array();
                    for ($r = 2; $r <= $row; $r++) {
                        $cArr = array();
                        for ($c = 0; $c <= ord($column) - 65; $c++) {
                            $cArr[] = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
                        }

                        $dataSources = empty($systemArr[$cArr[0]]) ? 3 : $systemArr[$cArr[0]];

                        $addRes = ExtCmdbApi::getInstance()->addOnlineInfo($cArr[0], $cArr[4], $cArr[5], $cArr[6],
                            $cArr[7], $cArr[8], $dataSources, json_encode(array('pms_id' => $id, 'type' => 'pms')));
                        if ($addRes !== false) {
                            if ($addRes['code'] != 200) {
                                $error[] = "SN:{$cArr[0]},添加到CMDB出{$addRes['msg']}!";
                            }
                        } else {
                            $error[] = "SN:{$cArr[0]},添加到CMDB时API出错!";
                        }

                    }
                    if (empty($error)) {
                        $remarks = "以上服务器信息,添加服务器信息CMDB全部成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "更新CMDB信息时:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e4';
                        $add = '0';
                    }
                    break;

                default:
                    break;
            }

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', $api, $exeId, $add);

        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('zhangyang7', $id, $api, $e->getMessage());
        }

    }

    /**
     * CMDB信息中IDC机房变更,撤机房,撤机柜
     * @param $id string
     * @return array
     */
    private function __cmdbIdcChange($id)
    {

        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $infoArr = json_decode($customInfo['info'], true);

        $idcJson = json_encode($infoArr['idc'], JSON_UNESCAPED_UNICODE);
        $cabinetJson = json_encode($infoArr['cabinet'], JSON_UNESCAPED_UNICODE);

        $changeRes = ExtCmdbApi::getInstance()->changeIdcinfo($idcJson, $cabinetJson,
            json_encode(array('pms_id' => $id, 'type' => 'pms')));
        if ($changeRes !== false) {
            if ($changeRes['code'] != 200) {
                $error[] = "更新机房机房信息时出错:{$changeRes['msg']}!";
            }
        } else {
            $error[] = "更新机房机房信息时API出错!";
        }

        if (empty($error)) {
            $remarks = "机房机柜信息,更新CMDB成功!";
            $exeId = '';
            $add = '';
        } else {
            $remarks = "更新CMDB信息时:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
            $exeId = 'e2';
            $add = '0';
        }

        BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', 'api_ext_cmdb_idc_change', $exeId, $add);

    }

    /**
     * CMDB系统网络设备变更
     * @param $id string
     * @param $type string
     * @param $api string
     */
    private function __cmdbSwitchChange($id, $type, $api)
    {

        try {
            if (!in_array($type, array('online', 'offline'))) {
                throw new Exception('输入参数错误:非法动作');
            }

            $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
            $dataArr = json_decode($customInfo['info'], true);

            $error = array();
            switch ($type) {
                case 'online':
                    foreach ($dataArr as $single) {
                        $data['sn'] = $single[0]['val'];
                        $data['idc_name'] = $single[1]['val'];
                        $data['idc_module'] = $single[2]['val'];
                        $data['cabinet_num'] = $single[3]['val'];
                        $data['u_bit'] = $single[4]['val'];
                        $data['ne_state'] = 1;

                        //检查是否已通过自动上报流程添加完毕
                        $checkSwitchInfo = ExtCmdbApi::getInstance()->getSwitchInfo($single[0]['val']);
                        
                        if ($single[5]['val'] == '是') {
                            //新增上线
                            if ($checkSwitchInfo !== false && $checkSwitchInfo['code'] == 400) {
                                throw new \Exception($checkSwitchInfo['data']['val']);
                            } elseif ($checkSwitchInfo['code'] == 200 && empty($checkSwitchInfo['data'])) {
                                //CMDB未检测到-新增
                                $addRes = ExtCmdbApi::getInstance()->addSwitchOnlineInfo($data, json_encode(['pms_id' => $id, 'type' => 'pms']));

                                if ($addRes !== false) {
                                    if ($addRes['code'] != 200) {
                                        $error[] = "【SN: {$data['sn']}】{$addRes['msg']}<br>";
                                    }
                                } else {
                                    $error[] = "【SN: {$data['sn']}】API出错!<br>";
                                }
                            } else {
                                //CMDB检测到-提示已添加
                                $error[] = "【SN: {$data['sn']}】该网络设备已由自动上报脚本添加,请核实!<br>";
                            }
                            
                        } else {
                            //闲置上线
                            if ($checkSwitchInfo !== false && $checkSwitchInfo['code'] == 400) {
                                throw new \Exception($checkSwitchInfo['data']['val']);
                            } elseif ($checkSwitchInfo['code'] == 200 && $checkSwitchInfo['data']['state'] == '闲置') {
                                //CMDB检测到闲置-上线
                                $addRes = ExtCmdbApi::getInstance()->updateSwitchInfo($data, json_encode(['pms_id' => $id, 'type' => 'pms']));

                                if ($addRes !== false) {
                                    if ($addRes['code'] != 200) {
                                        $error[] = "【SN: {$data['sn']}】{$addRes['msg']}<br>";
                                    }
                                } else {
                                    $error[] = "【SN: {$data['sn']}】API出错!<br>";
                                }
                            } else {
                                //CMDB检测到上线-提示已上线
                                $error[] = "【SN: {$data['sn']}】该网络设备已由自动上报脚本更新,请核实!<br>";
                            }
                        }
                        unset($data);
                    }

                    if (empty($error)) {
                        $remarks = "网络设备批量上线录入CMDB成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "网络设备批量上线录入CMDB时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e3';
                        $add = '0';
                    }
                break;

                case 'offline':
                    foreach ($dataArr as $single) {
                        $data['sn'] = $single[0]['val'];
                        $data['idc_name'] = $single[6]['val'];
                        $data['idc_module'] = $single[7]['val'];
                        $data['cabinet_num'] = $single[8]['val'];
                        $data['u_bit'] = '';
                        $data['ne_state'] = 5;

                        //调用CMDB接口
                        $addRes = ExtCmdbApi::getInstance()->updateSwitchInfo($data, json_encode(['pms_id' => $id, 'type' => 'pms']));

                        if ($addRes !== false) {
                            if ($addRes['code'] != 200) {
                                $error[] = "【SN: {$data['sn']}】{$addRes['msg']}<br>";
                            }
                        } else {
                            $error[] = "【SN: {$data['sn']}】API出错!<br>";
                        }
                        unset($data);
                    }

                    if (empty($error)) {
                        $remarks = "网络设备批量下线更新CMDB成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "网络设备批量下线更新CMDB时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e6';
                        $add = '0';
                    }
                    break;

                default:
                    break;
            }

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', $api, $exeId, $add);
            
        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('likx1', $id, $api, $e->getMessage());
        }
    }

    /**
     * Switch系统交换机关联关系变更
     * @param $id string
     * @param $type string
     * @param $api string
     */
    private function __switchRelationshipChange($id, $type, $api)
    {

        try {
            if (!in_array($type, array('online', 'offline'))) {
                throw new Exception('输入参数错误:非法动作');
            }

            $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
            $dataArr = json_decode($customInfo['info'], true);

            $error = array();
            switch ($type) {
                case 'online':
                    break;

                case 'offline':
                    foreach ($dataArr as $single) {
                        $post['sn'] = $single[0]['val'];
                        $post['host'] = $single[1]['val'];
                        //调用Switch接口
                        $delRes = ExtSwitchApi::getInstance()->delSwitchRelationshipInfo($post);

                        if ($delRes !== false) {
                            if ($delRes['code'] != 200) {
                                $error[] = "{$delRes['msg']}! 【SN: " . $post['sn'] . "】<br>";
                            }
                        } else {
                            $error[] = "API出错! 【" . $post['sn'] . "】<br>";
                        }
                        unset($data);
                    }

                    if (empty($error)) {
                        $remarks = "网络设备批量下线删除交换机互联关系成功!";
                        $exeId = '';
                        $add = '';
                    } else {
                        $remarks = "网络设备批量下线删除交换机互联关系时出错:<br>" . json_encode($error, JSON_UNESCAPED_UNICODE);
                        $exeId = 'e3';
                        $add = '0';
                    }
                    break;

                default:
                    break;
            }

            BusIssueServer::getInstance()->exeServer($id, $remarks, '', 'api', $api, $exeId, $add);

        } catch (\Exception $e) {
            $this->__sendExtApiErrorMsg('likx1', $id, $api, $e->getMessage());
        }
    }

    /**
     * 将连续IP字符串转换成数组
     * @param $ipString string
     * @return array
     */
    private function __checkIp($ipString)
    {

        $ipStrArr = explode("\n", $ipString);

        $ipData = array();
        $res['status'] = true;
        $res['msg'] = 'IP格式正确';

        if (!empty($ipStrArr)) {

            foreach ($ipStrArr as $ipStr) {
                $ipNum = array();
                $ipArr = explode('.', $ipStr);
                $ipNum[0] = array((int)$ipArr[0]);

                for ($x = 1; $x <= 3; $x++) {
                    if (strstr($ipArr[$x], '-') !== false) {

                        $ipSArr = explode('-', $ipArr[$x]);
                        $sIp = substr($ipSArr[0], 1);
                        $eIp = substr($ipSArr[1], 0, -1);

                        for ($i = $sIp; $i <= $eIp; $i++) {
                            $ipNum[$x][] = (int)$i;
                        }

                    } else {
                        $ipNum[$x] = array((int)$ipArr[$x]);
                    }
                }

                foreach ($ipNum[0] as $ip0) {
                    foreach ($ipNum[1] as $ip1) {
                        foreach ($ipNum[2] as $ip2) {
                            foreach ($ipNum[3] as $ip3) {
                                $ipData[] = $ip0 . '.' . $ip1 . '.' . $ip2 . '.' . $ip3;
                            }
                        }
                    }
                }
            }

            $res['data'] = array_unique($ipData);

        } else {
            $res['status'] = false;
            $res['msg'] = '输入参数错误:IP地址为空';
        }

        return $res;
    }


    /**
     * 获取提案详细数据
     * @param $id string
     * @return array
     */
    private function __getServerInfo($id)
    {
        $sql = 'SELECT
                        `List`.`id`,
                        `List`.`info_id`,
                        `Info`.`infoJson`
                    FROM
                        `IssueServer` AS `List`
                    JOIN `IssueServerInfo` AS `Info` ON (`Info`.`id` = `List`.`info_id`)
                    WHERE `List`.`id` = :id';

        $serverMysqlInfoList = MysqlIssueServer::getInstance()->querySQL($sql, array('id' => $id));

        $infoArr = json_decode($serverMysqlInfoList[0]['infoJson'], true);

        return $infoArr;
    }

    /**
     * 发送报警通知邮件
     * @param $uid string
     * @param $id string
     * @param $api string
     * @param $err string
     * @return array
     */
    private function __sendExtApiErrorMsg($uid, $id, $api, $err)
    {
        $cmd = "{$this->_php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendExtApiErrorEmail/uid/{$uid}/id/{$id}/api/{$api}/err/{$err}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

}
