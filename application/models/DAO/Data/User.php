<?php

namespace DAO\Data;

use Mysql\Common\CommonModel;
use \Redis\Common\CommonModel as RedisCommon;
use \ExtInterface\Erp\ApiModel as ExtErpApi;

    /**
     * 用户信息
     */
class UserModel extends \DAO\AbstractModel {

    /**
     * 获取用户基本信息
     * @param $uid string 域账号
     * @return array
     */
    public function getUserInfo($uid) {
        $userInfo = RedisCommon::getInstance('UserBasicInfo')->getHashAllValue($uid);
        if (empty($userInfo)){
            $humanInfo = ExtErpApi::getInstance()->getHumanInfo($uid);
            if (empty($humanInfo) && $humanInfo['code'] != 200)
            {
                throw new \Exception('ERP响应错误：无法获取用户信息');
            }

            $userInfo['joindate'] = $humanInfo['data']['joindate'];
            $userInfo['email'] = $humanInfo['data']['email'];
            $userInfo['objname'] = $humanInfo['data']['objname'];

            RedisCommon::getInstance('UserBasicInfo')->setHashValue($uid,$userInfo);
        }
        return $userInfo;
    }

    /**
     * 获取入职时间
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/1/14 10:33
     * @param $uid
     * @return mixed
     * @throws \Exception
     */
    public function getHireDate($uid)
    {
        $hireDate = RedisCommon::getInstance('Hire')->hget('Date', $uid);
        if (empty($hireDate))
        {
            $hireInfo = ExtErpApi::getInstance()->getHireDate($uid);
            if (empty($hireInfo) && $hireInfo['code'] != 200)
            {
                throw new \Exception('ERP响应错误：获取入职信息失败');
            }

            $hireDate = $hireInfo['data'];
            RedisCommon::getInstance('Hire')->hset('date', $uid, $hireDate);
        }

        return $hireDate;
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/18 6:17 下午
     */
    public function getODUidList()
    {
        $uidList = CommonModel::getInstance()->getListByTableName('perf_role_od',
            ['*'], NULL);

        return array_column($uidList, 'uid');
    }
    
    /**
     * 单例模式获取类实例
     * @return \DAO\Admin\UserModel
     */
    private static $_instance = null;

    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

}
