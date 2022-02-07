<?php

use \Base\Log as Log;
use \Tools\Tools as Tools;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\NodeModel as MysqlIssueNode;
use \Mysql\Issue\FocusServerModel as MysalIssueFocus;
use \Mysql\Issue\MobileModel as MysalIssueMobile;
use \Mysql\Issue\EmailCheckModel as MysalIssueEmailCheck;
use \DAO\Issue\ConfigModel as DAOIssueConfig;
use \ExtInterface\Msg\ApiModel as ExtMsgApi;
use \ExtInterface\Msg\WechatModel as ExtWechat;
use \ExtInterface\IdcMc\ApiModel as ExtIdcMcApi;
use \Mysql\Issue\VsanInfoModel as MysqlIssueVsanInfo;
use \Mysql\Issue\UserModel as MysqlIssueUserInfo;
use \Business\Issue\ServerModel as BusIssueServer;
use \Mysql\Issue\RenewModel as MysqlIssueRenew;
use \Mysql\Issue\CommonModel as MysqlCommon;

/**
 * Issue事务跟踪系统APP端接口
 */
class EmailController extends \Base\Controller_AbstractCli
{

    private $_emailTplInfo = '';
    private $_serverInfo = '';

    public function sendMsgEmailAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id']) && isset($param['user']) && isset($param['action'])) {

            $id = $param['id'];
            $user = $param['user'];
            $action = $param['action'];

            $host = \Yaf\Registry::get('config')->get('email.host');
            $this->_serverInfo = MysqlIssueServer::getInstance()->getOneServer(null, array('id' => $id));

            $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('role_id'),
                array('server_id' => $id, 'operator' => $user, 'action' => $action, 'status' => 0));

            //发件人
            $userInfo = MysqlIssueUserInfo::getInstance()->getOneUserInfo(array('cname'), array('user' => $user));
            $tos[] = array('address' => "{$user}@ifeng.com", 'name' => !empty($userInfo['cname']) ? $userInfo['cname'] : $user);

            $attachs = null; //邮件附件,默认为null

            switch ($action) {
                case 'check':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} ,需要您审批,谢谢";
                    $url = "/login/auto?type=approve&id=";
                    break;
                case 'receive':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} ,需要您领取,谢谢";
                    $url = "/login/auto?type=receive&id=";
                    break;
                case 'appoint':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} ,需要您指派执行人,谢谢";
                    $url = "/login/auto?type=appoint&id=";
                    break;
                case 'exe':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} ,需要您执行,谢谢";
                    $url = "/login/auto?type=exe&role={$nodeInfo['role_id']}&id=";
                    break;
                case 'finish':
                    $informerList = $this->__getInformerArray($id);
                    if (!empty($informerList)) {
                        foreach ($informerList as $informer) {
                            if (!in_array(array('address' => $informer['email'], 'name' => $informer['cname']), $tos)) {
                                $tos[] = array('address' => $informer['email'], 'name' => $informer['cname']);
                            }
                        }
                    }
                    if ($this->_serverInfo['i_type'] == 'token') {
                        $fileDir = APPLICATION_PATH . '/public/download/IfengToken-V1.0.docx';
                        $attachs = array($fileDir);
                        $this->sendSms($user);
                    }
                    $subject = "IFOS系统提示:服务完成,{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} 已执行完成,特此通知您,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'inform':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} 已执行完成,特此通知您,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'renew':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} 服务即将到期,特此通知您,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'renewExe':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id}  服务续约,需要您执行,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'renewInform':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id}  服务续约,已执行完成,特此通知您,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'reject':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} 已被驳回,请注意,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'stop':
                    $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】ID:{$id} 已被终止,请注意,谢谢";
                    $url = "/login/auto?type=detail&id=";
                    break;
                case 'renewServerPermission':
                    $subject = "重要!!! 您已经连续6个月未登陆以下服务器，是否续订";
                    $url = "/Operate/renewServerPermission?id=";
                    break;
                default:
                    echo '参数错误!';
                    die;
            }

            $url = $host . $url . $id;

            $this->_emailTplInfo = file_get_contents(TPL_EMAIL_ISSUE);

            $this->_setServerInfo($action, $user);
            $this->_setLinkInfo($url);

            $mail = new \SendMail\MailModel;

            $from = 'pms@ifeng.com';
            $from_name = 'PMS系统管理员';

            $WechatRes = $this->sendWechat($user, $subject, $url);
            $wmsg = "提案号{$id}发送Wechat状态:" . (string)$WechatRes;
            Log::getInstance('msg')->write($wmsg);

            //APP 推送消息
            if ($action == 'check') {
                $this->__appPush($id, $subject, $user);
            }

            $mailRes = $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo, $attachs);
            $msg = "提案号{$id}发送邮件状态:" . (string)$mailRes;
            Log::getInstance('msg')->write($msg);

        } else {
            echo '参数错误';
            die;
        }

    }

    /**
     * APP端信息推送
     * @param $id string
     * @param $user string
     * @param $subject string
     * @return array
     */
    private function __appPush($id, $subject, $user)
    {

        $postData = array(
            "title" => "【PMS系统】 {$subject}",
            "msg" => $subject,
            "user_id" => $user,
            "extras" => array('mission_id' => $id, 'to' => 'pmsIssueDetail'),
            "type" => 'pms',
        );

        ExtIdcMcApi::getInstance()->pushAppMsg($postData);
    }

    /**
     * 多种类型提案 动态提醒
     * @param $type string 提案类型
     */
    private function __checkFocus($type)
    {
        $return = [];

        $focusTypes = [
            //技术部-运维中心-IDC变更
            'batchonline',
            'batchoffline',
            'batchmigrate',
            'serverfailures',
            'serverosrefresh',
            'firmwarechanges',
            'idcchange',
            'batchofflinevirtualserver',
            'batchonlineswitch',
            'batchofflineswitch',
            'virtualmachinechanges',
            'batchofflinestoragedevice',

            //技术部-运维中心-域名相关
            'ifengidccom',

            //技术部-运维中心-基础架构变更
            'serveronline',
            'serveroffline',
            'servermigrate',

            //技术部-运维中心-容器资源申请
            'kubernetes',
            'kubernetesmonitor',

            //技术部-运维中心-系统运维相关
            'batchexe',

            //(变更)技术部-运维中心-网络相关
            'datacenter',
        ];

        if (in_array($type, $focusTypes)) {
            $return[] = ['focus'=>'wangshuo7'];
            // $return[] = ['focus'=>'likx1'];
        }

        return $return;
    }

    /**
     * 发送关注邮件
     * @param $id string
     * @param $user string
     * @param $action string
     * @return array
     */
    public function sendFocusEmailAction($id, $user, $action)
    {
        $host = \Yaf\Registry::get('config')->get('email.host');

        $actionMsg = array('create' => '创建', 'check' => '审批', 'receive' => '领取并执行', 'appoint' => '指派',
            'transfer' => '移交', 'exe' => '执行完成', 'finish' => '关闭', 'reopen' => '重开');

        $this->_serverInfo = MysqlIssueServer::getInstance()->getOneServer(null, array('id' => $id));

        $foucsList = MysalIssueFocus::getInstance()->getFocusServerListByFieldAndWhere(array('focus'), array('server_id' => $id));

        $checkFocus = $this->__checkFocus($this->_serverInfo['i_type']);
        $foucsList = array_merge($foucsList, $checkFocus);

        if (!empty($foucsList)) {
            $tos = array();
            foreach ($foucsList as $focus) {
                $tos[] = array('address' => "{$focus['focus']}@ifeng.com", 'name' => $focus['focus']);
            }

            $subject = "IFOS系统提示:您关注的{$this->_serverInfo['i_applicant']}申请的[{$this->_serverInfo['i_title']}]的提案发生变化,{$user}{$actionMsg[$action]}了该提案,请查看详情!";

            $this->_emailTplInfo = file_get_contents(TPL_EMAIL_ISSUE);

            $url = "{$host}/issue/server/detail?id={$id}";
            $this->_setFocusLinkInfo($url);
            $this->_setServerInfo($action, $user);

            $from = 'pms@ifeng.com';
            $from_name = 'PMS系统管理员';

            $mail = new \SendMail\MailModel;
            $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo);
        }

    }


    /**
     * 发送交接邮件
     * @param $id string  php cli/cli.php request_uri='/cli/Email/sendTransferEmail/id/'
     * @return array
     */
    public function sendTransferEmailAction($id)
    {
        $host = \Yaf\Registry::get('config')->get('email.host');

        $this->_serverInfo = MysqlIssueServer::getInstance()->getOneServer(null, array('id' => $id));

        switch ($this->_serverInfo['i_type']) {
            case 'applynat':
            case 'syqapplynat':
                $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】, 因{$this->_serverInfo['i_applicant']}已离职,需要转移所属人,请您操作,谢谢";
                $tos = array('address' => 'sre@ifeng.com', 'name' => 'SRE组');
                break;
            case 'applynetworkaccess':
                $subject = "IFOS系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】, 因{$this->_serverInfo['i_applicant']}已离职,需要转移所属人,请您操作,谢谢";
                $tos = array('address' => 'sre@ifeng.com', 'name' => 'SRE组');
                break;
            default:
                echo '参数错误!';
                die;
        }

        $this->_emailTplInfo = file_get_contents(TPL_EMAIL_ISSUE);

        $html = "<tr><td> 请注意: </td> <td class='left'><font color='#EA0000' size='5'>因申请人离职,请将该权限转移所属人!!</font></td></tr>" .
            "<tr><td> 提案ID: </td> <td class='left'>{$this->_serverInfo['id']}</td></tr>" .
            "<tr><td> 提案标题: </td> <td class='left'>{$this->_serverInfo['i_title']}</td></tr>" .
            "<tr><td> 提案类型: </td> <td class='left'>{$this->_serverInfo['i_name']}</td></tr>" .
            "<tr><td> 申请人: </td> <td class='left'>{$this->_serverInfo['i_applicant']}</td></tr>" .
            "<tr><td> 申请人部门: </td> <td class='left'>{$this->_serverInfo['i_department']}</td></tr>" .
            "<tr><td> 申请时间: </td> <td class='left'>{$this->_serverInfo['c_time']}</td></tr>";


        $infoArr = $this->createServerInfo($this->_serverInfo['id']);
        $infoHtml = "<tr height='30'><td colspan='2'>详细内容:</td></tr>";
        foreach ($infoArr as $key => $info) {
            if ($this->_serverInfo['i_type'] == 'token' && $key == 't_specialremarks') {
                $infoHtml .= "<tr><td>{$info['label']}: </td> <td class='left' >"
                    . "<font color='#ff4500'>{$info['value']}</font></td></tr>";
            } else {
                $infoHtml .= "<tr><td>{$info['label']}: </td> <td class='left' >{$info['value']}</td></tr>";
            }
        }

        $html .= $infoHtml;

        switch ($this->_serverInfo['i_type']) {
            case 'applynat':
            case 'syqapplynat':
                $renewHtml = "<tr height='30'><td>交接操作: </td> <td class='left' >"
                    . "<a href='{$host}/Operate/nattransfer?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>点击处理</a></td></tr>";
                break;
            case 'applynetworkaccess':
                $renewHtml = "<tr height='30'><td>交接操作: </td> <td class='left' >"
                    . "<a href='{$host}/Operate/networkaccesstransfer?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>点击处理</a></td></tr>";
                break;
            default:
                $renewHtml = "";
                break;
        }
        $html .= $renewHtml;

        $this->replaceTemplate('ServerInfo', $html);
        $url = "{$host}/issue/server/detail?id={$id}";
        $this->_setLinkInfo($url);
        $this->replaceTemplate('EmailCheck', '');

        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统管理员';

        $mail = new \SendMail\MailModel;
        $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo);

    }

    /**
     * 发送报错信息邮件
     * @param $msg string
     * @return array
     */
    public function sendErrorEmailAction($msg)
    {
        $subject = "IFOS系统提示:检测提案状态后台执行程序报错!";

        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统管理员';

        $mail = new \SendMail\MailModel;
        // $tos[] = array('address' => "zhangyang7@ifeng.com", 'name' => '张阳');
        $tos[] = array('address' => "likx1@ifeng.com", 'name' => '李可心');

        $mail->sendHtmlMail($subject, $tos, $from, $from_name, $msg);
        // $this->sendWechat('zhangyang7|likx1', $msg, '');
        $this->sendWechat('likx1', $msg, '');
    }

    /**
     * 发送报错信息邮件
     * @param $uid string
     * @param $id string
     * @param $api string
     * @param $err string
     * @return array
     */
    public function sendExtApiErrorEmailAction($uid, $id, $api, $err)
    {
        $host = \Yaf\Registry::get('config')->get('email.host');
        $subject = "IFOS系统提示:检测对外API接口执行程序报错! 提案号:{$id}";

        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统自动提醒';

        $mail = new \SendMail\MailModel;
        $uidArr = explode(',', $uid);

        foreach ($uidArr as $u) {
            $tos[] = array('address' => "{$u}@ifeng.com", 'name' => '');
        }
        if (!empty($tos)) {
            $msg = 'API接口地址:' . urldecode($api) . '<br>' . '错误信息:' . urldecode($err) . '<br>'
                . "关闭该提案的循环调用功能:{$host}/ExtApi/Issue_Common/closeIssue?id={$id}";
            $mail->sendHtmlMail($subject, $tos, $from, $from_name, $msg);
            foreach ($uidArr as $u) {
                $this->sendRtxMsg($u, $subject, '');
                $this->sendWechat($u, $subject, '');
            }
        }
    }

    /**
     * 发送Vsan信息有限
     * @param $user string
     * @param $type string
     */
    public function sendVsanEmailAction($user, $type)
    {
        $host = \Yaf\Registry::get('config')->get('email.host');

        $vsanList = MysqlIssueVsanInfo::getInstance()->getVsanList(array('server_id', 'uid', 'operator', 'expire', 'ip', 'delay'),
            array('uid' => $user));
        $this->_emailTplInfo = file_get_contents(TPL_EMAIL_ISSUE);

        if ($type == 'delay') {
            $subject = "PMS系统提示:{$user}的主机即将到期,请确认是否续约!";

            $operatorArr = array();
            $contentArr = array();
            $issueArr = array();
            foreach ($vsanList as $vsanInfo) {
                if ($vsanInfo['delay'] == '0') {
                    $day = (strtotime($vsanInfo['expire']) - strtotime(date('Y-m-d'))) / 86400;
                    if ($day <= 10 && $day > 0) {
                        $operatorArr[] = $vsanInfo['operator'];
                        $contentArr[] = "IP地址:{$vsanInfo['ip']}的主机还有 <em style='font-size: 20px;color: #ef270d;font-weight: bold;'>{$day}</em> 天到期";
                        if ($vsanInfo['server_id'] != '0') {
                            $issueArr[] = "<a href='{$host}/issue/server/detail?id={$vsanInfo['server_id']}' style='text-decoration : none;color: #2F0000'> {$vsanInfo['server_id']} </a>";
                        }
                    }
                }
            }

            if (empty($contentArr)) {
                die;
            } else {
                $contentStr = implode('<br>', $contentArr);
            }

            $issueStr = implode(' ', $issueArr);

            $url = $host . "/Operate/vsandelay";

            $msg = "<tr><td>主机所有人: </td><td>{$user}</td></tr>";
            $msg .= "<tr><td>相关提案: </td><td>{$issueStr}</td></tr>";
            $msg .= "<tr><td>提醒内容: </td><td>{$contentStr}</td></tr>";
            $msg .= "<tr><td>后续操作: </td><td><a href='{$url}' style='text-decoration : none;color: #00BB00'> 续约操作 </a></td></tr>";

            $rtx = $subject . "\n";
            $rtx .= '续约操作请点击:' . $url . "\n";

            $this->replaceTemplate('ServerInfo', $msg);
        }

        if ($type == 'poweroff') {

            $subject = "PMS系统提示:{$user}的主机已经关机,请确认是否续约!";
            $url = $host . "/Operate/vsandelay";

            $operatorArr = array();
            $contentArr = array();
            $issueArr = array();
            foreach ($vsanList as $vsanInfo) {
                $day = (strtotime($vsanInfo['expire']) - strtotime(date('Y-m-d'))) / 86400;
                if ($day == 0) {
                    $operatorArr[] = $vsanInfo['operator'];
                    $contentArr[] = "IP地址:{$vsanInfo['ip']}的主机已被<em style='color: #ef270d;font-weight: bold;'>关机</em>,请注意";
                    if ($vsanInfo['server_id'] != '0') {
                        $issueArr[] = "<a href='{$host}/issue/server/detail?id={$vsanInfo['server_id']}' style='text-decoration : none;color: #2F0000'> {$vsanInfo['server_id']} </a>";
                    }
                }
            }

            if (empty($contentArr)) {
                die;
            } else {
                $contentStr = implode('<br>', $contentArr);
            }
            $issueStr = implode(' ', $issueArr);

            $msg = "<tr><td>主机所有人: </td><td>{$user}</td></tr>";
            $msg .= "<tr><td>相关提案: </td><td>{$issueStr}</td></tr>";
            $msg .= "<tr><td>提醒内容: </td><td>{$contentStr}</td></tr>";
            $msg .= "<tr><td>后续操作: </td><td><a href='{$url}' style='text-decoration : none;color: #00BB00'> 续约操作 </a></td></tr>";

            $rtx = $subject . "\n";
            $rtx .= '续约操作请点击:' . $url;

            $this->replaceTemplate('ServerInfo', $msg);
        }

        if ($type == 'destroy') {

            $subject = "PMS系统提示:{$user}的主机已被销毁,谢谢";

            $operatorArr = array();
            $contentArr = array();
            foreach ($vsanList as $vsanInfo) {
                $day = (strtotime($vsanInfo['expire']) - strtotime(date('Y-m-d'))) / 86400;
                if ($day == -8) {
                    $operatorArr[] = $vsanInfo['operator'];
                    $contentArr[] = "IP地址:{$vsanInfo['ip']}的主机已被<em style='color: #ef270d;font-weight: bold;'>销毁</em>,请注意";
                }
            }

            if (empty($contentArr)) {
                die;
            } else {
                $contentStr = implode('<br>', $contentArr);
            }

            $msg = "<tr><td>主机所有人: </td><td>{$user}</td></tr>";
            $msg .= "<tr><td>提醒内容: </td><td>{$contentStr}</td></tr>";

            $rtx = $subject;

            $this->replaceTemplate('ServerInfo', $msg);
        }

        $this->replaceTemplate('LinkInfo', '');
        $this->replaceTemplate('EmailCheck', '');

        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统自动提醒';

        $mail = new \SendMail\MailModel;
        $userArr = array_unique(array_merge(array($user, 'yangguo'), $operatorArr));

        foreach ($userArr as $u) {
            $tos[] = array('address' => "{$u}@ifeng.com", 'name' => '');
        }
        if (!empty($tos)) {
            $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo);
            $this->sendWechat($user, $subject, $url);
        }
    }

    /**
     * 发送检测Vsan使用者
     * @param $uid string
     */
    public function sendCheckVsanUidCheckAction($uid)
    {

        $uidArr = explode('#', $uid);
        $vsanList = MysqlIssueVsanInfo::getInstance()->getVsanList(array('uid','vname', 'expire', 'ip'),
            array('uid' => $uidArr));

        $this->_emailTplInfo = file_get_contents(TPL_EMAIL_ISSUE);

        $subject = "PMS系统提示: 发现离职人员的虚机没有交接, 请注意,谢谢";

        $msg = '<tr><td>提醒内容: </td><td>';
        foreach ($vsanList as $vsanInfo) {
            $msg .= "域账号:{$vsanInfo['uid']} 虚机名:{$vsanInfo['vname']} IP:{$vsanInfo['ip']} 到期时间:{$vsanInfo['expire']} <br>";
        }
        $msg .= "</td></tr>";

        $this->replaceTemplate('ServerInfo', $msg);


        $this->replaceTemplate('LinkInfo', '');
        $this->replaceTemplate('EmailCheck', '');

        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统自动提醒';

        $mail = new \SendMail\MailModel;
        $userArr = array('yangguo', 'zhangyang7');

        foreach ($userArr as $u) {
            $tos[] = array('address' => "{$u}@ifeng.com", 'name' => '');
        }
        if (!empty($tos)) {
            $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo);
        }
    }

    /**
     * 获取运维负责人或业务负责人
     * @param $id string
     * @return array
     */
    private function __getInformerArray($id)
    {
        $userList = array();
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_type', 'info_id'), array('id' => $id));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(
            array('checkJson', 'infoJson'), array('id' => $server['info_id']));
        $checkArr = json_decode($serverInfo['checkJson'], true);
        $infoArr = json_decode($serverInfo['infoJson'], true);

//        if (!empty($checkArr['operator'])) {
//            $userInfo = MysqlIssueUserInfo::getInstance()->getOneUserInfo(
//                array('cname'), array('user' => $checkArr['operator']));
//            $userList[] = array('email' => $checkArr['operator'] . '@ifeng.com',
//                'cname' => !empty($userInfo['cname']) ? $userInfo['cname'] : $checkArr['operator']);
//        }

        if (!empty($infoArr['t_name_business'])) {
            $userInfo = MysqlIssueUserInfo::getInstance()->getOneUserInfo(
                array('cname'), array('user' => $infoArr['t_name_business']));
            $userList[] = array('email' => $infoArr['t_name_business'] . '@ifeng.com',
                'cname' => !empty($userInfo['cname']) ? $userInfo['cname'] : $infoArr['t_name_business']);
        }

        if ($server['i_type'] == 'yidianaccessifeng') {
            $userList[] = array('email' => 'zhouchen@yidian-inc.com', 'cname' => '周晨');
            $userList[] = array('email' => 'zhengzhidong@yidian-inc.com', 'cname' => '郑治东');
            $userList[] = array('email' => 'zhouyang@yidian-inc.com', 'cname' => '周阳');
        }

        if (in_array($server['i_type'], array('batchonline', 'batchoffline', 'batchmigrate'))) {
            $userList[] = array('email' => 'zhangjy@ifeng.com', 'cname' => '张军营');
            $userList[] = array('email' => 'jing.wang@ifeng.com', 'cname' => '王晶');
            $userList[] = array('email' => 'fanxb@ifeng.com', 'cname' => '范习斌');
        }

        if ($server['i_type'] == 'selfmedia') {
            $userList[] = array('email' => 'zhangxin11@ifeng.com', 'cname' => '张鑫');
            $userList[] = array('email' => 'weiyc@ifeng.com', 'cname' => '危云辰');
        }

        if (in_array($server['i_type'], ['msgwechat', 'msgmail', 'wechatoauth'])) {
            $userList[] = array('email' => 'bianxd@ifeng.com', 'cname' => '卞新栋');
        }

        return $userList;
    }

    /**
     * 发送Rtx信息
     * @param $user string
     * @param $sub string
     * @param $url string
     * @return array
     */
    private function sendRtxMsg($user, $sub, $url)
    {
        if ($this->_loginToken !== '') {
            $url = $url . '&token=' . $this->_loginToken;
        }
        $userInfo = MysalIssueMobile::getInstance()->getOneMobileByWhere(array('user' => $user));
        if (!empty($userInfo['cname'])) {
            $rtx['account'] = $userInfo['cname'];
            $rtx['title'] = 'PMS系统消息通知';
            $rtx['content'] = $sub . '.' . "\n" . '详情请点击:' . $url;
            return ExtMsgApi::getInstance()->sendRtx($rtx);
        } else {
            return false;
        }
    }

    /**
     * 发送Rtx信息
     * @param $user string
     * @param $sub string
     * @param $url string
     * @return array
     */
    private function sendWechat($user, $sub, $url)
    {
        if ($this->_loginToken !== '') {
            $url = $url . '&token=' . $this->_loginToken;
        }
        $content = $sub . "\n" . "友情提示:请在内网进行访问,外网审批请点击邮箱里的审批按钮!!! ";
        return ExtWechat::getInstance()->sendWechat($user, 'PMS系统消息通知', $content, $url);
    }

    /**
     * 发送短信信息
     * @param $user string
     * @return array
     */
    private function sendSms($user)
    {
        $userInfo = BusIssueServer::getInstance()->getIssueInfoFormToken($user);

        ExtMsgApi::getInstance()->sendSms(array('mobile' => $userInfo['mobile'],
            'content' => "您的凤凰令牌已经申请成功，请在30分钟内绑定令牌，超过30分钟无法绑定！"));
    }

    /**
     * 替换邮件提案内容,并生成免登陆的token
     * @param $action string 操作
     * @param $user string 用户名
     * @return string
     */
    private $_loginToken = '';
    private $_emailToken = '';

    private function _setServerInfo($action, $user)
    {
        $html = "<tr><td> 提案ID: </td> <td class='left'>{$this->_serverInfo['id']}</td></tr>" .
            "<tr><td> 提案标题: </td> <td class='left'>{$this->_serverInfo['i_title']}</td></tr>" .
            "<tr><td> 提案类型: </td> <td class='left'>{$this->_serverInfo['i_name']}</td></tr>" .
            "<tr><td> 申请人: </td> <td class='left'>{$this->_serverInfo['i_applicant']}</td></tr>" .
            "<tr><td> 申请人部门: </td> <td class='left'>{$this->_serverInfo['i_department']}</td></tr>" .
            "<tr><td> 申请时间: </td> <td class='left'>{$this->_serverInfo['c_time']}</td></tr>";

        // user = api时 存入数据库会报错
        if (strpos($user, 'api_') === false) {
            $data['server_id'] = $this->_serverInfo['id'];
            $data['user'] = $user;
            $data['login_token'] = Tools::md5();
            $data['login_status'] = 1;
            if ($action == 'check') {
                $data['email_token'] = Tools::md5();
                $data['email_status'] = 1;
            } else {
                $data['email_token'] = '';
                $data['email_status'] = 0;
            }
            $data['action'] = $action;
            $addRes = MysalIssueEmailCheck::getInstance()->addNewEmailCheck($data);
        }

        // if ($addRes) {

            $this->_loginToken = $data['login_token'];
            $this->_emailToken = $data['email_token'];

            $addHtml = '';
            $infoArr = $this->createServerInfo($this->_serverInfo['id']);
            $infoHtml = "<tr height='30'><td colspan='2'>详细内容:</td></tr>";
            foreach ($infoArr as $key => $info) {
                if ($this->_serverInfo['i_type'] == 'token' && $key == 't_specialremarks') {
                    $infoHtml .= "<tr><td>{$info['label']}: </td> <td class='left' >"
                        . "<font color='#ff4500'>{$info['value']}</font></td></tr>";
                } else {
                    $infoHtml .= "<tr><td>{$info['label']}: </td> <td class='left' >{$info['value']}</td></tr>";
                }

                //凤凰令牌补充说明
                if ($this->_serverInfo['i_type'] == 'token' && $action == 'finish' &&
                    $key == 't_use[]' && strstr($info['value'], "VPN")
                ) {
                    $addHtml .= "<tr><td>补充说明: </td> <td class='left' >凤凰令牌不是VPN，VPN权限需另行申请,<font color='#28FF28'>"
                        . "<a href='http://it.staff.ifeng.com/pms/vpn'> VPN权限申请 请点击这里 </a> </font></td></tr>";
                }

            }

            $html .= $infoHtml;

            if ($addHtml !== '') {
                $html .= $addHtml;
            }

            //消息系统补充说明
            if (in_array($this->_serverInfo['i_type'], ['msgwechat', 'mail']) && $action == 'finish') {
                $attachHtml = "<tr><td>补充说明: </td> <td class='left' ><font color='#F00'>请查询邮件,邮箱里会收到《消息网关服务 生成AppKey通知》的邮件</font></td></tr>";
                $html .= $attachHtml;
            }

            //NAT 续约
            if (in_array($this->_serverInfo['i_type'], ['applynat', 'syqapplynat']) && $action == 'renew') {
                $syshost = \Yaf\Registry::get('config')->get('system.host');
                $renewHtml = "<tr height='30'><td>操作: </td> <td class='left' >"
                    . "<a href='http://{$syshost}/Operate/natdelay?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: green'>续　约</a>
                    <a href='http://{$syshost}/Operate/natdelay?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>不续约</a></td></tr>";
                $html .= $renewHtml;
            }

            //网络权限 提示续约
            if ($this->_serverInfo['i_type'] == 'applynetworkaccess' && $action == 'renew') {
                $syshost = \Yaf\Registry::get('config')->get('system.host');
                $renewHtml = "<tr height='30'><td>操作: </td> <td class='left' >"
                    . "<a href='http://{$syshost}/Operate/networkaccessdelay?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: green'>续　约</a>;
                    <a href='http://{$syshost}/Operate/networkaccessdelay?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>不续约</a></td></tr>";
                $html .= $renewHtml;
            }
            //网络权限 执行人确认完成续约操作
            if ($this->_serverInfo['i_type'] == 'applynetworkaccess' && $action == 'renewExe') {
                $renewInfo = MysqlIssueRenew::getInstance()->getOneRenewInfo(array('num', 'type'),
                    array('server_id' => $this->_serverInfo['id'], 'status' => 1), null, 'id desc');
                $syshost = \Yaf\Registry::get('config')->get('system.host');
                $renewHtml = "<tr height='30'><td>本次续约时长: </td> <td class='left' ><b style='color:#F00'>{$renewInfo['num']} {$renewInfo['type']}</b></td></tr>";

                $renewHtml .= "<tr height='30'><td>操作: </td> <td class='left' >"
                    . "<a href='http://{$syshost}/Operate/networkaccessdelayexe?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: #ea6b2b'>点此处理</a></td></tr>";
                $html .= $renewHtml;
            }
            //服务器权限 提示续订
            if ($this->_serverInfo['i_type'] == 'renewserverpermission' && $action == 'renewServerPermission') {
                $syshost = \Yaf\Registry::get('config')->get('system.host');
                $renewHtml = "<tr height='30'><td>重要说明: </td><td class='left' style='color: #ff4500'>超7天未处理，将禁用您的跳板机服务器权限</td></tr>";

                $renewHtml .= "<tr height='30'><td>续订操作: </td> <td class='left' >"
                    . "<a href='http://{$syshost}/Operate/renewServerPermission?id={$this->_serverInfo['id']}' style='text-decoration: none;color: #fff; padding: 5px 10px; margin-right: 10px; border-radius: 4px; font-weight: 700; background: green'>点此按钮进入续订页面</a></td></tr>";
                $html .= $renewHtml;
            }

            //审批节点信息
            if ($action == 'check') {
                $nodeHtml = "<tr>
                        <td>流程日志</td>
                        <td style='padding: 10px 10px;'>
                            <table class='table-bordered'>
                                <thead align=left>
                                    <tr>
                                        <th>审批人</th>
                                        <th>角色</th>
                                        <th>操作</th>
                                        <th>详情</th>
                                        <th>途径</th>
                                        <th>时间</th>
                                    </tr>
                                </thead>
                                <tbody>";

                $nodeInfo = $this->__getNodeInfo($this->_serverInfo['id']);
                foreach ($nodeInfo as $node) {
                    if (!empty($node['agent'])) {
                        $node['user'] = $node['agent'] . "(" . $node['name'] . ")(" . $node['user'] . "代)";
                    } else {
                        $node['user'] = $node['user'] . "(" . $node['name'] . ")";
                    }
                    $nodeHtml .= "<tr>
                                <td>{$node['user']}</td>
                                <td>{$node['role']}</td>
                                <td>{$node['action']}</td>
                                <td>{$node['remarks']}</td>
                                <td>{$node['way']}</td>
                                <td>{$node['time']}</td>
                            </tr>";
                }
                $nodeHtml .= "</tbody>
                            </table>
                        </td>
                    </tr>";

                $html .= $nodeHtml;
            }

            $this->replaceTemplate('ServerInfo', $html);

            $this->_setEmailCheck($action, $data['email_token']);
        // } else {
        //     $msg = $this->_serverInfo['id'].' 生成邮件Token时错误,请注意!';
        //     self::sendErrorEmailAction($msg);
        // }
    }

    /**
     * 获取提案节点信息
     * @param $id int 提案ID
     */
    private function __getNodeInfo($id)
    {
        $nodeList = MysqlIssueNode::getInstance()->getNodeList(array('action', 'start_time', 'end_time',
            'operator_agent', 'status', 'way', 'operator', 'operator_name', 'role', 'remarks'),
            array('server_id' => $id, 'status' => 1), null, 'id desc');

        $actionMsg = array('create' => '创建', 'check' => '审批', 'receive' => '领取', 'appoint' => '指派',
            'reject' => '驳回', 'transfer' => '移交', 'commit' => '备注', 'exe' => '执行', 'update' => '更新',
            'finish' => '关闭', 'cancel' => '取消', 'reopen' => '重开', 'stop' => '终止');
        $data = array();
        foreach ($nodeList as $key => $node) {
            if ($node['action'] == 'create') {
                $endTime = $node['start_time'];
            } else {
                $endTime = $node['end_time'];
            }
            $data[] = array('way' => $node['way'],
                'time' => $endTime,
                'user' => $node['operator'],
                'agent' => $node['operator_agent'],
                'name' => $node['operator_name'],
                'role' => $node['role'],
                'action' => $actionMsg[$node['action']],
                'remarks' => $node['remarks']);
        }

        return $data;
    }


    /**
     * 替换邮件用户内容-外网邮件直接审批
     * @param $type string
     * @param $token string
     * @return string
     */
    private function _setEmailCheck($type, $token)
    {
        $html = '';
        if ($type == 'check') {
            $host = \Yaf\Registry::get('config')->get('Extranet.host');
            $pass = $host . 'Check?token=' . $token . '&code=2';
            $reject = $host . 'Check?token=' . $token . '&code=3';

            $html = "<tr><td>
                            <table>
                                <tr>
                                    <td style='background-color: #28b779;padding: 5px 10px;color:#fff;'><a href={$pass} style='text-decoration : none;color: #fff' >直接审批</a></td>
                                    <td width='20'>　　</td>
                                    <td style='background-color: #ffb848;padding: 5px 10px;color:#fff;'><a href={$reject} style='text-decoration : none;color: #fff' >直接驳回</a></td>
                                    <td width='20'>　　</td>
                                    <td width='150'> <font color='#ff4500'>(可在外网使用)</font>　</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr height='20'></tr>";
        }
        $this->replaceTemplate('EmailCheck', $html);
    }

    /**
     * 替换邮件用户内容-内网登录系统
     * @param $url string
     * @return null
     */
    private function _setLinkInfo($url)
    {
        if ($this->_loginToken !== '') {
            $url = $url . '&token=' . $this->_loginToken;
        }
        $html = "若您需要处理该提案, <a href='{$url}' >请点击这里</a>,登录PMS系统查看详情! <font color='#ff4500'>(只限内网环境)</font>";
        $this->replaceTemplate('LinkInfo', $html);
    }

    /**
     * 替换邮件用户内容
     * @param $url string
     * @return null
     */
    private function _setFocusLinkInfo($url)
    {
        $html = "查看提案详情 ,<a href='{$url}' style='color:#666666;' target=_blank> 请点击这里,登录系统查看详情! </a>  <font color='#ff4500'>(只限内网环境)";
        $this->replaceTemplate('LinkInfo', $html);
    }

    /**
     * 通用替换邮件内容
     * @param $flag string 通配符
     * @param $val string  替换值
     * @return null
     */
    private function replaceTemplate($flag, $val)
    {
        $this->_emailTplInfo = str_replace('%{' . $flag . '}', $val, $this->_emailTplInfo);
    }

    /**
     * 获取提案详细内容
     * @param $id string
     * @return array
     * 可以优化,复用,以后再说
     */
    private function createServerInfo($id)
    {
        $sql = 'SELECT
                        `List`.`id`,
                        `List`.`i_type`,
                        `Info`.`infoJson`
                    FROM
                        `IssueServer` AS `List`
                    JOIN `IssueServerInfo` AS `Info` ON (`Info`.`id` = `List`.`info_id`)
                    WHERE `List`.`id` = :id';

        $serverMysqlInfoList = MysqlIssueServer::getInstance()->querySQL($sql, array('id' => $id));

        if (!empty($serverMysqlInfoList)) {

            $serverMysqlInfo = $serverMysqlInfoList[0];

            $textArr = json_decode(DAOIssueConfig::getInstance()->getTableField($serverMysqlInfo['i_type'], 'text'), true);
            $confArr = json_decode(DAOIssueConfig::getInstance()->getTableField($serverMysqlInfo['i_type'], 'config'), true);

            $oldInfoArr = json_decode($serverMysqlInfo['infoJson'], true);

            $infoArr = array();
            foreach ($oldInfoArr as $name => $info) {
                if (is_array($info)) {
                    $newName = $name . '[]';
                    if (!empty($textArr[$newName])) {
                        if (!empty($confArr[$newName])) {
                            $valArr = array();
                            foreach ($info as $val) {
                                $valArr[] = $confArr[$newName][$val];
                            }

                            $infoArr[$newName] = array('label' => $textArr[$newName], 'value' => implode('<br>', $valArr));
                        } else {
                            $infoArr[$newName] = array('label' => $textArr[$newName], 'value' => $info);
                        }
                    }

                } else {
                    if (!empty($textArr[$name])) {
                        if (!empty($confArr[$name])) {
                            $infoArr[$name] = array('label' => $textArr[$name], 'value' => $confArr[$name][$info]);
                        } else {
                            $infoArr[$name] = array('label' => $textArr[$name], 'value' => $info);
                        }
                    }
                }
            }
            return $infoArr;
        } else {
            return array();
        }
    }

    /**
     * 发送报错信息邮件
     * @param $uid string
     * @param $id string
     * @param $msg string
     * @return array
     */
    public function sendEmailAndRtxAction($uid, $subject, $msg)
    {
        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统自动提醒';

        $mail = new \SendMail\MailModel;
        $uidArr = explode(',', $uid);

        foreach ($uidArr as $u) {
            $tos[] = array('address' => "{$u}@ifeng.com", 'name' => '');
        }
        if (!empty($tos)) {
            $mail->sendTextMail($subject, $tos, $from, $from_name, $msg);
        }
    }

    /*
     * 发送故障报告提醒邮件
     * @param $id string 
     * @param $tos string
     */
    public function sendAvaInfoEmailAction($id, $tos)
    {
        $avaInfo = MysqlCommon::getInstance()->getOneInfo('AvaInfo', null, ['id' => $id]);

        $dutyUserStr = '';
        foreach (json_decode($avaInfo['duty_user'], true) as $value) {
            $dutyUserStr .= " " . $value['name'] . "(" . $value['user'] . ")";
        }

        $subject = "故障报告: " . $avaInfo['name'] . " " . $avaInfo['title'];
        $this->_emailTplInfo = file_get_contents(TPL_AVA_INFO);
        $this->replaceTemplate('title', $avaInfo['title']);
        $this->replaceTemplate('user', $avaInfo['name'] . '(' . $avaInfo['user'] . ') 【' . $avaInfo['department'] . '】');
        $this->replaceTemplate('business', $avaInfo['norm_master'] . ' - ' . $avaInfo['norm_slave']);
        $this->replaceTemplate('level', $avaInfo['level_master']);
        $this->replaceTemplate('type', $avaInfo['type']);
        $this->replaceTemplate('effect', $avaInfo['effect']);
        $this->replaceTemplate('start_time', $avaInfo['start_time']);
        $this->replaceTemplate('end_time', $avaInfo['end_time']);
        $this->replaceTemplate('time', round($avaInfo['time'] / 3600, 2) . '小时');
        $this->replaceTemplate('reason', $avaInfo['reason']);
        $this->replaceTemplate('duty_department', $avaInfo['duty_department']);
        $this->replaceTemplate('duty_user', $dutyUserStr);
        $this->replaceTemplate('improvement', $avaInfo['improvement']);

        $from = 'pms@ifeng.com';
        $from_name = 'PMS系统自动提醒';

        $mail = new \SendMail\MailModel;
        $tos = json_decode($tos, true);

        $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo);
    }

}