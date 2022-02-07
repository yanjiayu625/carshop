<?php
namespace DAO\Data;

use DAO\AbstractModel;
use Mysql\Common\CommonModel as MysqlCommon;

class incomeModel extends AbstractModel
{
    /**
     * 根据uid获取申请部门
     * @param
     * @return array|bool|string@author wangxf3 <wangxf3@ifeng.com>
     * @time 2019/11/1
     */
    public function getDeptData($where)
    {
        $deptList = MysqlCommon::getInstance()->getListByTableName('mandate_dept',
            ['relation_dept'], $where);
        return $deptList;
    }


    /**
     * 根据uid授权人
     * @param
     * @return array|bool|string@author wangxf3 <wangxf3@ifeng.com>
     * @time 2019/11/1
     */
    public function getUidData($where)
    {
        $uidList = MysqlCommon::getInstance()->getListByTableName('income_dept',
            ['uidList'], $where);
        return $uidList;
    }


    /**
     * 根据uid获取手机号
     * @param
     * @return array|bool|string@author wangxf3 <wangxf3@ifeng.com>
     * @time 2019/11/1
     */
    public function getPhoneData($where)
    {
        $phone = MysqlCommon::getInstance()->getListByTableName('data_center_all_user',
            ['phone'], $where);
        return $phone;
    }


    /**
     * 根据合同编号获取合同版本信息 时间倒叙
     * @param
     * @return array|bool|string@author wangxf3 <wangxf3@ifeng.com>
     * @time 2019/11/1
     */
    public function getVersionData($where)
    {
        $versionHistoryList = MysqlCommon::getInstance()->getListByTableName('legal_document_detail',
            ['*'], $where,'id desc');
        return $versionHistoryList;
    }


    /**
     * 单例模式获取类实例
     * @@author wangxf3 <wangxf3@ifeng.com>
     * @time 2019/11/1
     * @var null
     */
    private static $_instance = null;


    /**
     * 获取单例
     * @@author wangxf3 <wangxf3@ifeng.com>
     * @time 2019/11/1
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}