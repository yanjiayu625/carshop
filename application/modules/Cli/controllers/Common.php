<?php

use \Business\Issue\ServerModel as BusIssueServer;

/**
 * 常用功能,
 */
class CommonController extends \Base\Controller_AbstractCli
{
    private $_php = '';

    /**
     * 根据result和status判断提案进行
     */
    public function createFinishedJumpboxAction()
    {
        $param = $this->getRequest()->getParams();
        if (isset($param['user'])) {
            $user = $param['user'];

            $serverType = 'steppingstones';
            $userInfo = BusIssueServer::getInstance()->getCreateIssueUserInfo($serverType, $user);

            $serverInfo['i_type'] = 'steppingstones';
            $serverInfo['i_name'] = $userInfo['tree'];

            $serverInfo['i_urgent'] = '1';
            $serverInfo['i_risk'] = '0';
            $serverInfo['i_countersigned'] = '';
            $serverInfo['i_applicant']      = $userInfo['name'];
            $serverInfo['i_email']          = $user.'@ifeng.com';
            $serverInfo['i_department']     = $userInfo['depart'];
            $serverInfo['i_leader'] = '';
            $serverInfo['i_title'] = "【申请跳板机】";

            $serverInfo['t_steppingstones'] = 1;

            $createRes = BusIssueServer::getInstance()->createFinish($serverInfo, 'api', $user, 'pms');

            if(!$createRes['status']){
                $this->__sendErrorMsg("系统为{$user}自动创建的申请跳板机提案失败,请检查");
            }

        } else {
            $this->__sendErrorMsg("系统自动创建的申请跳板机提案失败,缺少用户域账号信息,请检查");
        }
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
