<?php

namespace Redis\Common;

/**
 * JWTtoken
 */
class JwtModel extends \Redis\AbstractModel {

    /**
     * Redis前缀
     * @var string
     */
    protected $_prefix = 'JWTtoken';

    /**
     * 计算key
     * @return string
     */
    public function calcKey($prefix) {
        return $prefix.'-'.$this->_prefix;
    }

    /**
     * @param string 
     * @return boolean
     * @throws string
     */
    public function setValue($prefix, $value) {
        $set = $this->set($this->calcKey($prefix), $value);
        $second = 72*3600;
        $exp = $this->expire($this->calcKey($prefix), $second);
        if( $set && $exp ){
            return true;
        }else{
            throw new \Exception("保存Redis失败！");
        }
    }

    /**
     * 获取token值
     * @return array
     */
    public function getValue($prefix) {
        return $this->get($this->calcKey($prefix));
    }

    private static $_instance = null;
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


}
