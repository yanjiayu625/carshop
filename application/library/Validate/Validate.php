<?php
namespace Validate;

use \Tools\Tools;

class Validate {

	//通用方式
	public static function regex($regex, $string) {
		return preg_match($regex, $string);
	}

	//小写字符
	public static function isLowerLetter($string) {
		return preg_match("/^[a-z]+$/", $string);
	}
	//小写字符
	public static function isLowerLetterAndUnderline($string) {
		return preg_match("/^[a-z_]+$/", $string);
	}
	//字母数字横线点
	public static function isLetterNumberLinePoint($string) {
		return preg_match('/^[a-zA-Z0-9\-.]+$/', $string);
	}
	//大写字符
	public static function isUpperLetter($string) {
		return preg_match("/^[A-Z]+$/", $string);
	}
	//字符
	public static function isLetter($string) {
		return preg_match("/^[A-Za-z]+$/", $string);
	}
	//字符和斜线
	public static function isLetterAndLine($string) {
		return preg_match('/^[A-Za-z\/]+$/', $string);
	}
	//数字
	public static function isNumber($string) {
		return preg_match("/^[0-9]+$/", $string);
	}
	//字母和数字
	public static function isLetterAndNumber($string) {
		return preg_match("/^[A-Za-z0-9]+$/", $string);
	}

	//数值
	public static function isNumerical($string) {
		return preg_match( "-[0-9]+(.[0-9]+)?|[0-9]+(.[0-9]+)?", $string);
	}
	//是否为IP
	public static function isIp($string) {
		return ip2long($string);
	}


	//邮件
	public static function isEmail($email) {
		return preg_match('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z0-9]+$/ui', $email);
	}

	public static function isMobile($mobile) {
		return preg_match('/^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1})|(17[0-9]{1})|145|147)+\d{8})$/', $mobile);
	}

	public static function isChinese($string) {
		return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $string);
	}

	public static function isCnEnNb($string) {
		return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z_0-9\(\)\s]+$/u', $string);
	}

	public static function isMd5($md5){
		return preg_match('/^[a-f0-9A-F]{32}$/', $md5);
	}

	public static function isSha1($sha1) {
		return preg_match('/^[a-fA-F0-9]{40}$/', $sha1);
	}

	public static function isToken($token) {
		return preg_match('/^[a-zA-Z0-9=]+$/', $token);
	}

	public static function isFloat($float) {
		return strval((float)$float) == strval($float);
	}

	public static function isUnsignedFloat($float) {
		return strval((float)$float) == strval($float) && $float >= 0;
	}

	public static function isOptFloat($float) {
		return empty($float) || Validate::isFloat($float);
	}

	public static function isAlias($alias) {
		return preg_match('/^[a-zA-Z-]{4-12}$/u', $alias);
	}

	public static function isPrice($price) {
		return preg_match('/^[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
	}

	public static function isNegativePrice($price) {
		return preg_match('/^[-]?[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
	}

	public static function isSearch($search) {
		return preg_match('/^[^<>;=#{}]{1,64}$/u', $search);
	}

	public static function isMessage($message) {
		return !empty($message) && !preg_match('/[<>{}]/i', $message);
	}

	public static function isCleanHtml($html) {
		$events = 'onmousedown|onmousemove|onmmouseup|onmouseover|onmouseout|onload|onunload|onfocus|onblur|onchange';
		$events .= '|onsubmit|ondblclick|onclick|onkeydown|onkeyup|onkeypress|onmouseenter|onmouseleave|onerror|onselect|onreset|onabort|ondragdrop|onresize|onactivate|onafterprint|onmoveend';
		$events .= '|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onmove';
		$events .= '|onbounce|oncellchange|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavailable|ondatasetchanged|ondatasetcomplete|ondeactivate|ondrag|ondragend|ondragenter|onmousewheel';
		$events .= '|ondragleave|ondragover|ondragstart|ondrop|onerrorupdate|onfilterchange|onfinish|onfocusin|onfocusout|onhashchange|onhelp|oninput|onlosecapture|onmessage|onmouseup|onmovestart';
		$events .= '|onoffline|ononline|onpaste|onpropertychange|onreadystatechange|onresizeend|onresizestart|onrowenter|onrowexit|onrowsdelete|onrowsinserted|onscroll|onsearch|onselectionchange';
		$events .= '|onselectstart|onstart|onstop';

		return (!preg_match('/<[ \t\n]*script/ims', $html) && !preg_match('/(' . $events . ')[ \t\n]*=/ims', $html) && !preg_match('/.*script\:/ims', $html) && !preg_match('/<[ \t\n]*i?frame/ims', $html));
	}

	public static function isPasswd($passwd, $size = 6) {
		return preg_match('/^[.a-z_0-9-!@#$%\^&*()]{' . $size . ',32}$/ui', $passwd);
	}

	public static function isDateFormat($date) {
		return (bool)preg_match('/^([0-9]{4})-((0?[0-9])|(1[0-2]))-((0?[0-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date);
	}

	public static function isDate($date) {
		if (!preg_match('/^([0-9]{4})-((0?[1-9])|(1[0-2]))-((0?[1-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/ui', $date, $matches))
			return false;

		return checkdate(intval($matches[2]), intval($matches[5]), intval($matches[0]));
	}

	public static function isTimestamp($time) {
		//return ctype_digit($time) && $time <= 2147483647;
		return (int)$time > 0 && strtotime(date('Y-m-d H:i:s', $time)) === (int)$time;
	}

	public static function isBirthDate($date) {
		if (empty($date) || $date == '0000-00-00')
			return true;
		if (preg_match('/^([0-9]{4})-((?:0?[1-9])|(?:1[0-2]))-((?:0?[1-9])|(?:[1-2][0-9])|(?:3[01]))([0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $birth_date))
		{
			if ($birth_date[1] > date('Y') && $birth_date[2] > date('m') && $birth_date[3] > date('d'))
				return false;

			return true;
		}

		return false;
	}

	public static function isBool($bool) {
		return $bool === null || is_bool($bool) || preg_match('/^0|1$/', $bool);
	}

	public static function isOrderWay($way) {
		return ($way === 'ASC' | $way === 'DESC' | $way === 'asc' | $way === 'desc');
	}

	public static function isInt($value) {
		return ((string)(int)$value === (string)$value || $value === false);
	}

	public static function isUnsignedInt($value) {
		return (preg_match('#^[0-9]+$#', (string)$value) && $value < 4294967296 && $value >= 0);
	}

	public static function isPercentage($value) {
		return (Validate::isFloat($value) && $value >= 0 && $value <= 100);
	}

	public static function isUnsignedId($id) {
		return Validate::isUnsignedInt($id); /* Because an id could be equal to zero when there is no association */
	}

	public static function isNullOrUnsignedId($id) {
		return $id === null || Validate::isUnsignedId($id);
	}

	public static function isLoadedObject($object) {
		return is_object($object) && $object->id;
	}

	public static function isUrl($url) {
		return preg_match('/^[~:#,%&_=\(\)\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
	}

	public static function isUrlOrEmpty($url) {
		return empty($url) || self::isUrl($url);
	}

	public static function isEmpty($string) {
		return empty($string);
	}

	public static function isAbsoluteUrl($url) {
		return preg_match('/^https?:\/\/[!,:#%&_=\(\)\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
	}

	public static function isMySQLEngine($engine) {
		return (in_array($engine, array('InnoDB', 'MyISAM')));
	}

	public static function isUnixName($data) {
		return preg_match('/^[a-z0-9\._-]+$/ui', $data);
	}

	public static function isFileName($name) {
		return preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
	}

	public static function isDirName($dir) {
		return self::isFileName($dir);
	}

	public static function isCookie($data) {
		return (is_object($data) && (get_class($data) == 'Cookie' && get_class($data) == 'CookieModel'));
	}

	public static function isOptUnsignedId($id) {
		return is_null($id) OR self::isUnsignedId($id);
	}

	public static function isString($data) {
		return !empty($data) && is_string($data);
	}

	public static function isSerializedArray($data) {
		return $data === null || (is_string($data) && preg_match('/^a:[0-9]+:{.*;}$/s', $data));
	}

	public static function isIpAddress($data) {
		$ary = explode('.', $data);
		if (!preg_match('/[^\.\d]/', $data) && count($ary) == 4 && $ary[0] >= 0 && $ary[1] >= 0 && $ary[2] >= 0 && $ary[3] >= 0 && $ary[0] <= 255 && $ary[1] <= 255 && $ary[2] <= 255 && $ary[3] <= 255
		)
			return true;
		else
			return false;
	}

	public static function isIMEI($data) {
		return preg_match('/^[0-9a-z]{15}$/i', $data);
	}

	public static function isISBN($isbn) {
		return preg_match('/^[0-9]{13}$/', $isbn);
	}

	public static function isPublishTime($time) {
		return preg_match('/^[0-9]{4}-[0-9]{2}$/', $time);
	}

	public static function isNickname($data) {
		return preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z_]{2,16}$/u', $data);
	}

	public static function isOptNickname($data) {
		if ($data == null || self::isNickname($data))
		{
			return true;
		}

		return false;
	}

}