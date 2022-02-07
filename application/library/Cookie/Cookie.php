<?php
namespace Cookie;

class Cookie
{
	private $_env = null;
	private $_conf = [
		'product' => ['name'=>'WebCookie','domain'=>'erp.ifeng.com','path'=>'/'],
		'test' => ['name'=>'WebCookie','domain'=>'erp.staff.ifeng.com','path'=>'/'],
	];

	public function __construct()
	{
		$this->_env = ini_get('yaf.environ');
	}

	//设置Cookie
	public function setcookie($hour)
	{


		if (is_numeric($hour)) {
			$value = $this->_generateKey();
			if( $hour !== 0 ){
				$expire = time() + $hour * 3600;
			}else{
				$expire = time() - 3600;
			}
		
			if( setcookie($this->_conf[$this->_env]['name'], $value, $expire, $this->_conf[$this->_env]['path'],
				$this->_conf[$this->_env]['domain']) ){
				return $value;
			}
		} else {
			throw new \Exception("Hour小时必须为数字");
		}
	}

	//获取Cookie的值
	public function getcookie()
	{
		if (!empty($_COOKIE[$this->_conf[$this->_env]['name']])) {
			return $_COOKIE[$this->_conf[$this->_env]['name']];
		} else {
			return null;
		}
	}

	//删除Cookie
	public function delcookie()
	{
		setcookie($this->_conf[$this->_env]['name'],'',time()-1, $this->_conf[$this->_env]['path'],
			$this->_conf[$this->_env]['domain']);
	}


	//生成随机KEY
	private function _generateKey()
	{
		$param = 22;
		$str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$key = "";
		for ($i = 0; $i < $param; $i++) {
			$key .= $str{mt_rand(0, 61)};
		}
		$key .= strtotime(date("Y-m-d H:i:s", time()));
		return md5($key);
	}

	/**
	 * 类实例
	 * @var \Cookie\Cookie
	 */
	private static $_instance = null;

	/**
	 * 获取类实例
	 * @return \Cookie\Cookie
	 */
	public static function getInstance() {
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __destruct()
	{
	}

}
