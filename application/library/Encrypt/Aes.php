<?php

namespace Encrypt;

/**
 * AES加密类
 */
class Aes
{
    /**
     * var string $secret_key 加解密的密钥(此处传入base64加密后的值,使用时需要转码)
     */
    protected $_key;
    
    /**
     * var string $method 加解密方法，可通过openssl_get_cipher_methods()获得
     */
    protected $_method;


    /**
     * 构造函数
     * @author likexin
     * @time   2021-03-18 20:35:51
     * @param string $key 加密key
     * @param string $method 模式
     */
    private function __construct($key, $method)
    {
        $this->_key = base64_decode($key);
        $this->_method = $method;
    }

    /**
     * 加密
     * @author likexin
     * @time   2021-03-18 20:35:11
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        return openssl_encrypt($data, $this->_method, $this->_key, 0);
    }

    /**
     * 解密
     * @author likexin
     * @time   2021-03-18 20:35:28
     * @param string $data
     * @return string
     */
    public function decrypt($data) {
        $encrypted = base64_decode($data);
        return openssl_decrypt($encrypted, $this->_method, $this->_key, 1);
    }


    /**
     * 类实例
     * @var \Encrypt\Aes
     */
    private static $_instance = null;

    /**
     * 获取类实例
     * @return \Encrypt\Aes
     */
    public static function getInstance($key, $method = 'AES-128-ECB')
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($key, $method);
        }

        return self::$_instance;
    }
}
