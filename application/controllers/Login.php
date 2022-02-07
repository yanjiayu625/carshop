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
        $role   = $this->getRequest()->getQuery('role');

        if( empty($loginToken) || empty($type) || empty($id) ){
            Tools::redirect('/index');
        }else{
            $info = MysalIssueEmailCheck::getInstance()->getOneEmailCheckInfoByWhere(
                array('login_token'=>$loginToken,'login_status' => '1'));
            if( empty($info) ){
                Tools::redirect("/issue/server/{$type}?id={$id}&&role={$role}");
            }else{
                if( empty(Session::getInstance()->getValue('usr')) ) {
                    $userInfo = ExtLdapApi::getInstance()->getAllUserInfo($info['user']);
                    Session::getInstance()->setExpire(true);
                    Session::getInstance()->setValue(array('usr' => $info['user']));
                    Session::getInstance()->setValue(array('name' => $userInfo['name']));
                    Session::getInstance()->setValue(array('email' => $userInfo['email']));

                    $this->__saveUserInfo($info['user'],$userInfo['name'],$userInfo['org']);
                }
                MysalIssueEmailCheck::getInstance()->updateEmailCheck(array('login_status'=>0),
                    array('login_token'=>$loginToken,'user' =>$info['user']));
                Tools::redirect("/issue/server/{$type}?id={$id}&role={$role}");
            }
        }

    }

    /**
     * 用户登出操作
     */
    public function logoutAction()
    {
        Session::getInstance()->destroy();
        ExtIdcOsApi::getInstance()->logout();
    }

    /**
     * 系统维护跳转
     */
    public function maintainingAction(){

    }

    /**
     * 从登录时保存用户信息,主要为组织结构信息
     * @param string $usr 域账户
     * @param string $name 中文名
     * @param array $org 组织结构信息
     */
    private function __saveUserInfo($usr, $name, $org){

        $userInfo = MysalIssueUserInfo::getInstance()->getOneUserInfo(array('id'),array('user'=>$usr));

        if(empty($userInfo)){
            $data['user']   = $usr;
            $data['cname']  = empty($name) ? '' : $name;
            $data['department'] = $org['department'] ?? '';
            $data['center'] = $org['center'] ?? '';
            $data['group']  = $org['group'] ?? '';
            $data['leader'] = '';
            MysalIssueUserInfo::getInstance()->addNewUser($data);
        }else{
            $set['cname']      = empty($name) ? '' : $name;
            $set['department'] = $org['department'] ?? '';
            $set['center']     = $org['center'] ?? '';
            $set['group']      = $org['group'] ?? '';
            MysalIssueUserInfo::getInstance()->updateUserInfo($set,array('user'=>$usr));
        }
    }
}