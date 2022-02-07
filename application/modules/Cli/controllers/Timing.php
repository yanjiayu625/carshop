<?php

use \Mysql\Issue\TimingModel       as MysqlIssueTiming;
use \Mysql\Issue\ReloadModel       as MysqlIssueReload;
use \ExtInterface\Ansible\ApiModel as ExtAnsibleApi;
use \ExtInterface\IdcOs\ApiModel   as ExtIdcOsApi;
use \ExtInterface\Sw\ApiModel      as ExtSwitchApi;

use \Business\Issue\ServerModel    as BusIssueServer;

/**
 * 每分钟执行一次,检查是否有外部脚本执行完成
 */
class TimingController extends \Base\Controller_AbstractCli
{
    /**
     * 根据IssueTimingScript表中数据检测外系统脚本执行情况
     */
    public function checkExtApiAction()
    {
        $issueList = MysqlIssueTiming::getInstance()->getTimingList(
            array('server_id', 'api', 'result', 'email'), array('status' => 0));

        foreach ($issueList as $issue) {

            switch ($issue['api']) {
                case 'api_ext_yumInstall':
                    $this->__yumInstall($issue['server_id'], $issue['result'], $issue['email']);
                    break;
                case 'api_ext_pushKey':
                    $this->__pushKey($issue['server_id'], $issue['result'], $issue['email']);
                    break;
                case 'api_ext_checkStatus':
                    $this->__checkInstallStatus($issue['server_id'], $issue['result'], $issue['email']);
                    break;
                case 'api_ext_pushSshKey':
                    $this->__pushSshKey($issue['server_id'], $issue['result'], $issue['email']);
                    break;
                default:
                    break;
            }

        }
    }

    /**
     * Yum源安装软件
     * @param  $id string
     * @param  $result string
     * @param  $email string
     */
    private function __yumInstall($id, $result, $email)
    {
        $resArr = json_decode($result, true);

        $success = 0;
        foreach ($resArr as $sv => $res) {

            switch ($res['state']) {
                case 'PENDING':

                    $uid = 'zhaosy,lisai';
                    $api = 'http://ansible.ifengidc.com/result/';
                    $taskInfo = ExtAnsibleApi::getInstance()->getTaskInfo($res['task-id']);

                    if ($taskInfo['status']) {
                        if ($taskInfo['data']['state'] == 'SUCCESS') {
                            if ($taskInfo['data']['result']['status'] &&
                                empty($taskInfo['data']['result']['errors']) &&
                                empty($taskInfo['data']['result']['failed'])
                            ) {
                                $resArr[$sv]['state'] = 'SUCCESS';
                            } else {
                                $resArr[$sv]['state'] = 'FAILED';

                                $timeInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('email', 'c_time'),
                                    array('server_id' => $id, 'api' => 'api_ext_yumInstall'));
                                if ($timeInfo['email'] == $timeInfo['c_time'] ||
                                    time() - strtotime($timeInfo['email']) >= 1800
                                ) {
                                    $this->__sendEmail($uid, $id, $api . $res['task-id'], '该接口任务执行错误');
                                }
                            }
                            $set['result'] = json_encode($resArr);
                            MysqlIssueTiming::getInstance()->updateTimingInfo($set, array('server_id' => $id));
                        }
                        if ($taskInfo['data']['state'] == 'PENDING') {
                            if (time() - strtotime($email) >= 600) {
                                $this->__sendEmail($uid, $id, $api . $resArr['task-id'], '该接口任务执行时间超过10分钟');
                            }
                        }
                    } else {
                        $this->__sendExtApiErrorMsg($uid, $id, urlencode($api . $resArr['task-id']), urlencode($taskInfo['data']));
                    }

                    break;
                case 'SUCCESS':

                    $success++;

                    break;
                case 'FAILED':

                    $this->__reloadApi($id, 'api_ext_yumInstall');
                    break;
                default:

                    break;
            }

        }

        if ($success == count($resArr)) {

            $set['status'] = 1;
            MysqlIssueTiming::getInstance()->update($set, array('server_id' => $id, 'api' => 'api_ext_yumInstall'));

            $msg =  'Key推送完成, 结果:全部成功';
            BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_yumInstall', '', '');
        }

    }

    /**
     * 推送KEY
     * @param  $id string
     * @param  $result string
     * @param  $email string
     */
    private function __pushKey($id, $result, $email)
    {
        $resArr = json_decode($result, true);

        switch ($resArr['state']) {
            case 'PENDING':

                $uid = 'zhaosy,lisai';
                $api = 'http://ansible.ifengidc.com/result/';
                $taskInfo = ExtAnsibleApi::getInstance()->getTaskInfo($resArr['task-id']);

                if ($taskInfo['status']) {
                    if ($taskInfo['data']['state'] == 'SUCCESS' ) {
                        if( $taskInfo['data']['result']['status'] &&
                            empty($taskInfo['data']['result']['errors']) &&
                            empty($taskInfo['data']['result']['failed'])
                        ){
                            $resArr['state'] = 'SUCCESS';
                        }else{
                            $resArr['state'] = 'FAILED';

                            $timeInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('email','c_time'),
                                array('server_id'=>$id,'api'=>'api_ext_pushKey'));
                            if($timeInfo['email'] == $timeInfo['c_time'] ||
                                time()-strtotime($timeInfo['email']) >= 1800
                            ){
                                $this->__sendEmail($uid,$id,$api.$resArr['task-id'],'该接口任务执行错误');
                            }
                        }
                        $set['result'] = json_encode($resArr);
                        MysqlIssueTiming::getInstance()->updateTimingInfo($set, array('server_id' => $id));
                    }
                    if ($taskInfo['data']['state'] == 'PENDING'){
                        if(time()-strtotime($email) >= 600){
                            $this->__sendEmail($uid,$id,$api.$resArr['task-id'],'该接口任务执行时间超过10分钟');
                        }
                    }
                }else{
                    $this->__sendExtApiErrorMsg($uid,$id,urlencode($api.$resArr['task-id']),urlencode($taskInfo['data']));
                }

                break;
            case 'SUCCESS':

                $set['status'] = 1;
                MysqlIssueTiming::getInstance()->update($set, array('server_id' => $id));

                $msg =  'Key推送完成, 结果:全部成功';
                BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_pushKey', '', '');

                break;
            case 'FAILED':
                $timeInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('num'), array('server_id'=>$id,'api'=>'api_ext_pushKey'));
                if((int)$timeInfo['num'] < 10){

                    $num = (int)$timeInfo['num'] + 1;
                    MysqlIssueTiming::getInstance()->updateTimingInfo(array('num'=>$num), array('server_id' => $id,'api'=>'api_ext_pushKey'));
                    $this->__reloadApi($id,'api_ext_pushSshKey');
                }else{
                    $taskInfo = ExtAnsibleApi::getInstance()->getTaskInfo($resArr['task-id']);

		    $msgArr = array();
                    if(!empty($taskInfo['data']['result']['msg'])){
                        $msgArr[] = 'Msg:'.json_encode($taskInfo['data']['result']['msg'],JSON_UNESCAPED_UNICODE);
                    }
                    if(!empty($taskInfo['data']['result']['errors'])){
                        $msgArr[] = 'Errors:'.json_encode($taskInfo['data']['result']['errors'],JSON_UNESCAPED_UNICODE);
                    }
                    if(!empty($taskInfo['data']['result']['failed'])){
                        $msgArr[] = 'Failed:'.json_encode($taskInfo['data']['result']['failed'],JSON_UNESCAPED_UNICODE);
                    }
                    $msg =  implode('<br>',$msgArr);

                    $set['status'] = 1;
                    MysqlIssueTiming::getInstance()->update($set, array('server_id' => $id));

                    BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_pushKey', '', '');
                }
                break;
            default:

                break;
        }

    }

    /**
     * 检测重装服务器操作系统建成
     * @param  $id string
     * @param  $result string
     * @param  $email string
     */
    private function __checkInstallStatus($id, $result, $email)
    {

        $resArr = json_decode($result, true);

        $token = ExtIdcOsApi::getInstance()->getToken('zhangyang7','Jx3tVm$C372s2xxkjJ3=');
        ExtIdcOsApi::getInstance()->setToken($token);


        switch ($resArr['state']) {
            case 'PENDING':

                $query['status'] = 2;
                $query['id'] = $resArr['install_id'];
                $devices = ExtIdcOsApi::getInstance()->getDeviceList($query);

                if($devices !== false ){
                    $resArr['state'] = 'SUCCESS';

                    $set['result'] = json_encode($resArr);
                    MysqlIssueTiming::getInstance()->updateTimingInfo($set, array('server_id' => $id));

                }else{
                    $timeInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('email','c_time'),
                        array('server_id'=>$id,'api'=>'api_ext_checkStatus'));
                    if($timeInfo['email'] == $timeInfo['c_time'] ||
                        time()-strtotime($timeInfo['email']) >= 3600
                    ){
                        $this->__sendEmail('zhangyang7',$id,'IdcOs系统接口,ID:'.$resArr['install_id'],'系统装机时间过长');
                    }
                }

                break;
            case 'SUCCESS':

                $set['status'] = 1;
                MysqlIssueTiming::getInstance()->update($set, array('server_id' => $id));

                $delRes = $this->__delMacFormDhcp($id);
                if($delRes){
                    $msg =  '系统已安装成功,并删除DHCP配置文件成功';
                }else{
                    $msg =  '系统已安装成功,但删除DHCP配置文件失败,已通知管理员';
                    $this->__sendExtApiErrorMsg('zhangyang7',
                        $id,urlencode("http://switch.ifos.ifengidc.com:8081/Api/Control/setMacToDhcp/id/{$id}"),
                        urlencode('在DHCP配置文件中删除MAC信息出错,请速度处理!'));
                }

                BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_checkStatus', '', '');
                break;
            default:

                break;
        }

    }

    /**
     * 推送KEY
     * @param  $id string
     * @param  $result string
     * @param  $email string
     */
    private function __pushSshKey($id, $result, $email)
    {
        $resArr = json_decode($result, true);

        switch ($resArr['state']) {
            case 'PENDING':

                $uid = 'zhaosy,lisai';
                $api = 'http://ansible.ifengidc.com/result/';
                $taskInfo = ExtAnsibleApi::getInstance()->getTaskInfo($resArr['task-id']);

                if ($taskInfo['status']) {
                    if ($taskInfo['data']['state'] == 'SUCCESS' ) {
                        if( $taskInfo['data']['result']['status'] &&
                            empty($taskInfo['data']['result']['errors']) &&
                            empty($taskInfo['data']['result']['failed'])
                        ){
                            $resArr['state'] = 'SUCCESS';
                        }else{
                            $resArr['state'] = 'FAILED';

                            $timeInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('email','c_time'),
                                array('server_id'=>$id,'api'=>'api_ext_pushKey'));
                            if($timeInfo['email'] == $timeInfo['c_time'] ||
                                time()-strtotime($timeInfo['email']) >= 1800
                            ){
                                $this->__sendEmail($uid,$id,$api.$resArr['task-id'],'该接口任务执行错误');
                            }
                        }
                        $set['result'] = json_encode($resArr);
                        MysqlIssueTiming::getInstance()->updateTimingInfo($set, array('server_id' => $id));
                    }
                    if ($taskInfo['data']['state'] == 'PENDING'){
                        if(time()-strtotime($email) >= 600){
                            $this->__sendEmail($uid,$id,$api.$resArr['task-id'],'该接口任务执行时间超过10分钟');
                        }
                    }
                }else{
                    $this->__sendExtApiErrorMsg($uid,$id,urlencode($api.$resArr['task-id']),urlencode($taskInfo['data']));
                }

                break;
            case 'SUCCESS':

                $set['status'] = 1;
                MysqlIssueTiming::getInstance()->update($set, array('server_id' => $id));

                $msg =  'Key推送完成, 结果:全部成功';
                BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_pushSshKey', '', '');

                break;
            case 'FAILED':
                $timeInfo = MysqlIssueTiming::getInstance()->getOneTiming(array('num'), array('server_id'=>$id,'api'=>'api_ext_pushSshKey'));
                if((int)$timeInfo['num'] < 10){

                    $num = (int)$timeInfo['num'] + 1;
                    MysqlIssueTiming::getInstance()->updateTimingInfo(array('num'=>$num), array('server_id' => $id,'api'=>'api_ext_pushSshKey'));
                    $this->__reloadApi($id,'api_ext_pushSshKey');
                }else{
                    $taskInfo = ExtAnsibleApi::getInstance()->getTaskInfo($resArr['task-id']);

                    $msgArr = array();
                    if(!empty($taskInfo['data']['result']['msg'])){
                        $msgArr[] = 'Msg:'.json_encode($taskInfo['data']['result']['msg'],JSON_UNESCAPED_UNICODE);
                    }
                    if(!empty($taskInfo['data']['result']['errors'])){
                        $msgArr[] = 'Errors:'.json_encode($taskInfo['data']['result']['errors'],JSON_UNESCAPED_UNICODE);
                    }
                    if(!empty($taskInfo['data']['result']['failed'])){
                        $msgArr[] = 'Failed:'.json_encode($taskInfo['data']['result']['failed'],JSON_UNESCAPED_UNICODE);
                    }
                    $msg =  implode('<br>',$msgArr);

                    $set['status'] = 1;
                    MysqlIssueTiming::getInstance()->update($set, array('server_id' => $id,'api'=>'api_ext_pushSshKey'));

                    BusIssueServer::getInstance()->exeServer($id, $msg, '', 'api', 'api_ext_pushSshKey', '', '');
                }
                break;
            default:

                break;
        }

    }

    /**
     * 重启
     */
    public function checkReloadApiAction()
    {
        $issueList = MysqlIssueReload::getInstance()->getReloadList(
            array('server_id', 'api','interval','u_time'), array('status' => 0));

        foreach ($issueList as $issue) {
            if(time()-strtotime($issue['u_time']) >= 60 * $issue['interval']){
                $this->__reloadApi($issue['server_id'],$issue['api']);
                MysqlIssueReload::getInstance()->updateReloadInfo(array('u_time'=>date('Y-m-d H:i:s')),
                    array('server_id'=>$issue['server_id']));
            }
        }
    }

    /**
     * 重新执行API
     * @param $id string
     * @param $api string
     * @return array
     */
    private function __reloadApi($id, $api)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/extapi/setApi/id/{$id}/api/{$api}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    //发送错误邮件
    private function __sendEmail($uid,$id,$api,$err){
        $this->__sendExtApiErrorMsg($uid,$id,urlencode($api),urlencode($err));

        MysqlIssueTiming::getInstance()->updateTimingInfo(array('email'=>date('Y-m-d H:i:s')),
            array('server_id' => $id));
    }

    //发送错误邮件
    private function __delMacFormDhcp($id){
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
        $dhcp['type'] = 'del';
        $apiRes = ExtSwitchApi::getInstance()->setMacToDhcp($dhcp);

        return $apiRes;
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
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendExtApiErrorEmail/uid/{$uid}/id/{$id}/api/{$api}/err/{$err}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }


}
