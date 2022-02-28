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

    //公共返回
    public function commonReturn($code,$msg='',$result = []){
        $res['code'] = $code;
        $res['msg']  = $msg;
        $res['data'] = $result;
        Tools::returnAjaxJson($res);
    }

    //密码加密
    public function encryptionPass($pass){
        return md5(md5($pass));
    }

    //curl
    public function curl_request($url,$post='',$cookie='', $returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        $getinfo = curl_getinfo($curl);//curl_getinfo()函数获取CURL请求输出的相关信息
        curl_close($curl);
        if($returnCookie){
            $info = array();
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = empty($matches[1][0]) ? $matches : substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            return json_decode($data,true);
        }
    }

}
