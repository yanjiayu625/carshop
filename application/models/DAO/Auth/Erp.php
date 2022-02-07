<?php

namespace DAO\Auth;

use \Mysql\Common\CommonModel as MysqlCommon;
use \Redis\Common\CommonModel as RedisCommon;

/**
 * ERP系统权限
 */
class ErpModel extends \DAO\AbstractModel
{
    /**
     * 获取网站导航API权限列表
     * @return  json
     */
    public function getErpNav()
    {
        $topList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation',
            ['id', 'path', 'icon', 'value'], ['top_id' => 0], 'position Asc');
        foreach ($topList as $key => $topInfo){
            $secondList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation', ['id',
                'path', 'icon', 'value', 'api_code', 'position'], ['top_id'=>$topInfo['id']], 'position Asc');

            foreach ($secondList as $sKey => $secInfo){
                $thirdList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation', ['id',
                    'path', 'icon', 'value', 'api_code', 'position'], ['top_id'=>$secInfo['id']], 'position Asc');
                foreach ($thirdList as $tKey => $thiInfo){
                    $thirdList[$tKey]['level'] = 3;
                }

                $secondList[$sKey]['level'] = 2;
                $secondList[$sKey]['children'] = $thirdList;
            }

            $topList[$key]['level'] = 1;
            $topList[$key]['children'] = $secondList;
        }

        return $topList;
    }

    /**
     * 获取ERP系统用户导航信息
     * @param string $uid 用户域账号
     * @param string $deptId 用户所在部门ID
     * @return array
     */
    public function getWebNav($uid, $deptId)
    {
        $webString = RedisCommon::getInstance('WebErpNav')->getValue($uid);
        $topList = [];
        if ($webString === false) {
            $navList = $this->_getUserNav($uid, $deptId);
            if(!empty($navList)){
                $topWhere = empty($navList)?['top_id'=>0]:['top_id'=>0,'id'=>array_column($navList,'nav_id')];
                $topList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation',
                    ['id', 'path', 'icon', 'value'], $topWhere, 'position Asc');
                foreach ($topList as $key => $topInfo){
                    $secWhere = empty($navList)?['top_id'=>0]:['top_id'=>$topInfo['id'],'id'=>array_column($navList,'nav_id')];
                    $secList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation', ['id', 'path', 'icon', 'value'],
                        $secWhere, 'position Asc');
                    if(!empty($secList)){
                        foreach ($secList as $sKey => $secInfo){
                            $thrWhere = empty($navList)?['top_id'=>0]:['top_id'=>$secInfo['id'],'id'=>array_column($navList,'nav_id')];
                            $thrList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation', ['id', 'path', 'icon', 'value'],
                                $thrWhere, 'position Asc');
                            if(!empty($thrList)){
                                foreach ($thrList as $tKey => $thrInfo ){
                                    unset($thrList[$tKey]['id']);
                                }
                                $secList[$sKey]['children'] = $thrList;
                            }
                            unset($secList[$sKey]['id']);
                        }
                        $topList[$key]['children'] = $secList;
                    }
                    unset($topList[$key]['id']);
                }
            }

            RedisCommon::getInstance('WebErpNav')->setValue($uid, $topList);

        } else {
            $topList = json_decode($webString, true);
        }

        return $topList;
    }

    /**
     * 获取用户导航ID数组
     * @param string $uid 角色UID
     * @param string $deptId 用户所在部门ID
     * @return array
     */
    private function _getUserNav($uid, $deptId){

        $navList = [];
        $userInfo = MysqlCommon::getInstance()->getInfoByTableName('admin_erp_user', ['id'],
            ['uid'=> $uid, 'status'=> 1]);
        if(!empty($userInfo)){
            $navList = MysqlCommon::getInstance()->getListByTableName('admin_erp_nav_relation',['nav_id'],
                ['type'=>1,'relation_id'=>$userInfo['id']]);
        }else{

            do{
                $setInfo = MysqlCommon::getInstance()->getInfoByTableName('admin_erp_dept', ['id'],
                    ['dept_id' => $deptId, 'status'=>1]);
                if(!empty($setInfo)){
                    $navList = MysqlCommon::getInstance()->getListByTableName('admin_erp_nav_relation',['nav_id'],
                        ['type'=>2,'relation_id'=>$setInfo['id']]);
                    break;
                }

                $parentInfo = MysqlCommon::getInstance()->getInfoByTableName('data_center_department',
                    ['name', 'parent_id'], ['dept_id' => $deptId]);
                if($parentInfo['parent_id'] == ''){
                    break;
                }
                $deptId = $parentInfo['parent_id'];
            }while($deptId != '');

        }
        return $navList;
    }

    /**
     * 重置ERP系统用户导航信息
     * @param string $uid 用户域账号
     * @return array
     */
    public function resetWebNavByUid($uid)
    {
        $apiString = RedisCommon::getInstance('WebErpNav')->getValue($uid);
        if (!empty($apiString)) {
            RedisCommon::getInstance('WebErpNav')->delete($uid);
        }
        return true;
    }

    /**
     * 重置ERP系统用户导航信息
     * @param string $dId 部门表Id
     * @return array
     */
    public function resetWebNavByDept($dId)
    {
        $deptInfo = MysqlCommon::getInstance()->getInfoByTableName('admin_erp_dept', ['dept_id'],
            ['id' => $dId]);

        $allUidArr = $this->_getDeptUid($deptInfo['dept_id']);

        foreach ($allUidArr as $uid){
            $apiString = RedisCommon::getInstance('WebErpNav')->getValue($uid);
            if (!empty($apiString)) {
                RedisCommon::getInstance('WebErpNav')->delete($uid);
            }
        }

        return true;
    }

    /**
     * 递归获取部门下的所有人员
     * @param string $deptId 部门Id
     * @return array
     */
    private function _getDeptUid($deptId){

        $uidArr = [];

        $userList = MysqlCommon::getInstance()->getListByTableName('data_center_all_user',
            ['uid'], ['dept_id' => $deptId, 'descr'=>'在职']);
        if(!empty($userList)){
            $uidArr = array_column($userList, 'uid');
        }

        $deptList = MysqlCommon::getInstance()->getListByTableName('data_center_department',
            ['dept_id'], ['parent_id' => $deptId]);
        if(!empty($deptList)){
            foreach ($deptList as $deptInfo){
                if(!in_array($deptInfo['dept_id'], ['D000815', 'D002075','D999999','D002782'])){
                    $uidArr = array_merge($uidArr, $this->_getDeptUid($deptInfo['dept_id']));
                }
            }
        }

        return $uidArr;
    }

    /**
     * 获取ERP系统用户API信息
     * @param string $uid 用户域账号
     * @param string $deptId 用户所在部门ID
     * @return array
     */
    public function getErpUserApi($uid, $deptId)
    {
        $apiString = RedisCommon::getInstance('WebErpUserApi')->getValue($uid);

        if ($apiString !== false) {
            $apiList = [];

            $navList = $this->_getUserNav($uid, $deptId);

            $topWhere = empty($navList)?['top_id'=>0]:['top_id'=>0,'id'=>array_column($navList,'nav_id')];
            $apiArr = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation', ['id', 'api_code'],$topWhere);
            foreach ($apiArr as $key => $apiInfo) {
                $secWhere = empty($navList)?['top_id'=>$apiInfo['id']]:['top_id'=>$apiInfo['id'],'id'=>array_column($navList,'nav_id')];
                $secList = MysqlCommon::getInstance()->getListByTableName('admin_erp_navigation', ['api_code'], $secWhere);
                foreach ($secList as $secInfo) {
                    $apiList[] = $apiInfo['api_code'] . '_' . $secInfo['api_code'];
                }
            }

            RedisCommon::getInstance('WebErpUserApi')->setValue($uid, $apiList);
        } else {
            $apiList = json_decode($apiString, true);
        }

        return $apiList;
    }


    /**
     * 重置ERP系统用户API权限
     * @param string $uid 用户域账号
     * @return array
     */
    public function resetErpUserApi($uid)
    {
        $apiString = RedisCommon::getInstance('WebErpUserApi')->getValue($uid);
        if (!empty($apiString)) {
            return (boolean)RedisCommon::getInstance('WebErpUserApi')->delete($uid);
        }
        return true;
    }

    /**
     * 重置ERP系统所有用户
     * @return array
     */
    public function resetWebErpALLUser()
    {
        $keysArr = RedisCommon::getInstance('WebErpNav')->keys('');
        if(!empty($keysArr)){
            foreach ($keysArr as  $uid){
                RedisCommon::getInstance('WebErpNav')->delete($uid);
            }
        }
    }

    /**
     * 单例模式获取类实例
     * @return \DAO\Auth\ErpModel
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
