<?php

use \Base\Log                     as Log;
use \Tools\Tools                  as Tools;
use \Mysql\Issue\ServerModel      as MysqlIssueServer;
use \Mysql\Issue\EmailCheckModel  as MysalIssueEmailCheck;
use \DAO\Issue\ConfigModel        as DAOIssueConfig;
use \ExtInterface\Msg\ApiModel    as ExtMsgApi;
use \Mysql\Issue\MobileModel      as MysalIssueMobile;
use \ExtInterface\Msg\WechatitModel as ExtWechatitApi;

/**
 * 给IT系统相关提案发送邮件,内容与PMS邮件不同
 */
class ItemailController extends \Base\Controller_AbstractCli
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

            $host = \Yaf\Registry::get('config')->get('It.host');
            $this->_serverInfo = MysqlIssueServer::getInstance()->getOneServer(null, array('id' => $id));

            $attachs = null; //邮件附件,默认为null

            switch ($action) {
                case 'check':
                    $url = "/pms/approve?id={$id}&type={$this->_serverInfo['i_type']}";
                    $subject = "IT客服系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】,需要您审批,谢谢";
                    break;
                case 'receive':
                    $url = "/pms/receive?id={$id}";
                    $subject = "IT客服系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】,需要您领取,谢谢";
                    break;
                case 'appoint':
                    $subject = "IT客服系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】,需要您指派执行人,谢谢";
                    break;
                case 'exe':
                    $url = "/pms/exe?id={$id}&type={$this->_serverInfo['i_type']}";
                    $subject = "IT客服系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】,需要您执行,谢谢";
                    break;
                case 'finish':
                    if ($this->_serverInfo['i_title'] == '【特权申请】香港代理') {
                        $fileDir = APPLICATION_PATH . '/public/download/Ifeng-Proxy-V1.0.docx';
                        $attachs = array($fileDir);
                    }
                    if ($this->_serverInfo['i_title'] == '【特权申请】VPN') {
                        $fileDir = APPLICATION_PATH . '/public/download/IFENG-Token-VPN.zip';
                        $attachs = array($fileDir);
                    }
                    $url = "/pms/detail?id={$id}";
                    $subject = "IT客服系统提示:您申请的 【{$this->_serverInfo['i_title']}】已执行完成,谢谢";
                    break;
                case 'inform':
                    $url = "/pms/detail?id={$id}";
                    $subject = "IT客服系统提示:{$this->_serverInfo['i_applicant']}申请的 【{$this->_serverInfo['i_title']}】已执行完成,特此通知您,谢谢";
                    break;
                default:
                    echo '参数错误!';
                    die;
            }

            $url = $host . $url;

            $this->_emailTplInfo = file_get_contents(TPL_IT_EMAIL_ISSUE);

            $this->_setServerInfo($action, $user);
            $this->_setLinkInfo($url);

            $mail = new \SendMail\MailModel;

            $tos = array('address' => "{$user}@ifeng.com", 'name' => $user);
            $from = 'pms@ifeng.com';
            $from_name = 'IT中心客服系统管理员';

            $RtxRes = $this->sendRtxMsg($user, $subject, $url);
            $msg = "提案号{$id}发送Rtx状态:".(string)$RtxRes;
            Log::getInstance('msg')->write($msg);

            $WechatRes = $this->sendWechat($user, $subject, $url);
            $msg = "提案号{$id}发送Wechat(it)状态:".(string)$WechatRes;
            Log::getInstance('msg')->write($msg);

            $mailRes = $mail->sendHtmlMail($subject, $tos, $from, $from_name, $this->_emailTplInfo, $attachs);
            $msg = "提案号{$id}发送邮件状态:".(string)$mailRes;
            Log::getInstance('msg')->write($msg);

        } else {
            echo '参数错误';
            die;
        }

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
        $tos = array('address' => "zhangyang7@ifeng.com", 'name' => '张阳');

        $mail->sendHtmlMail($subject, $tos, $from, $from_name, $msg);
        $this->sendRtxMsg($tos['address'], $subject, '');
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
        $userInfo = MysalIssueMobile::getInstance()->getOneMobileByWhere(array('user'=>$user));
        if(!empty($userInfo['cname'])){
            $rtx['account'] = $userInfo['cname'];
            $rtx['title']   = 'IT客服系统消息通知';
            $rtx['content'] = $sub . '.' . "\n" . '详情请点击:' . $url;
            return ExtMsgApi::getInstance()->sendRtx($rtx);
        }else{
            return false;
        }
    }

    /**
     * 发送Wechat信息
     * @param $user string
     * @param $sub string
     * @param $url string
     * @return array
     */
    private function sendWechat($user, $sub, $url)
    {
        $title   = 'IT客服系统消息通知';
        $content = $sub . '.' . "\n" . '详情请点击:' . $url;
        return ExtWechatitApi::getInstance()->sendWechat($user, $title, $content);
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
        $html =  "<tr><td> 提案标题: </td> <td class='left'>{$this->_serverInfo['i_title']}</td></tr>" .
            "<tr><td> 提案类型: </td> <td class='left'>{$this->_serverInfo['i_name']}</td></tr>" .
            "<tr><td> 申请人: </td> <td class='left'>{$this->_serverInfo['i_applicant']}</td></tr>" .
            "<tr><td> 申请时间: </td> <td class='left'>{$this->_serverInfo['c_time']}</td></tr>";

        $data['server_id'] = $this->_serverInfo['id'];
        $data['user'] = $user;
        $data['login_token'] = Tools::md5();
        $data['login_status'] = 1;
        if($action == 'check'){
            $data['email_token'] = Tools::md5();
            $data['email_status'] = 1;
        }else{
            $data['email_token'] = '';
            $data['email_status'] = 0;
        }
        $data['action'] = $action;
        $addRes = MysalIssueEmailCheck::getInstance()->addNewEmailCheck($data);

        if ($addRes) {

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
            }
            $html .= $infoHtml;

            if ($addHtml !== '') {
                $html .= $addHtml;
            }

            $this->replaceTemplate('ServerInfo', $html);

            $this->_setEmailCheck($action, $data['email_token']);
            $this->_specialInfo($action);
        } else {
            $msg = $this->_serverInfo['id'].' 生成邮件Token时错误,请注意!';
            self::sendErrorEmailAction($msg);
        }
    }

    /**
     * 替换邮件特殊内容
     * @param $action string
     */
    private function _specialInfo($action)
    {
        if ($this->_serverInfo['i_title'] == '【特权申请】VPN' && $action == 'finish' ) {
            $html = "<div><p class=\"text-name\">Dear，凤凰人~</p>
                          <p class=\"text-detail\">您已成功开通VPN，接下来只需几个步骤，您就可以不受地域限制的访问内网环境办公啦~
                                    具体安装方法请详见附件</p>

                                <p class=\"text-contact\">如您有任何问题，请RTX联系IT中心，或者致电010-6067 6789</p>
                            </div>";

        }else{
            $html = '';
        }

        $this->replaceTemplate('SpecialInfo', $html);
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
        $html = "若您需要处理该申请, <a href='{$url}' >请点击这里</a>,登录IT中心客服系统查看详情! <font color='#ff4500'>(只限内网环境)</font>";
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
     * @return string
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

}