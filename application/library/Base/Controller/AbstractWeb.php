<?php

namespace Base;

use \Tools\Tools;
use \Redis\Common\SessionModel as SessionRedis;
use \Redis\Common\CommonModel as RedisCommon;
use \Mongodb\Issue\CommonModel as MongoDBIssue;
use \DAO\Data\PmsModel as PmsConf;
use \ExtInterface\Pms\ApiModel as ExtPmsApi;


/**
 * Web端控制器抽象类
 *
 */
abstract class Controller_AbstractWeb extends \Yaf\Controller_Abstract {

    /**
     * Web用户登录权限
     */
    public $_uid   = '';
    public $_cname = '';
    public $_dept  = '';
    public $_phone  = '';
    public $_sex   = '';

    public function init() {

        \Yaf\Dispatcher::getInstance()->disableView();

    }

    public function commonReturn($code,$msg='',$result = []){
        $res['code'] = $code;
        $res['msg']  = $msg;
        $res['data'] = $result;
        Tools::returnAjaxJson($res);
    }

}
