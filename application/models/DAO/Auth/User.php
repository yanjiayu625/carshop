<?php

namespace DAO\Auth;

use DAO\AbstractModel;
use Mysql\Common\CommonModel as MysqlCommon;

class UserModel extends AbstractModel
{

    /**
     * 获取用户职能信息
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/25 17:28
     * @param $uid
     * @return array
     */
    public function getUserRole($uid)
    {
        $sql = "SELECT * FROM `admin_user` LEFT JOIN `admin_user_role` 
                ON `admin_user`.`id` = `admin_user_role`.`user_id` 
                WHERE `admin_user`.`uid` = '{$uid}'";

        $userInfo = MysqlCommon::getInstance()->querySQL($sql, []);
        return head($userInfo);
    }

    /**
     * 获取用户导航信息
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/26 11:10
     * @param $role
     * @return array
     */
    public function getRoleNavs($role)
    {
        $sql = "SELECT * FROM `admin_navigation` 
                WHERE `admin_navigation`.`id` IN 
                (SELECT  `nav_id` FROM `admin_role_nav`
                where `admin_role_nav`.`role_id` = '{$role}')";
        $userInfo = MysqlCommon::getInstance()->querySQL($sql, []);

        return $userInfo;
    }

    /**
     * 检测用户是否有对应code接口的权限
     * @author tanghan <tanghan@ifeng.com>
     * @time 2019/12/26 11:02
     * @param $uid
     * @param $code
     * @return bool
     */
    public function hasNavPermission($uid, $code)
    {
        $roleInfo = $this->getUserRole($uid);
        $roleId = $roleInfo['role_id'];
        $navInfo = $this->getRoleNavs($roleId);

        return in_array($code, array_column($navInfo, 'web_code'));
    }

    private static $_instance = null;

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function __construct(){}

    private function __wakeup(){}

    private function __clone(){}
}