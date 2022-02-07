<?php

namespace Base;

use Exception;
use Redis\Common\CommonModel as RedisCommon;
use Tools\Tools;
use Yaf\Registry;
use function EasyWeChat\Kernel\Support\get_server_ip;

/**
 * @method static mysqlLog(false|string $json_encode)
 * @method static msgLog(false|string $json_encode)
 * @method static apiLog(false|string $json_encode)
 * @method static cliLog(false|string $json_encode)
 * @method static accessLog(false|string $json_encode)
 * @method static curlLog(false|string $json_encode)
 * @method static travelLog(false|string $json_encode)
 * @method static erpLog(false|string $json_encode)
 * @method static authLog(false|string $json_encode)
 * @method static errorLog(false|string $json_encode)
 * @method static meetingLog(false|string $json_encode)
 *
 * @author tanghan <tanghan@ifeng.com>
 * @time 2020/1/6 16:39
 * Class NewLog
 * @package Base
 */
class NewLog
{
    private static $_logKey = 'expense_logs';

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/4/25 11:29
     * @return string
     */
    private static function logDir()
    {
        $dir = Registry::get('config')->get('log.dir');

        if (empty($dir)) $dir = '/data/logs/php/';

        if (Tools::isDevelop()) $dir = APPLICATION_PATH . "/data/log/";

        return $dir;
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/4/25 14:00
     * @throws Exception
     */
    public static function consumer()
    {
        while (true) {
            $logs = RedisCommon::getInstance('')->brpop(self::$_logKey, 2);

            if (empty($logs)) continue;

            self::writer($logs[1]);
        }
    }

    /**
     * 写入redis消息队列
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/24 17:20
     * @param $type
     * @param $message
     * @param $cli
     * @return bool
     */
    public static function redisLog($type, $message, $cli=null)
    {
        $logStr = date("Y-m-d H:i:s") . substr(microtime(), 1, 4)
            . " server_ip:" . gethostbyname(gethostname())
            . " type:" . $type
            . " tag:" . $_SERVER['customTag']
            . " uri:" . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')
            . " ref:" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')
            . " cli:" . (!empty($cli) ? $cli : '')
            . " msg:" . $message . "\n";

        $logStr = json_encode([
            'type' => $type,
            'message' => $logStr
        ]);

        // 开发环境直接写入文件
        if (Tools::isDevelop())
        {
            self::writer($logStr);
            return true;
        }

        $res = RedisCommon::getInstance('')->lpush(self::$_logKey, $logStr);

        return (bool)$res;
    }

    /**
     * 从redis写入文件
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/25 13:49
     * @param $type
     * @param $message
     */
    public static function writer($message)
    {
        $msgArr = json_decode($message, true);

        $type = $msgArr['type'];
        $message = $msgArr['message'];

        $dir = self::logDir();

        if (!file_exists($dir))
        {
            mkdir($dir, 0700, true);
        }

        if (Tools::isDevelop())
        {
            $filePath = $dir . "{$type}.log";
        }
        else
        {
            $filePath = $dir . date('Ymd') . '.log';
        }

        file_put_contents($filePath, $message, FILE_APPEND);
    }

    /**
     * 魔术方法
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/27 16:48
     * @param $func
     * @param array $argv
     * @return bool|mixed
     */
    public static function __callStatic($func, array $argv)
    {
        $prefixLog = 'Log';
        $prefixWriter = 'Writer';

        if (strpos($func, $prefixWriter) !== false)
        {
            $pros = array_filter(explode($prefixWriter, $func))[0];
            $staticFunc = 'writer';
        }

        if (strpos($func, $prefixLog) !== false)
        {
            $pros = array_filter(explode($prefixLog, $func))[0];
            $staticFunc = 'redisLog';
        }

        if (empty($pros)) return false;

        array_unshift($argv, $pros);

        return call_user_func_array('self::'.$staticFunc, $argv);
    }
}