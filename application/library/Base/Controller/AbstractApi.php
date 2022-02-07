<?php

namespace Base;

use \Tools\Tools as Tools;
use \Redis\Common\CommonModel     as RedisCommon;

/**
 * Api模块控制器抽象类
 */
abstract class Controller_AbstractApi extends \Yaf\Controller_Abstract
{
    private  $_ipArr = NULL;
    public   $_sourceIp = NULL;

    private  $_token = '1778833722ed91b55f442bbb5de87f03';
    public   $_userInfo = [];

    private function initIpArr()
    {
        $ipArr = [
            'default_api' => [ //通用白名单列表
                '127.0.0.1',
                '172.30.150.102',
            ],
        ];

        return $ipArr;
    }
    
    public function init()
    {
        \Yaf\Dispatcher::getInstance()->disableView();

        try {
            $this->_ipArr = $this->initIpArr();

            $token = $this->getRequest()->getServer('HTTP_TOKEN');
            $this->_sourceIp = Tools::getRemoteAddr();
            $controller = strtolower($this->getRequest()->getControllerName());

            switch ($controller) {
                case 'ext_api':
                    break;

                default:
                    if (empty($token)) {
                        throw new \Exception('密钥缺失', 400);
                    }
                    if ($token != $this->_token) {
                        throw new \Exception('密钥错误', 400);
                    }
                    if(!in_array($this->_sourceIp, array_merge($this->_ipArr[$controller]??[], $this->_ipArr['default_api']))){
                        NewLog::AuthLog("IP: " . $this->_sourceIp . " controllerName:" . $controller );
                        throw new \Exception("请求IP非法: " . $this->_sourceIp, 400);
                    }
                    break;
            }

        } catch (\Exception $e) {

            $res['code'] = $e->getCode();
            $res['msg']  = $e->getMessage();

            Tools::returnAjaxJson($res);
        }
    }

}
