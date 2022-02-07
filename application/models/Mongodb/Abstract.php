<?php

namespace Mongodb;

use \Base\Log as Log;
/**
 * 数据读取模型抽象类
 *
 * @package Mongodb
 */
abstract class AbstractModel
{
    static $mongo_link;

    static $dbName;

    /**
     * 获取 PDO 连接
     */
    public function getMongoLink()
    {
        if (!self::$mongo_link) {
            $conf = \Yaf\Registry::get('config')->get('mongodb.params');
            if (!$conf) {
                throw new \MongoConnectionException('mongodb数据库必须设置');
            }
            self::$dbName = $conf['db'];
            try {
                if(!empty($conf['rla'])){
                    $server = sprintf("mongodb://%s:%s@%s/%s?replicaSet=%s", $conf['usr'], $conf['pwd'], $conf['host'],
                        $conf['db'], $conf['rla']);
                }else{
                    $server = sprintf("mongodb://%s:%s@%s:%s/%s", $conf['usr'],$conf['pwd'],$conf['host'], $conf['port'], $conf['db']);
                }

                self::$mongo_link = new \MongoDB\Driver\Manager($server);
                
            } catch (\MongoConnectionException $e) {
                echo '<b>Connection failed:</b> ' . $e->getMessage();
                die();
            }

        }
        return self::$mongo_link;
    }


    /**
     * 执行MongoDB命令
     * @param array $param
     * @return \MongoDB\Driver\Cursor
     */
    private function command(array $param) {
        $cmd = new \MongoDB\Driver\Command($param);
        $data =  $this->getMongoLink()->executeCommand(self::$dbName, $cmd);
        return $data->toArray();
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/5/27 16:26
     * @param array $param
     * @return \MongoDB\Driver\Cursor
     * @throws \MongoConnectionException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    private function commandTraverse(array $param) {
        $cmd = new \MongoDB\Driver\Command($param);
        $data =  $this->getMongoLink()->executeCommand(self::$dbName, $cmd);
        return $data;
    }
    /**
     * 插入数据
     * @param  string $collname
     * @param  array  $documents    [["name"=>"values", ...], ...]
     * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
     * @return \MongoDB\Driver\Cursor
     */
    public function insert($collname, array $documents, array $writeOps = []) {
        $cmd = [
            "insert"    => $collname,
            "documents" => $documents,
        ];
        $cmd += $writeOps;

        $res = $this->command($cmd);

        return $res;
    }

    /**
     * 查询
     * @param string $collname
     * @param array $filter [query]
     * @param array $projection [projection ]
     * @param array $writeOps
     * @param array $sort [排序]
     * @param bool $traverse
     * @return array | \MongoDB\Driver\Cursor
     * @throws \MongoConnectionException
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function query($collname, array $filter, array $projection, array $writeOps = [], array $sort = [], bool $traverse = false){
        $cmd = ["find" => $collname];

        if(!empty($filter)){
            $cmd += ['filter' => $filter];
        }
        if(!empty($projection)){
            $cmd += ['projection'=>$projection];
        }
        if(!empty($sort)){
            $cmd += ['sort'=>$sort];
        }
        $cmd += $writeOps;

        $res = $this->commandTraverse($cmd);

        return $traverse ? $res : $res->toArray();
    }

    /**
     * 更新数据
     * @param  string $collname
     * @param  array  $updates      [["q"=>query,"u"=>update,"upsert"=>boolean,"multi"=>boolean], ...]
     * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
     * @return \MongoDB\Driver\Cursor
     */
    public function update($collname, array $updates, array $writeOps = [])
    {
        $cmd = [
            "update" => $collname,
            "updates" => $updates,
        ];
        $cmd += $writeOps;
        return $this->command($cmd);
    }

    /**
     * 获取自增ID
     * @author likexin
     * @time   2020-09-17 11:22:58
     * @param string $collname
     * @param string $typeName
     * @return int
     */
    public function getAutoIncId($collname, $typeName)
    {
        $update = ['$inc' => ['id' => 1]];
        $query = ['name' => $typeName];
        $cmd = [
            'findandmodify' => $collname, 
            'update' => $update,
            'query' => $query, 
            'new' => true, 
            'upsert' => true
        ];

        $res = $this->command($cmd);

        return $res[0]->value->id;
    }

    /**
     * 删除数据
     * @param  string $collname
     * @param  array  $deletes      [["q"=>query,"limit"=>int], ...]
     * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
     * @return \MongoDB\Driver\Cursor
     */
    function del($collname, array $deletes, array $writeOps = []) {
        foreach($deletes as &$_){
            if(isset($_["q"]) && !$_["q"]){
                $_["q"] = (Object)[];
            }
            if(isset($_["limit"]) && !$_["limit"]){
                $_["limit"] = 0;
            }
        }
        $cmd = [
            "delete"    => $collname,
            "deletes"   => $deletes,
        ];
        $cmd += $writeOps;
        return $this->command($cmd);
    }

    
}
