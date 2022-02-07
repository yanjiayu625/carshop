<?php

namespace DAO\Auth;

use DAO\AbstractModel;
use \Mysql\Common\CommonModel as MysqlCommon;
use \Redis\Common\CommonModel as RedisCommon;

/**
 * 系统管理用户模块数据处理层
 */
class RoleModel extends AbstractModel
{

    /**
     * 获取角色导航信息
     * @param string $id 角色ID
     * @return array
     */
    public function getRoleNav($id)
    {

        $navString = RedisCommon::getInstance('WebRoleNav')->get($id);
        if (empty($navString)) {
            $navIdList = MysqlCommon::getInstance()->getListByTableName('admin_role_nav', ['nav_id'], ['role_id' => $id]);
            $navArr = array_column($navIdList, 'nav_id');

            $navList = MysqlCommon::getInstance()->getListByTableName('admin_navigation', ['id',
                'web_code', 'web_name'], ['top_id' => 0, 'id' => $navArr], 'position Asc');
            foreach ($navList as $key => $navInfo) {
                $list = MysqlCommon::getInstance()->getListByTableName('admin_navigation', ['id',
                    'web_code', 'web_name'], ['top_id' => $navInfo['id'], 'id' => $navArr], 'position Asc');

                $topList[$key]['list'] = $list;
            }
            RedisCommon::getInstance('WebRoleNav')->set($id, json_encode($navList));
        } else {
            $navList = json_decode($navString, true);
        }

        return $navList;
    }

    /**
     * 获取角色API信息
     * @param string $roleId 角色ID
     * @return array
     */
    private function _getRoleApi($roleId)
    {

        $apiIdList = MysqlCommon::getInstance()->getListByTableName('admin_role_nav', ['nav_id'], ['role_id' => $roleId]);
        $apiIdArr = array_column($apiIdList, 'nav_id');

        $apiList = [];

        $apiArr = MysqlCommon::getInstance()->getListByTableName('admin_navigation', ['id', 'api_code'],
            ['top_id' => 0, 'id' => $apiIdArr]);
        foreach ($apiArr as $key => $apiInfo) {
            $secList = MysqlCommon::getInstance()->getListByTableName('admin_navigation', ['api_code'],
                ['top_id' => $apiInfo['id'], 'id' => $apiIdArr]);
            foreach ($secList as $secInfo) {
                $apiList[] = $apiInfo['api_code'] . '_' . $secInfo['api_code'];
            }
        }

        return $apiList;
    }

    /**
     * 获取用户API信息
     * @param string $uid 用户域账号
     * @return array
     */
    public function getUserApi($uid)
    {
        $apiString = RedisCommon::getInstance('WebUserApi')->getValue($uid);
        if ($apiString === false) {
            $userInfo = MysqlCommon::getInstance()->getInfoByTableName('admin_user', ['id'], ['uid' => $uid]);
            if (!empty($userInfo)) {
                $roleList = MysqlCommon::getInstance()->getListByTableName('admin_user_role', ['role_id'],
                    ['user_id' => $userInfo['id']]);
                $roleIdArr = array_column($roleList, 'role_id');
            } else {
                $roleIdArr = [2]; //普通用户
            }
            $apiArr = [];
            foreach ($roleIdArr as $roleId) {
                $apiArr = array_merge($apiArr, $this->_getRoleApi($roleId));
            }
            RedisCommon::getInstance('WebUserApi')->setValue($uid, $apiArr);
        } else {
            $apiArr = json_decode($apiString, true);
        }

        return $apiArr;
    }

    /**
     * 重置用户API权限
     * @param string $uid 用户域账号
     * @return array
     */
    public function resetUserApi($uid)
    {
        $apiString = RedisCommon::getInstance('WebUserApi')->getValue($uid);
        if (!empty($apiString)) {
            return (boolean)RedisCommon::getInstance('WebUserApi')->delete($uid);
        }
        return true;
    }

    /**
     * 重置角色的所有用户API权限
     * @param string $roleId 角色ID
     * @return array
     */
    public function resetRoleApi($roleId)
    {
        $userIdList = MysqlCommon::getInstance()->getListByTableName('admin_user_role', ['user_id'], ['role_id' => $roleId]);
        if (!empty($userIdList)) {
            foreach ($userIdList as $userInfo) {
                $uidInfo = MysqlCommon::getInstance()->getInfoByTableName('admin_user', ['uid'], ['id' => $userInfo['user_id']]);
                if (!empty($uidInfo['uid'])) {
                    $apiString = RedisCommon::getInstance('WebUserApi')->getValue($uidInfo['uid']);
                    if (!empty($apiString)) {
                        RedisCommon::getInstance('WebUserApi')->delete($uidInfo['uid']);
                    }
                }
            }
        }
    }

    /**
     * 修改导航时,成功所有用户API权限
     * @param string $navId 导航ID
     * @return array
     */
    public function resetNavApi($navId)
    {
        $roleIdList = MysqlCommon::getInstance()->getListByTableName('admin_role_nav', ['role_id'], ['nav_id' => $navId]);
        if (!empty($roleIdList)) {
            foreach ($roleIdList as $roleInfo) {
                self::resetRoleApi($roleInfo['role_id']);
            }
        }
    }

    /**
     * 单例模式获取类实例
     * @return \DAO\Auth\RoleModel
     */
    private static $_instance = null;

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

}
