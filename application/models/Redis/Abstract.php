<?php

namespace Redis;

/**
 * redis操作类
 */
class AbstractModel {

    /**
     * 表名和键的分割符号
     */
    const DELIMITER = ':';

    /**
     * 连接的库
     * 
     * @var int 
     */
    protected $_db = 0;

    /**
     * 前缀
     * 
     * @var string 
     */
    static $prefix = "";

    /**
     * redis连接对象，未选择库的
     * 
     * @var \Redis
     */
    static $redis;

    /**
     * 获取redis连接
     * 
     * @staticvar null $redis
     * @return \Redis
     * @throws \Exception
     */
    public function getRedis() {
        if (!self::$redis) {
            $conf = \Yaf\Registry::get('config')->get('redis.params');
            if (!$conf) {
                throw new \Exception('redis连接必须设置');
            }
            if (isset($conf['prefix'])) {
                self::$prefix = $conf['prefix'];
            }
            
            self::$redis = new \Redis();
            self::$redis->connect($conf['host'], $conf['port'], 3);
            self::$redis->auth($conf['pwd']);
        }
        self::$redis->select($this->_db);
        return self::$redis;
    }

    /**
     * 给key增加前缀
     * 
     * @param string $key
     * @return string
     */
    private function _addPrefix($key) {
        if (self::$prefix) {
            return self::$prefix . self::DELIMITER . $key;
        }
        return $key;
    }

    /**
     * 设置key的有效时间
     * @param string $key
     * @param string $second
     * @return boolean
     */
    public function expire($key,$second) {
        return $this->getRedis()->expire($this->_addPrefix($key),$second);
    }

    /**
     * 删除key
     * @param string $key
     * @return boolean
     */
    public function del($key) {
        return $this->getRedis()->del($this->_addPrefix($key));
    }

    /**
     * 获取key
     * @param string $pattern
     * @reutnr array
     * @return array
     */
    public function keys($pattern) {
        return $this->getRedis()->keys($pattern);
    }

    /**
     * 设置key和value
     * 
     * @param string $key
     * @param json $value
     * @return boolean
     */
    public function set($key, $value) {
        return $this->getRedis()->set($this->_addPrefix($key), $value);
    }

    /**
     * 设置key、expire、value
     * @param string $key
     * @param int $expire 秒
     * @param array $value
     * @return boolean
     */
    public function setex($key,$expire,$value) {
        return $this->getRedis()->setex($this->_addPrefix($key),$expire,$value);
    }

    /**
     * 根据key值获取缓存数据
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->getRedis()->get($this->_addPrefix($key));
    }

    /**
     * 根据key、fieldArr值保存数据
     * @param string $key
     * @param  $field
     * @return mixed
     */
    public function hmset($key, $field) {
        return $this->getRedis()->hmset($this->_addPrefix($key), $field);
    }

    /**
     * 根据key、field、value值保存数据
     * @param string $key
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function hset($key, $field, $value) {
        return $this->getRedis()->hset($this->_addPrefix($key), $field, $value);
    }

    /**
     * 根据key值获取Hash数据
     *
     * @param string $key
     * @return mixed
     */
    public function hgetAll($key) {
        return $this->getRedis()->hgetAll($this->_addPrefix($key));
    }

    /**
     * 根据key,field值获取Hash数据
     *
     * @param string $key
     * @param string $field
     * @return mixed
     */
    public function hget($key,$field) {
        return $this->getRedis()->hget($this->_addPrefix($key),$field);
    }

    /**
     * redis自增1
     * 
     * @param string $key
     * @return int
     */
    public function incr($key) {
        return $this->getRedis()->incr($this->_addPrefix($key));
    }

    /**
     * redis自减1
     * 
     * @param string $key
     * @return int
     */
    public function decr($key) {
        return $this->getRedis()->decr($this->_addPrefix($key));
    }

    /**
     * redis自减1
     * 
     * @param string $key
     * @return int
     */
    public function decrby($key, $decrement) {
        return $this->getRedis()->decrby($this->_addPrefix($key), $decrement);
    }

    /**
     * 增加列表内的元素
     * 
     * @param string $key
     * @param mix $value
     * @return int
     */
    public function lpush($key, $value) {
        return $this->getRedis()->lpush($this->_addPrefix($key), $value);
    }

    /**
     * 阻塞取
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/23 17:47
     * @param $key
     * @param int $timeout
     * @return array
     * @throws \Exception
     */
    public function brpop($key, $timeout=10)
    {
        return $this->getRedis()->brPop($this->_addPrefix($key), $timeout);
    }

    /**
     * 获取列表内的元素
     * @param string $key
     * @param int $start
     * @param int $stop
     * @return mix
     */
    public function lrange($key, $start, $stop) {
        return $this->getRedis()->lrange($this->_addPrefix($key), $start, $stop);
    }

    /**
     * 增加集合内的元素
     * 
     * @param string $key
     * @param mix $value
     * @return int
     */
    public function sadd($key, $value) {
        return $this->getRedis()->sadd($this->_addPrefix($key), $value);
    }

    /**
     * 列出集合内的元素
     * 
     * @param int $key
     * @return mix
     */
    public function smembers($key) {
        return $this->getRedis()->smembers($this->_addPrefix($key));
    }

    /**
     * 添加元素至有序表
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/24 10:37
     * @param $key
     * @param array $options
     * @param $score
     * @param $value
     * @return int
     * @throws \Exception
     */
    public function zAdd($key, array $options, $score, $value)
    {
        if (empty($options))
        {
            return $this->getRedis()->zAdd($key, $score, $value);
        }

        return $this->getRedis()->zAdd($key, $options, $score, $value);
    }

    /**
     * 从集合中删除元素
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/24 10:38
     * @param $key
     * @param mixed ...$members
     * @return int
     * @throws \Exception
     */
    public function zRem($key, ...$members)
    {
        return $this->getRedis()->zRem($key, ...$members);
    }

    /**
     * 成员值递增
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/24 10:47
     * @param $key
     * @param $member
     * @param $num
     * @return float
     * @throws \Exception
     */
    public function zIncrBy($key, $member, $num)
    {
        return $this->getRedis()->zIncrBy($key, $num, $member);
    }

}
