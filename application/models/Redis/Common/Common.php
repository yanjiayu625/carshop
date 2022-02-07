<?php

namespace Redis\Common;

/**
 * 通用Redis
 */
class CommonModel extends \Redis\AbstractModel
{

    /**
     * Redis前缀
     * @var string
     */
    protected $_prefix = null;

    public function __construct($prefix)
    {
        $this->_prefix = $prefix;
    }

    /**
     * 计算key
     * @param int $key
     * @return string
     */
    public function calcKey($key)
    {
        return $this->_prefix . self::DELIMITER . $key;
    }

    /**
     * 保存哈希数组
     * @param string $key
     * @param array $fieldArr
     * @return boolean
     * @throws string
     */
    public function setHashValue($key, $fieldArr)
    {
        return $this->hmset($this->calcKey($key), $fieldArr);
    }

    /**
     * 获取哈希数据中某个Key的对应Value值
     * @param string $key
     * @param string $field
     * @return array
     */
    public function getHashFieldValue($key, $field)
    {
        return $this->hget($this->calcKey($key), $field);
    }

    /**
     * 获取哈希数据所有Value
     * @param string $key
     * @return array
     */
    public function getHashAllValue($key)
    {
        return $this->hgetall($this->calcKey($key));
    }

    /**
     * 保存字符串数据 K-V
     * @param string $key
     * @param array $data
     * @return array
     */
    public function setValue($key, $data)
    {
        return $this->set($this->calcKey($key), json_encode($data));
    }

    /**
     * 设置K-V与过期时间
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/10/11 14:18
     * @param $key
     * @param $value
     * @param null $expire
     * @return bool
     * @throws \Exception
     */
    public function setValueExpire($key, $value, $expire = null)
    {
        if (!is_string($value))
        {
            $value = json_encode($value);
        }
        return $this->getRedis()->set($this->calcKey($key), $value, $expire);
    }

    /**
     * 获取字符串数据 K-V
     * @param string $key
     * @return array
     */
    public function getValue($key)
    {
        return $this->get($this->calcKey($key));
    }
    /**
     * 删除数据
     * @param int $key
     * @return array
     */
    public function delete($key)
    {
        return $this->del($this->calcKey($key));
    }

    /**
     * 判断key存不存在
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/10/11 11:29
     * @param $key
     * @return mixed
     */
    public function exists($key)
    {
        return $this->getRedis()->exists($this->calcKey($key));
    }
    
    /**
     * 类实例
     * @var \Redis\Common\CommonModel
     */
    private static $_instance = null;

    /**
     * 获取类实例
     * @param $prefix string
     * @return \Redis\Common\CommonModel
     */
    public static function getInstance($prefix)
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($prefix);
        }
        self::$_instance->_prefix = $prefix;

        return self::$_instance;
    }

}
