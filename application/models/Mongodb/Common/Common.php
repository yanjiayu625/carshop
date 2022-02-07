<?php

namespace Mongodb\Issue;

    /**
     * 提案页面模板
     */
class CommonModel extends \Mongodb\AbstractModel {

    /**
     * 表名
     * @param string $collName
     * @param array $data
     * @return array $res
     */
    public function addData($collName, $data) {
        $res =  $this->insert($collName,$data);
        return $res;
    }

    public function getList($collName, $filter, $projection, array $writeOps = [], array $sort = []) {
        $res =  $this->query($collName, $filter, $projection, $writeOps, $sort);
        return $res;
    }

    public function getListTraverse($collName, $filter, $projection, array $writeOps = [], array $sort = [])
    {
        $res =  $this->query($collName, $filter, $projection, $writeOps, $sort, true);
        return $res;

    }

    public function getOne($collName, $filter, $projection, array $writeOps = [], array $sort = []) {
        $one = [];
        $res =  $this->query($collName, $filter, $projection, $writeOps, $sort);
        if(!empty($res)){
            $one = $res[0];
        }
        return $one;
    }

    public function updateData($collName, $where, $set , $upsert = false , $multi = true,  array $writeOps = []) {
        $update = [["q" => $where, "u" => ['$set' => $set], "upsert" => $upsert, "multi" => $multi]];
        $res =  $this->update($collName, $update, $writeOps);
        return $res;
    }

    public function delData($collName, $deletes, array $writeOps = []) {
        $res =  $this->del($collName, $deletes, $writeOps);
        return $res;
    }

    //获取分页参数
    public function getPage($pageNum, $pageSize) {
        $page  = empty($pageNum) ? 1 : $pageNum;
        $limit = empty($pageSize) ? 10 : $pageSize;
        return ['skip' => (int) ($page - 1) * $limit, 'limit' => (int) $limit];
    }

    /**
     * 销毁类实例
     */
    function __destruct()
    {
    }

    /**
     * 单例模式
     * @var \Mongodb\Issue\CommonModel
     */
    private static $_instance = null;

    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

}
