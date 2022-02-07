<?php

namespace Mysql\Common;

use \Mysql\Super\DBTableModel as MysqlSuperTable;
use \Mysql\Super\TColumnModel as MysqlSuperColumn;
use \Mysql\Super\DBTypeModel as MysqlSuperType;
use \Business\Dao\DataLogicModel as DaoDataLogic;
use Tools\Tools;

/**
 * 通用数据库操作类
 */
class CommonModel extends \Mysql\AbstractModel
{
    protected $_tableName = '';
    /**
     * 添加新表
     * @param $tableName string 表名称
     * @param $action string 操作类型.select,update,del
     * @param $data string 操作数据数据
     * @commit 默认有3个字段,id,c_time,u_time
     * @return string
     */
    public function operateDataTable($tableName,$action,$data)
    {
        $this->_tableName = $tableName;

        switch ( $action ) {
            case 'select':
                $res = $this->query($data);
                break;
            case 'update':
                $res = $this->update($data);

                break;
            case 'delete':
                $res = $this->delete($data);
                break;
            default:
                $res['code'] = 400;
                $res['msg']  = '操作类型错误';
                break;
        }

        return $res;
    }


    /**
     * 通过表名获取数据库表列表
     * @param $table_name string 表名
     * @param $fields array 字段
     * @param $where array 查询条件
     * @param $order string 排序
     * @param $group string
     * @return array
     */
    public function getListByTableName($table_name, $fields, $where, $order = null, $group = null)
    {
        $this->setOperateTable($table_name);
        $query = array(
            'fields' => $fields,
            'where'  => $where,
            'order'  => $order,
            'group'  => $group
        );

        $res =  $this->query($query);
        return $res;
    }

    /**
     * 获取PDOStatement 用于FOREACH
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/6/1 10:36
     * @param $table_name
     * @param $fields
     * @param $where
     * @param null $order
     * @param null $group
     * @return array|\PDOStatement
     */
    public function getListByTableNameTraverse($table_name, $fields, $where, $order = null, $group = null)
    {
        $this->setOperateTable($table_name);
        $query = array(
            'fields' => $fields,
            'where'  => $where,
            'order'  => $order,
            'group'  => $group
        );

        $res =  $this->query($query, null, true);
        $res->setFetchMode(\PDO::FETCH_ASSOC);
        return $res;
    }

    /**
     * 获取数量
     * @param $table_name string
     * @param $where array
     * @return array
     */
    public function getCountByWhere($table_name, $where) {
        $this->setOperateTable($table_name);
        $query = array(
            'where'  => $where,
            'fields'  => 'COUNT'
        );
        $res =  $this->query($query);
        $num = $res[0]['COUNT(id)'];
        return $num;
    }
    /**
     * 获取数据列表,带分页
     * @param $table_name string 表明
     * @param $fields array 字段
     * @param $where array 查询条件
     * @param $order string 排序
     * @param $group string
     * @param $limit limit
     * @return array
     * @author zhangyang7
     */
    public function getListLimitByTableName($table_name, $fields, $where = null, $order = null, $group = null, $limit = null)
    {
        $this->setOperateTable($table_name);
        $query = array(
            'fields' => $fields,
            'where'  => $where,
            'order'  => $order,
            'group'  => $group,
            'limit'  => $limit,
        );

        $res =  $this->query($query);
        return $res;
    }

    /**
     * 获取单个数据表信息
     * @param $table_name string 表名
     * @param $fields array 字段
     * @param $where array 查询条件
     * @param $order string
     * @param $group string
     * @return array
     *
     */
    public function getInfoByTableName($table_name, $fields, $where, $order = null, $group = null)
    {
        $res = array();
        $this->setOperateTable($table_name);
        $query = array(
            'fields' => $fields,
            'where'  => $where,
            'order'  => $order,
            'group'  => $group
        );

        $list =  $this->query($query);

        if (!empty($list)) {
            $res = current($list);
        }
        return $res;
    }

    /**
     * 添加单条数据
     * @param $table_name string 表名
     * @param $data array 批量插入数据
     * @return bool
     *
     */
    public function addInfoByTableName($table_name, $data)
    {
        $this->setOperateTable($table_name);
        return $this->insert($data);
    }

    /**
     * 添加多条数据
     * @param $table_name string 表名
     * @param $data array 批量插入数据
     * @return bool
     *
     */
    public function addMultInfoByTableName($table_name, $data)
    {
        $this->setOperateTable($table_name);
        return $this->insertMult($data);
    }

    /**
     * 添加单条数据
     * @param $table_id int 表id
     * @param $data array 批量插入数据
     * @return bool
     *
     */
    public function addInfoByTableId($table_id, $data)
    {
        $table_name = $this->getTableNameByTableId($table_id);
        return $this->addInfoByTableName($table_name, $data);
    }

    /**
     * 添加多条数据
     * @param $table_id string 表名
     * @param $data array 批量插入数据
     * @return bool
     *
     */
    public function addMultInfoByTableId($table_id, $data)
    {
        $table_name = $this->getTableNameByTableId($table_id);
        return $this->addMultInfoByTableName($table_name, $data);
    }

    /**
     * 更新字段信息
     * @param $table_name string 表名
     * @param $set array 更新内容
     * @param $where array 条件
     * @return array
     */
    public function updateListByTableName($table_name, $set, $where) {
        $this->setOperateTable($table_name);
        $res =  $this->update($set, $where);
        return $res;
    }

    /**
     * 更新字段信息
     * @param $table_id int 表id
     * @param $set array 更新内容
     * @param $where array 条件
     * @return array
     */
    public function updateListByTableId($table_id, $set, $where) {
        $table_name = $this->getTableNameByTableId($table_id);
        $res =  $this->updateListByTableName($table_name, $set, $where);
        return $res;
    }

    /**
     * 删除信息
     * @param $table_name string 表名
     * @param $where array 条件
     * @return array
     */
    public function deleteListByTableName($table_name, $where) {
        $this->setOperateTable($table_name);
        $res = $this->delete($where);
        return $res;
    }

    /**
     * 删除信息
     * @param $table_id int 表id
     * @param $where array 条件
     * @return array
     */
    public function deleteListByTableId($table_id, $where) {
        $table_name = $this->getTableNameByTableId($table_id);
        $res =  $this->deleteListByTableName($table_name, $where);
        return $res;
    }

    /**
     * 删除一列
     * @param $column_name string 列的名字
     * @param $table_name string 表的名字
     * @return array
     */
    public function deleteColumn($column_name, $table_name) {
        $this->setOperateTable($table_name);
        $res =  $this->dropColumn($column_name);
        return $res;
    }

    /**
     * 添加一列
     * @param $column_name string 列的名字
     * @param $data_type string 表类型, 如varchar(255)
     * @param $default string 表默认值, 如''
     * @param $comment string 表注释说明, 如sn号
     * @param $after_column_name string 在哪个字段后面, 如name
     * @param $table_name string 表名, 如cmdb_server
     * @return array
     */
    public function addColumn($column_name, $data_type, $default, $comment, $after_column_name, $table_name) {
        $this->setOperateTable($table_name);
        $res =  $this->insertColumn($column_name, $data_type, $default, $comment, $after_column_name);
        return $res;
    }

    /**
     * 销毁类实例
     */
    function __destruct()
    {
    }

    /**
     * 单例模式
     * @var \Mysql\Common\CommonModel
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
