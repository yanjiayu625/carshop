<?php

namespace Mysql;
use \Base\Log as Log;
use Base\NewLog;
use ExtInterface\Msg\WechatModel;
use Tools\Tools;

/**
 * 数据读取模型抽象类
 *
 * @package Mysql
 */
abstract class AbstractModel
{

    protected $_tableName = '';

    static $pdo_link;

    private $sql_value = array();

    /**
     * 获取 PDO 连接
     */
    private function getPdoLink()
    {
        if (!self::$pdo_link) {
            $conf = \Yaf\Registry::get('config')->get('mysql.database.params.');
            if (!$conf) {
                throw new \PDOException('mysql数据库必须设置');
            }
            try {
                self::$pdo_link = new \PDO('mysql:dbname=' . $conf['database'] . ';host=' . $conf['hostname'], $conf['username'], $conf['password']);
                self::$pdo_link->query('SET NAMES ' . $conf['charset']);
            } catch (\PDOException $e) {
                echo '<pre>';
                echo '<b>Connection failed:</b> ' . $e->getMessage();
                die();
            }
        }
        return self::$pdo_link;
    }

    private $_sth;
    private $_sql;
    private $_val;

    function halt()
    {
        $error_info = $this->_sth->errorInfo();
        $s = [
            'Title: <Msql Error>',
            'Tag:' . $_SERVER['customTag'] ?? 'unknown',
            'Env: ' . ini_get('yaf.environ') ?? 'unknown',
            'Errno: '. $error_info[1],
            'Error: ' . $error_info[2],
            'Sql: ' . $this->_sql,
            'Val: ' . json_encode($this->_val)
        ];

        $errStr = implode("\n", $s);
        NewLog::mysqlLog($errStr);
        if (ini_get('yaf.environ') != 'develop') {
            WechatModel::getInstance()->sendWechat("zhangyang7|likx1|tanghan|wangxf3", 'Msql Error', $errStr, '');
        } else {
            var_dump($errStr);
        }
//        Log::getInstance('mysql')->write($s);
        return false;
    }

    function execute($sql, $values = array())
    {
        $this->_sql      = $sql;
        $this->_val      = $values;
        $this->_sth      = $this->getPdoLink()->prepare($sql);
        $bool            = $this->_sth->execute($values);
        $this->sql_value = null;
        if ('00000' !== $this->_sth->errorCode()) {
            return $this->halt();
        }
        return $bool;
    }

    /**
     * 设置事务表明
     * @param $name string
     */
    public function setTransactionTable($name)
    {
        $this->_tableName = $name;
    }

    /**
     * 设置要操作的表
     * @param $name string
     */
    public function setOperateTable($name)
    {
        $this->_tableName = $name;
    }

    /**
     * 开启事务
     * @return bool
     */
    public function beginTransaction()
    {
        $this->getPdoLink()->beginTransaction();
    }

    /**
     * 执行事务
     * @return bool
     */
    public function commitTransaction()
    {
        $this->getPdoLink()->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollbackTransaction()
    {
        $this->getPdoLink()->rollBack();
    }

    /**
     * 数据表操作语句
     * @param $sql string SQL语句
     * @return bool
     */
    public function exeSql($sql)
    {
        return $this->execute($sql);
    }

    /**
     * 删除表中一列
     * @param $column_name
     * @return bool
     */
    public function dropColumn($column_name)
    {
        $sql = 'ALTER TABLE `' . $this->_tableName . '` DROP COLUMN `' . $column_name . '`';
        return $this->execute($sql);
    }

    /**
     * 添加表中一列
     * @param $column_name
     * @return bool
     */
    public function insertColumn($column_name, $data_type, $default = '""', $comment = '""', $after_column_name = '')
    {
        $sql = 'ALTER TABLE `' . $this->_tableName . '` ';
        $sql .= 'ADD COLUMN `' . $column_name . '` ';

        if ($data_type != '') {
            $sql .= $data_type . ' NOT NULL ';
        }

        if($default != '') {
            $sql .= 'DEFAULT ' . $default;
        }

        if ($comment != '') {
            $sql .= ' COMMENT ' . '"' . $comment . '"';
        }

        if ($after_column_name != '') {
            $sql .= ' AFTER `' . $after_column_name . '` ';
        }
        return $this->execute($sql);
    }

    /**
     * 查询数据,直接SQL
     * @param $sql string 查询SQL
     * @param $values array 查询数据
     * @param $fetch_style string 查询类型
     * @return array
     */
    public function querySQL($sql,$values = array(), $fetch_style = \PDO::FETCH_ASSOC)
    {
        $this->execute($sql, $values);
        return $this->_sth->fetchAll($fetch_style);
    }


//    public function getAll($sql, $values = array(), $fetch_style = \PDO::FETCH_ASSOC) {
//        $this->execute($sql, $values);
//        return $this->_sth->fetchAll($fetch_style);
//    }

    /**
     * 查询单表的类 当需要数据量较大时，需要优化分页查询SQL
     * @param $query array 查询SQL
     * @param int $fetch_style
     * @param bool $traverse
     * @return array | \PDOStatement
     */
    public function query($query = array(), $fetch_style = \PDO::FETCH_ASSOC, $traverse = false)
    {
        $sql = $this->_buildQuery($query);
        $this->execute($sql, $this->sql_value);

        return $traverse ? $this->_sth : $this->_sth->fetchAll($fetch_style);
    }

    /**
     * 组合查询条件,生成SQL
     * @param $query array 查询条件
     * @return string
     */
    private function _buildQuery($query = array())
    {
        $query = array_merge(
            array(
                'fields' => null,
                'table' => $this->_tableName,
                'where' => null,
                'group' => null,
                'order' => null,
                'limit' => null
            ),
            (array)$query
        );
        $sql = 'SELECT';
        $sql .= $this->_fields($query['fields']);
        $sql .= 'FROM';
        $sql .= $this->_table($query['table']);
        $sql .= $this->_where($query['where']);
        $sql .= $this->_group($query['group']);
        $sql .= $this->_order($query['order']);
        $sql .= $this->_limit($query['limit']);

        return $sql;
    }

    /**
     * 插入数据,形式为Array,key为字段名,value为值
     * @param $values array 插入数据
     * @return array
     */
    public function insert($values = array())
    {
        $sql = $this->__buildInsert($values);
        if($this->execute($sql, $this->sql_value)){
            return self::$pdo_link->lastInsertId();
        } else {
            return false;
        }
    }

    private function __buildInsert($values)
    {
        if(is_array($values)){
            $keys = array();
            foreach ($values as $key => $val) {
                $keys[] = "`{$key}` = :{$key}" ;
                $this->sql_value[':' . $key] = $val;
            }
            $sql = 'INSERT INTO ';
            $sql .= $this->_table($this->_tableName);
            $sql .= ' SET ';
            $sql .= implode(',',$keys);
            return $sql;
        }else{
            throw new \Exception('Insert语句中values必须为数组');
        }
    }

    /**
     * 插入数据,形式为Array,key为字段名,value为值
     * @param $values array 插入数据
     * @return array
     */
    public function insertMult($values = array())
    {
        $sql = $this->__buildInsertMult($values);
        if ($this->execute($sql, $this->sql_value)){
            return self::$pdo_link->lastInsertId();
        }else{
            return false;
        }
    }

    private function __buildInsertMult($valuesArr)
    {
        if(is_array($valuesArr)){
            $fieldArr = array();
            $valueArr = array();
            $f = false;
            foreach( $valuesArr as $k => $values){
                $keys = array();
                if( is_array($values) ){
                    foreach ($values as $key => $val) {
                        if( !$f ){
                            $fieldArr[] = "`{$key}`";
                        }
                        $keys[] = ":{$key}{$k}" ;
                        $this->sql_value[':' . $key.$k] = $val;
                    }
                    $f = true;
                }else{
                    throw new \Exception('Insert语句中values必须为数组');
                }
                $valueArr[] = "(" . implode(',',$keys) . ")";
            }
            $sql = 'INSERT INTO ';
            $sql .= $this->_table($this->_tableName);
            $sql .= ' (' . implode(',',$fieldArr) . ') ';
            $sql .= ' VALUES ' . implode(',',$valueArr);
            return $sql;
        }else{
            throw new \Exception('Insert语句中values必须为数组');
        }
    }

    /**
     * 修改数据
     * @param $set array 修改数据
     * @param $where array 条件数据
     * @return array
     */
    public function update($set = array(), $where = array())
    {
        $sql = $this->__buildUpdate($set,$where);
        return $this->execute($sql, $this->sql_value);
    }
    private function __buildUpdate($set,$where)
    {
        if(is_array($set) && is_array($where)){
            $sql = "UPDATE ";
            $sql .= $this->_table($this->_tableName);
            $sql .= ' SET ';
            $sql .= $this->_set($set);
            $sql .= $this->_where($where);
            return $sql;
        }else{
            throw new \Exception('Update语句中set和where必须为数组');
        }
    }


    /**
     * 删除数据
     * @param $where array 条件数据
     * @return array
     */
    public function delete($where = array())
    {
        $sql = $this->__buildDelete($where);
        return $this->execute($sql, $this->sql_value);
    }

    private function __buildDelete($where)
    {
        if(is_array($where)){
            $sql = "DELETE FROM  ";
            $sql .= $this->_table($this->_tableName);
            $sql .= $this->_where($where);
            return $sql;
        }else{
            throw new \Exception('Delete语句中where必须为数组');
        }
    }

    /*******************SQL语句处理函数**************************/
    //SQL 查询语句中处理字段函数
    private function _fields($fields)
    {
        if ($fields === null) {
            $fieldStr = ' * ';
        } else {
            if (is_array($fields)) {
                $fieldStr = ' `' . implode('`, `', $fields) . '` ';
            } else {
                if( $fields == 'COUNT' ){
                    $fieldStr = ' COUNT(id) ';
                }else{
                    throw new \Exception('Select语句中fields必须为数组');
                }
            }
        }
        return $fieldStr;
    }

    //SQL语句中处理表名函数
    private function _table($table)
    {
        if ($table === null) {
            throw new \Exception('Select语句中table没有设置');
        } else {
            $tableStr = " `{$table}` ";
        }
        return $tableStr;
    }

    //SQL语句中处理条件函数
    private function _where($where)
    {
        if ($where === null) {
            $whereStr = '';
        } else {
            if (is_array($where)) {
                $whereStr = $this->_whereToString($where);
            } else {
                throw new \Exception('SQL语句中条件数据必须为数组');
            }
        }
        return $whereStr;
    }

    //SQL语句中处理GROUP函数
    private function _group($group)
    {
        if ($group === null) {
            $groupStr = '';
        } else {
            $groupStr = "GROUP BY `{$group}` ";
        }
        return $groupStr;
    }

    //SQL语句中处理ORDER函数
    private function _order($order)
    {
        if ($order === null) {
            $orderStr = '';
        } else {
            $orderStr = "ORDER BY {$order} ";
        }
        return $orderStr;
    }

    //SQL语句中处理LIMIT函数
    private function _limit($limit)
    {
        if ($limit === null) {
            $limitStr = '';
        } else {
            $limitStr = "LIMIT {$limit}";
        }
        return $limitStr;
    }

    //SQL语句中处理SET函数
    private function _set($set)
    {
        foreach ($set as $key => $val) {
            $keys[] = "`{$key}` = :set_{$key}" ;
            $this->sql_value['set_' . $key] = $val;
        }
        return implode(',',$keys);
    }

    //SQL语句中处理WHERE函数
    private function _whereToString($where)
    {
        foreach ($where as $key => $value) {
            if ($key == 'OR') {
                $out[] = array();
            } else {
                $out[] = $this->_parseKey($key, $value);
            }
        }
        return ' WHERE ' . implode(' AND ', $out) . ' ';
    }

    private function _parseKey($key, $value)
    {
        if (strpos($key, ' ') === false) {
            $operator = '=';
        } else {
            list($key, $operator) = explode(' ', trim($key), 2);
        }

        if(!is_array($value)){
            $newKey ='where_'.$key;
            //时间区间
            if( !empty($this->sql_value[$newKey]) ){
                $newKey .= '1';
            }
            $this->sql_value[$newKey] = $value;

            return "`{$key}` {$operator} ".":{$newKey}";
        }else{
            switch ($operator) {
                case '=':
                    $operator = 'IN';
                    break;
                case '!=':
//                case 'like':
//                    $operator = 'like';
//                    break;
                case '<>':
                    $operator = 'NOT IN';
                    break;
            }
            foreach($value as $k => $v){
                $keys[] = ":where_{$key}_{$k}";
                $this->sql_value['where_'.$key.'_'.$k] = $v;
            }

            return "`{$key}` {$operator} (".implode(', ', $keys).")";
        }

    }

}
