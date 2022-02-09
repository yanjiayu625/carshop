<?php

use \Tools\Tools;
use \Session\Session;
use \ExtInterface\IdcOs\ApiModel as ExtIdcOsApi;
use \ExtInterface\Sso\ApiModel   as ExtSsoApi;
use \ExtInterface\Ldap\ApiModel  as ExtLdapApi;
use \Mysql\Issue\EmailCheckModel as MysalIssueEmailCheck;
use \Mysql\Issue\UserModel       as MysalIssueUserInfo;

class LoginController extends \Base\Controller_AbstractIndex
{

    /**
     * 用户登录调用SSO(test)
     */
    public function ssoAction()
    {
        $usr      = $this->getRequest()->getQuery('uid');
        $backurl  = $this->getRequest()->getQuery('backurl');
        if( empty($usr) || empty($backurl) ){
            $ssoLogin = ExtSsoApi::getLoginUrl($backurl);
            Tools::redirect($ssoLogin);
        }else{
            $userInfo = ExtSsoApi::getUserInfoFromSso($usr);
            var_dump($userInfo);die;
        }
    }

    /**
     * 用户登录调用SSO
     */
    public function indexAction()
    {
        if( Session::getInstance()->getValue('usr') ){
            Tools::redirect('/index');
        }else{
            $usr      = $this->getRequest()->getQuery('uid');
            $backurl  = $this->getRequest()->getQuery('backurl');
            if( empty($usr) || empty($backurl) ){
                Tools::redirect('/index');
            }else{
                $userInfo = ExtLdapApi::getInstance()->getAllUserInfo($usr);
                if(empty($userInfo) || $userInfo['org']['department'] == 'OSDEP'){
                    //Ldap无此人 或 在 OSDEP组中
                    Tools::redirect('/error/forbidden');
                    
                }else{

                    Session::getInstance()->setExpire(true);
                    Session::getInstance()->setValue(array('usr'=>$usr));
                    Session::getInstance()->setValue(array('name'=>$userInfo['name']));
                    Session::getInstance()->setValue(array('email'=>$userInfo['email']));

                    $this->__saveUserInfo($usr,$userInfo['name'],$userInfo['org']);

                    Tools::redirect(urldecode($backurl));
                }
            }
        }
    }

    /**
     * 自动登录,通过邮件token登陆
     */
    public function autoAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView();

        $loginToken = $this->getRequest()->getQuery('token'); //邮件中的token验证
        $type   = $this->getRequest()->getQuery('type');
        $id     = $this->getRequest()->getQuery('id');

        if( empty($loginToken) || empty($type) || empty($id) ){
            Tools::redirect('/index');
        }else{

        }

    }

    /**
     * 用户登出操作
     */
    public function logoutAction()
    {
        Session::getInstance()->destroy();
    }

    /**
     * 系统维护跳转
     */
    public function maintainingAction(){

    }

}