<?php

namespace Base;

use \ExtInterface\Msg\WechatModel as ExtWechat;
use Redis\Common\CommonModel;
use \Tools\Tools;
use \Redis\Common\SessionModel as SessionRedis;
use \Bll\System\AuthModel         as BllSysAuth;

abstract class Controller_AbstractWechat extends \Yaf\Controller_Abstract
{

    /**
     * Wechat用户登录信息
     */
    public $_uid = '';
    public $_cname = '';
    public $_department = '';
    public $_avatar = '';

    public function init()
    {
//        $this->_uid          = 'zhangyang7';
//        $this->_cname        = '张阳';
//        $this->_department   = '信息系统部/研发组';

        \Yaf\Dispatcher::getInstance()->disableView();

        $token = $this->getRequest()->getServer('HTTP_WECHATTOKEN');
        try{
            // $sysInfo = BllSysAuth::getInstance()->systemMaintenance('Wechat');
            // if($sysInfo['status'] == true){
            //     throw new \Exception($sysInfo['time'], 402);
            // }

            if(empty($token)){
                throw new \Exception('权限错误:Token值为空', 401);
            }

            $userInfo  = SessionRedis::getInstance('Wechat')->getAllValue($token);
            if(empty($userInfo) || empty($userInfo['uid'])){
                throw new \Exception('权限错误:Token值非法', 401);
            }

            if ( !isset($userInfo['uid']) ||
                 !isset($userInfo['name']) ||
                 !isset($userInfo['avatar']) ||
                 !isset($userInfo['department'])) {
                throw new \Exception('用户基本信息缺失，请重新登陆！', 401);
            }

            $this->_uid     = $userInfo['uid'];
            $this->_cname   = $userInfo['name'];
            $this->_avatar  = $userInfo['avatar'];
            $this->_department  = $userInfo['department'];

            // 记录用户访问日志 1分钟过期
            // 屏蔽报错
            $loginInfo = [
                'token' => $token,
                'cname' => $this->_cname,
                'time' => date('Y-m-d H:i:s')
            ];
            @CommonModel::getInstance('loginLog')->setValueExpire($this->_uid, $loginInfo, 60);

        }catch (\Exception $e) {
            $res['code'] = $e->getCode();
            $res['msg']  = $e->getMessage();

            NewLog::accessLog($e->getMessage());

            if ($e->getCode() == 401)
            {
                $env = ini_get('yaf.environ') != 'product' ? 'develop' : '';
                $title = $env . "用户登录失败\n";
                $content = "url: " . empty($_SERVER['HTTP_REFERER'])?'':$_SERVER['HTTP_REFERER'] .
                    "\nuri: " .  empty($_SERVER['REQUEST_URI'])?'':$_SERVER['REQUEST_URI'] . "\n\n" . $e->getMessage();
                $wechatRes = ExtWechat::getInstance()->sendWechat('tanghan', $title, $content . ' token:'.$token, '');
            }

            Tools::returnAjaxJson($res);
        }

    }

}
