<?php

namespace DAO\Issue;

use \Tools\Tools;
use \Redis\Issue\MainModel      as RedisIssueMain;
use \Mysql\Common\CommonModel   as MysqlCommon;
use \Mongodb\Issue\CommonModel  as MongoDBIssue;
use \ExtInterface\Iapp\ApiModel as ExtIappApi;

/**
 * 提案节点相关信息处理模块
 */
class MainModel extends \Bll\AbstractModel
{
    /**
     * 获取提案工作流中的节点和连线的输入值key数组
     * @param $id string
     * @return array
     */
    public function getIssueMain($id)
    {
        $issueArr = RedisIssueMain::getInstance()->getAllInfo($id);
        if (empty($issueArr)) {
            $issueArr = $this->_initIssueMain($id);
        }

        return $issueArr;
    }

    /**
     * @param $id
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/2/17 9:01 下午
     * @return bool
     */
    public function flushIssueInfo($id)
    {
        $issueInfo = RedisIssueMain::getInstance()->getAllInfo($id);
        if (!empty($issueInfo)) RedisIssueMain::getInstance()->deleteInfo($id);
        return true;
    }


    /**
     * 初始化提案数据
     * @param $id string
     * @return array
     */
    public function initIssueMain($id)
    {
        $issueArr = RedisIssueMain::getInstance()->getAllInfo($id);
        if (empty($issueArr)) {
            $this->_initIssueMain($id);
        }
    }

    /**
     * 初始化提案内容详情
     * @param $id string
     * @return array
     */
    private function _initIssueMain($id){

        $issueInfo = MongoDBIssue::getInstance()->getOne('issue_info_copy', ['issue_id'=>$id],
            ['_id'=>0]);
        $issueArr = Tools::object2array($issueInfo);
        
        $htmlArr = $issueArr['html'];
        $configArr = $issueArr['config'];
        $tableInfo = $issueArr['table'];

        $issueArr['html'] = json_encode($issueArr['html'], JSON_UNESCAPED_UNICODE);
        $issueArr['config'] = json_encode($issueArr['config'], JSON_UNESCAPED_UNICODE);

        if(!empty($configArr)){
            $confArr = [];
            foreach ($configArr as $k => $vArr){
                foreach ($vArr as $a){
                    $confArr[$k][$a['value']] = $a['text'];
                }
            }
        }
        $tableArr = [];
        foreach ($htmlArr as $html){
            if(isset($tableInfo[$html['key']])){

                if(!empty($confArr[$html['key']])){
                    $tableArr[$html['key']] = ['label' => $html['label'], 'value'=>$confArr[$html['key']][$tableInfo[$html['key']]]];
                }else{
                    $tableArr[$html['key']] = ['label' => $html['label'], 'value'=>$tableInfo[$html['key']]];
                }
            }
        }

        $issueArr['table'] = json_encode($tableArr, JSON_UNESCAPED_UNICODE);

//        $approverList = MysqlCommon::getInstance()->getListByTableName('issue_workflow_approve', ['id', 'node_id',
//            'node_name','apvr_uid','status', 'order', 'append'], ['issue_id'=>$id], '`order` asc');
//        foreach ($approverList as $k => $approver){
//            if( $approver['apvr_uid']== 'system'){
//                $cnameStr = '系统';
//            }else{
//                $uidArr = explode(',',$approver['apvr_uid']);
//                $cnameArr = [];
//                foreach ($uidArr as $uid){
//                    $userInfo = ExtAdms::getInstance()->getUserInfo($uid);
//                    $cnameArr[] = $userInfo['name'];
//                }
//                $cnameStr = implode(',',$cnameArr);
//            }
//            MysqlCommon::getInstance()->updateListByTableName('issue_workflow_approve',
//                ['apvr_name'=>$cnameStr], ['id'=>$approver['id']]);
//            $approverList[$k]['apvr_name'] = $cnameStr;
//            unset($approverList[$k]['id']);
//            unset($approverList[$k]['order']);
//        }
//        $issueArr['workflow'] = json_encode($approverList, JSON_UNESCAPED_UNICODE);

        RedisIssueMain::getInstance()->setInfo($id, $issueArr);

        return $issueArr;
    }

    /**
     * 获取AMT系统中的Uid加密数据
     * @param $uid string 域账号
     * @throws string
     * @return array
     */
    public function getAmtUidXxtea($uid)
    {
        $uidInfo = MysqlCommon::getInstance()->getInfoByTableName('amt_ifengapp_uid_xxtea',['xxtea'],['uid'=>$uid]);
        if(empty($uidInfo)){
            $amtUid = ExtIappApi::getInstance()->getUid($uid);
            if(empty($amtUid) || $amtUid['success'] != true){
                throw new \Exception('获取用户加密Uid信息错误,请重试');
            }

            $uidXxtea = $amtUid['data'];

            MysqlCommon::getInstance()->addInfoByTableName('amt_ifengapp_uid_xxtea',['xxtea'=>$uidXxtea, 'uid'=>$uid]);

        }else{
            $uidXxtea = $uidInfo['xxtea'];
        }
        return $uidXxtea;
    }

    /**
     * 单例模式获取类实例
     * @return \DAO\Issue\DataModel
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
