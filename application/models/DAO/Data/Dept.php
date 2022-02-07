<?php

namespace DAO\Data;

use \Redis\Common\CommonModel as RedisCommon;
use \Mysql\Common\CommonModel as MysqlCommon;

/**
 * 部门列表,员工列表
 */
class DeptModel extends \Bll\AbstractModel
{
    private $_deptType = [
        'All'=>['D000815', 'D000761', 'D002075', 'D999999', 'D002782'],
        'Perf'=>['D000815', 'D000761', 'D002075', 'D999999', 'D002782', 'D000006', 'D002346', 'D002585'],
    ];

    /**
     * 获取一级部门列表
     * @author likexin
     * @time   2021-12-01 16:15:52
     * @return array
     */
    public function getFirstDeptList()
    {
        $deptWhere['parent_id'] = 'D000000';
        $deptWhere['dept_id !='] = 'D999999';

        $deptJson = RedisCommon::getInstance('CommonDeptList')->getValue('First');
        
        if ($deptJson === false) {

            $deptArr = [];
            $firstInfo = MysqlCommon::getInstance()->getListByTableName('data_center_department', ['dept_id', 'name'], $deptWhere);
            foreach ($firstInfo as $first) {
                $deptArr[] = ['dept_id' => $first['dept_id'], 'name' => $first['name']];
            }

            RedisCommon::getInstance('CommonDeptList')->setValue('First', $deptArr);
        } else {
            $deptArr = json_decode($deptJson, true);
        }

        return $deptArr;
    }

    /**
     * 获取部门列表
     * @param $type string
     * @return array
     */
    public function getDeptList($type = 'All')
    {
        $deptWhere['parent_id'] = '';

        $deptJson = RedisCommon::getInstance('CommonDeptList')->getValue($type);
        
        if ($deptJson === false) {

            $deptArr = [];
            $topInfo = MysqlCommon::getInstance()->getInfoByTableName('data_center_department',
                ['dept_id', 'name'], $deptWhere);
            $deptArr['label'] = $topInfo['name'];
            $deptArr['value'] = $topInfo['dept_id'];
            $deptArr['children'] = $this->_childDept($topInfo['dept_id'], $type);

            RedisCommon::getInstance('CommonDeptList')->setValue($type, $deptArr);
        } else {
            $deptArr = json_decode($deptJson, true);
        }

        return $deptArr;
    }

    /**
     * 获取部门
     * @author zhangyang7
     * @param $topId string
     * @param $type string 类型 ALL Perf
     * @return array
     */
    private function _childDept($topId, $type)
    {
        $deptWhere['parent_id'] = $topId;

        $childList = MysqlCommon::getInstance()->getListByTableName('data_center_department',
            ['dept_id', 'name'], $deptWhere, 'dept_id');
        $dataArr = [];
        if (!empty($childList)) {
            foreach ($childList as $childInfo) {
                $data = [];
                $data['label'] = $childInfo['name'];
                $data['value'] = $childInfo['dept_id'];

                if (!in_array($childInfo['dept_id'], $this->_deptType[$type])) {
                    $childrenArr = $this->_childDept($childInfo['dept_id'], $type);
                    if (!empty($childrenArr)) {
                        $data['children'] = $childrenArr;
                    }
                    $dataArr[] = $data;
                }
            }
        }
        return $dataArr;
    }

    /**
     * 获取部门列表
     * @author zhangyang7
     * @param $allUser string ALL:所有,OnJob:在职
     * @return array
     */
    public function getDeptUserList($allUser)
    {
        $deptWhere['parent_id'] = '';
        if ($allUser == 'OnJob') {
            $uidWhere['descr'] = '在职';
        }

        $deptJson = RedisCommon::getInstance('CommonDeptUserList')->getValue($allUser);

        if ($deptJson === false) {

            $deptArr = [];
            $topInfo = MysqlCommon::getInstance()->getInfoByTableName('data_center_department',
                ['dept_id', 'name'], $deptWhere);
            $deptArr['label'] = $topInfo['name'];
            $deptArr['value'] = $topInfo['dept_id'];

            $uidWhere['dept_id'] = $topInfo['dept_id'];
            $uidWhere['uid !='] = '';

            $userList = MysqlCommon::getInstance()->getListByTableName('data_center_all_user',
                ['name', 'uid', 'descr'], $uidWhere);
            $deptArr['users'] = $userList;
            $deptArr['children'] = $this->_childrenDept($allUser, $topInfo['dept_id'], true);

            RedisCommon::getInstance('CommonDeptUserList')->setValue($allUser, $deptArr);
        } else {
            $deptArr = json_decode($deptJson, true);
        }

        return $deptArr;
    }

    /**
     * 获取部门和部门员工的贵函数
     * @author zhangyang7
     * @param $allUser string
     * @param $topId string
     * @param $hasUser bool 是否有用户
     * @return array
     */
    private function _childrenDept($allUser, $topId, $hasUser)
    {

        $deptWhere['parent_id'] = $topId;

        $childList = MysqlCommon::getInstance()->getListByTableName('data_center_department',
            ['dept_id', 'name'], $deptWhere, 'dept_id');
        $dataArr = [];
        if (!empty($childList)) {
            foreach ($childList as $childInfo) {
                $data = [];
                $data['label'] = $childInfo['name'];
                $data['value'] = $childInfo['dept_id'];
                if ($hasUser) {
                    if ($allUser == 'OnJob') {
                        $uidWhere['descr'] = '在职';
                    }
                    $uidWhere['dept_id'] = $childInfo['dept_id'];
                    $uidWhere['uid !='] = '';

                    $userList = MysqlCommon::getInstance()->getListByTableName('data_center_all_user',
                        ['name', 'uid', 'descr'], $uidWhere);
                    $data['users'] = $userList;
                }
                if (!in_array($childInfo['dept_id'], ['D000815', 'D000761', 'D002075', 'D999999', 'D002782'])) {
                    $childrenArr = $this->_childrenDept($allUser, $childInfo['dept_id'], $hasUser);
                    if (!empty($childrenArr)) {
                        $data['children'] = $childrenArr;
                    }
                    $dataArr[] = $data;
                }
            }
        }
        return $dataArr;
    }
    

    /**
     * 单例模式获取类实例
     * @return \DAO\Data\DepartmentModel
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
