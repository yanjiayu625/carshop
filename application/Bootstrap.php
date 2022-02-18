<?php

use Tools\Tools;
use Yaf\Dispatcher;
use Base\NewLog;

/**
 * Bootstrap引导程序
 * 所有在Bootstrap类中定义的, 以_init开头的方法, 都会被依次调用
 * 而这些方法都可以接受一个Yaf_Dispatcher实例作为参数.
 */
class Bootstrap extends \Yaf\Bootstrap_Abstract {
    protected $config;
    /**
     * 把配置存到注册表
     */
    public function _initConfig() {
        $this->config = \Yaf\Application::app()->getConfig();
        \Yaf\Registry::set('config', $this->config);
    }

    /**
     * 初始化错误捕捉
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/5/19 15:59
     */
    public function _initErrorCatch()
    {
        set_error_handler(function($errno, $errStr, $errFile, $errLine, $errContext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false; // 忽略错误 继续执行
            }

            $msg = implode("\n",
                [
                    "Environ: " . ini_get('yaf.environ') ?? 'unknow',
                    "Errno: " . $errno,
//                    'Tag:' . $_SERVER['customTag'] ?? 'unknown',
                    "ErrStr: " . $errStr,
                    "ErrFile: " . $errFile,
                    "ErrLine: " . $errLine,
                    "ErrContext: " . json_encode($errContext),
                ]
            );

            //发送提醒
            if (Tools::isDevelop()) {
                var_dump($msg);
            }

            //记录日志
            NewLog::errorLog('异常错误:' . $msg);

            //抛出异常,停止执行后续代码
            if (Tools::isProduct()) {
                throw new Exception('异常错误');
            }
            return true; //停止执行
        });
    }


    public function _initSMTP()
    {
        define('SMTP_SERVER', $this->config->smtp->server);
        define('SMTP_SSL', $this->config->smtp->ssl);
        define('SMTP_USERNAME', $this->config->smtp->username);
        define('SMTP_PASSWORD', $this->config->smtp->password);
    }

    /**
     * 注册composer
     */
    public function _initAutoload()
    {
        require __DIR__ . "/vendor/autoload.php";
    }

    public function _initPlugin(Dispatcher $dispatcher)
    {
        $user = new UserPlugin();
        $dispatcher->registerPlugin($user);
    }


    public function _initConst()
    {

        if (isset($this->config->storage->params->dir))
        {
            define('STORAGE_DIR', $this->config->storage->params->dir);
        }
        
    }


    /**
     * 将邮件数据保存
     */
    public function _initMailer() {
        if (isset($this->config->smtp))
        {
//            if (isset($this->config->smtp->server))
//            {
//                define('SMTP_SERVER', $this->config->smtp->server);
//            }
//            if (isset($this->config->smtp->ssl))
//            {
//                define('SMTP_SSL', $this->config->smtp->ssl);
//            }
//            if (isset($this->config->smtp->username))
//            {
//                define('SMTP_USERNAME', $this->config->smtp->username);
//            }
//            if (isset($this->config->smtp->password))
//            {
//                define('SMTP_PASSWORD', $this->config->smtp->password);
//            }
//            if (isset($this->config->smtp->helo))
//            {
//                define('SMTP_HELO', $this->config->smtp->helo);
//            }
        }
    }


}
