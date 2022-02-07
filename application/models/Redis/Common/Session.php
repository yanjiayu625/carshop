<?php

namespace Redis\Common;

/**
 * 用户信息缓存
 */
class SessionModel extends \Redis\AbstractModel {

    /**
     * Redis前缀
     * @var string
     */
    protected $_prefix = null;

    public function __construct()
    {
        $this->_prefix = 'Web'.'Auth';
    }
    /**
     * 计算key
     * @param int $token
     * @return string
     */
    public function calcKey($token) {
        return $this->_prefix . self::DELIMITER . $token;
    }

    /**
     * 根据token、fieldArr初始化用户Session信息
     * @param string $token
     * @param array $fieldArr
     * @param int $hour
     * @return boolean
     * @throws string
     */
    public function create($token,$fieldArr,$hour) {
        $set = $this->hmset($this->calcKey($token), $fieldArr);
        $second = $hour*3600 ;
        $exp = $this->expire($this->calcKey($token), $second);
        if( $set && $exp ){
            return true;
        }else{
            throw new \Exception("初始化Session时保存Redis失败！");
        }
    }

    /**
     * 根据token、fieldArr保存用户Session信息
     * @param string $token
     * @param array $fieldArr
     * @return boolean
     * @throws string
     */
    public function setValue($token, $fieldArr) {
        $set = $this->hmset($this->calcKey($token), $fieldArr);
        if( $set ){
            return true;
        }else{
            throw new \Exception("设置Session值时保存Redis失败！");
        }
    }

    /**
     * 根据token查找用户Session信息
     * @param string $token
     * @param string $field
     * @return array
     */
    public function getFieldValue($token,$field) {
        if( is_string($field) ){
            return $this->hget($this->calcKey($token),$field);
        }else{

        }
        return null;
    }

    /**
     * 根据token查找用户Session信息
     * @param string $token
     * @return array
     */
    public function getAllValue($token) {
        return $this->hgetall($this->calcKey($token));
    }
    /**
     * 更新数据
     * @param string $token
     * @param string $field
     * @param string $value
     * @return array
     */
    public function updateValue($token, $field, $value) {
        return $this->hset($this->calcKey($token), $field, $value);
    }

    /**
      * 删除Session
      * @param int $key
      * @return array
      */
    public function delete($key) {
        return $this->del($this->calcKey($key));
    }

    /**
     * 类实例
     * @var \Redis\Common\SessionModel
     */
    private static $_instance = null;

    /**
     * 获取类实例
     * @return \Redis\Common\SessionModel
     */
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

}
