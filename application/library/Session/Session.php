<?php
namespace Session;
use \Cookie\Cookie;
use \Tools\Tools;
use \Redis\Common\SessionModel as SessionRedis;

    /**
     * Session类 已使用redis代替php_session
     */
class Session {

    private $_token = null;

    public function __construct()
    {
        $this->_token = Cookie::getInstance()->getcookie();
    }

    //获取用户Session信息
    public function setExpire($hour)
    {
        $this->_token = Cookie::getInstance()->setcookie($hour);
        if( !is_null($this->_token)  ){
            $data['id'] = $this->_token;
            $data['ip'] = Tools::getRemoteAddr();
            SessionRedis::getInstance()->create($this->_token, $data, $hour);
        }else{
            throw new \Exception("初始化Cookie失败！");
        }
        return true;
    }

    //设置用户Session信息
    public function setValue($data)
    {
        if( !is_null($this->_token) ){
            SessionRedis::getInstance()->setValue($this->_token, $data);
        }else{
            throw new \Exception("请初始化Session！");
        }
    }

    /**
     * 获取Session中的field值
     * @param  string key
     * @return string
     */
    public function getValue( $key = null )
    {
        if( !is_null($this->_token) ){
            //此处增加判断上一次登录IP功能
            return SessionRedis::getInstance()->getFieldValue($this->_token, $key);
        }else{
            return null;
        }
    }

    /**
     * 摧毁Session
     * @param  string key
     * @return string
     */
    public function destroy()
    {
        if( !is_null($this->_token) ){
            Cookie::getInstance($this->_type)->delcookie();
            return SessionRedis::getInstance()->delete($this->_token);
        }else{
            return null;
        }
    }
    
    /**
     * 获取类实例
     * @return \Session\Session
     */
    private static $_instance = null;

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __destruct()
    {
        $_token 	= null;
    }
}
