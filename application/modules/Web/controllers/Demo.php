<?php

use \DAO\Issue\ConfigModel as DaoIssueConfig;
use \Mysql\Issue\TreeModel as MysqlIssueTree;
use \Mysql\Issue\ProcessModel as MysqlIssueProcess;

    /**
     * 提案管理
     */
class AdminController extends \Base\Controller_AbstractIssueAdmin {

    //提案管理介绍页面
    public function indexAction()
    {
        
    }

    //提案表格编辑页面
    public function edittableAction()
    {
        $name   = $this->getRequest()->getQuery('name');
        $relDir = DaoIssueConfig::getInstance()->getTableField($name,'dir');

        if( $relDir == null){
            $dir = TEMPLATE_DIR . 'IssueTpl/'.$name.'.html';
        }else{
            $dir = TEMPLATE_DIR . $relDir;
        }
        if(!file_exists($dir)){
            $fopen = fopen($dir,'wb');
            fputs($fopen,'');
            fclose($fopen);
        }

        $this->getView()->assign("dir", $dir);
        $this->getView()->assign("name",  '申请人姓名');
        $this->getView()->assign("email", '申请人邮箱');
        $this->getView()->assign("department", '申请人部门');

        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('type','level1','level2','level3','level4'),array('name'=>$name));
        $this->getView()->assign("serverType", $treeInfo['type']);
        unset($treeInfo['type']);
        $this->getView()->assign("servername", implode('>',array_values($treeInfo)));

        //检测是否有直属领导人审批
        $processInfo = MysqlIssueProcess::getInstance()->getOneProcess(
            array('checkJson'),array('name'=>$name));
        if( !empty($processInfo) ){
            $checkArr = json_decode( $processInfo['checkJson'] ,true );
            $checkList = $checkArr['list']['check'];
            $leader = (array_key_exists('{leader}',$checkList)) ? true : false;
            $this->getView()->assign("leader", $leader);

            $operator = (array_key_exists('{operator}',$checkList)) ? true : false;
            $this->getView()->assign("operator", $operator);
        }else{
            $this->getView()->assign("leader", false);
            $this->getView()->assign("operator", false);
        }

    }


    //设置提案流程页面
    public function setprocessAction()
    {
    }

    //设置提案执行人组页面
    public function setexecutorAction()
    {
    }
    //接口说明
    public function apidetailAction()
    {
    }
     //设置连接页面
    public function settingsAction()
    {
        $name   = $this->getRequest()->getQuery('name');
        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1','level2','level3','level4'),array('name'=>$name));
        $this->getView()->assign("servername", implode('>',array_values($treeInfo)));
    }
    //设置连接页面
    public function setcodeAction()
    {
    } 
    //设置连接页面
    public function setexehtmlAction()
    {
    }
    //数据统计页面
    public function totaldataAction()
    {
        
    }
    //分类数据统计页面
    public function subdataAction()
    {
        
    }
    //数据统计页面
    public function timedataAction()
    {
        
    }
     //数据统计页面
    public function domaindataAction()
    {
        
    }
    //报修统计
    public function repairlistAction()
    {
        
    }

}
