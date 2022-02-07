<?php

namespace Base;

use Tools\Tools;
use Redis\Common\CommonModel as RedisCommon;

/**
 * 日志类
 */
class Log {

    private $_handle  = null;
    private $_type    = null;
    private $_fileDir = array(
        'mysql' => '/data/log/mysql.log',
        'msg'   => '/data/log/msg.log',
        'api'   => '/data/log/api.log',
        'erp'   => '/data/log/erp.log',
        'auth'  => '/data/log/auth.log',
        'cli'   => '/data/log/cli.log',
        'access'=> '/data/log/access.log',
        'curl'=> '/data/log/curl.log',
        'travel'=> '/data/log/travel.log',
        'log' => '/data/log/test.log'
        );

    public function __construct($type)
    {
        $this->_type = $type;
    }

    public function getFileDir()
    {
        $fileDir = $this->_fileDir[$this->_type];

        if (!Tools::isDevelop())
        {
            $info = explode('.', $fileDir);
            $filename = $info[0] . '-' . date('Ymd');
            $fileDir = $filename . '.log';
        }

        return $fileDir;
    }

    public function log($ip, $name, $json)
    {
        fwrite($this->getHandle(), date("Y-m-d H:i:s")
            . "\tApiIp:" . $ip
            . "\tApiName:" . $name
            . "\tApiJson:" . $json
            . "\r\n");
    }

    /**
     * 打卡时间同步日志
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/9 10:01
     * @param $message
     */
    public static function syncLog($message)
    {
        $logStr = date("Y-m-d H:i:s")
            . " tag:" . $_SERVER['customTag']
            . " uri:" . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')
            . " msg:" . $message;

        $dir = APPLICATION_PATH . '/data/log/sync.log';

        file_put_contents($dir, $logStr . "\r\n", FILE_APPEND);
        self::$_instance = null;
    }
    
    /**
     * 访问日志
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/11/18 20:41
     * @param $message
     */
    public static function accessLog($message)
    {
        $logStr = date("Y-m-d H:i:s")
            . " tag:" . $_SERVER['customTag']
            . " uri:" . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')
            . " msg:" . $message;

        if (Tools::isDevelop())
        {
            $dir = APPLICATION_PATH . '/data/log/access.log';
        }
        else
        {
            $dir = APPLICATION_PATH . '/data/log/access'.'-' . date('Ymd') . '.log';
        }

        file_put_contents($dir, $logStr . "\r\n", FILE_APPEND);
        self::$_instance = null;
    }

    /**
     * 写入日志
     * 
     * @param string $message
     * @param string $cli
     */
    public function write($message, $cli=null) {
        $logStr = date("Y-m-d H:i:s") . substr(microtime(), 1, 4)
            . " tag:" . $_SERVER['customTag']
            . " uri:" . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')
            . " ref:" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')
            . " cli:" . (!empty($cli) ? $cli : '')
            . " msg:" . $message;

        $dir = APPLICATION_PATH . $this->getFileDir();

        $res = file_put_contents($dir, $logStr . "\r\n", FILE_APPEND);
        if ($res === false)
        {
            throw new \Exception('日志添加出错');
        }
    }

    /**
     * 获取打开文件句柄
     * 
     * @return 
     */
    public function getHandle() {
        if (!$this->_handle) {
            $this->_handle = fopen(APPLICATION_PATH . $this->_fileDir[$this->_type], 'a');
        }

        return $this->_handle;
    }

    /**
     * 获取实例
     * 
     * @return \Base\Log
     */
    private static $_instance = null;

    /**
     * 获取实例
     * @param $type string
     * @return \Base\Log
     */
    public static function getInstance($type) {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($type);
        }
        return self::$_instance;
    }

}
