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


    }

}
