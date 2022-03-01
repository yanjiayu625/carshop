<?php

use \Mysql\Issue\ServerModel        as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel    as MysqlIssueServerInfo;
use \Mysql\Issue\UserModel          as MysqlUserInfo;
/**
 * 常用功能,
 */
class InitController extends \Base\Controller_AbstractCli
{
    /**
     * 根据result和status判断提案进行 php cli/cli.php request_uri='/cli/Init/mysql'
     * 获取申请人部门分管VP
     * @return  json
     */
    public function mysqlAction()
    {
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_main`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_operation_node`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_save`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_workflow_approve`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_workflow_approve_uid`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_cli_queue`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_execute_status`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_relation`");
        MysqlCommon::getInstance()->exeSql("Truncate table `issue_notify`");
        MysqlCommon::getInstance()->exeSql("Truncate table `travel_ctrip_order_authorize`");
        MysqlCommon::getInstance()->exeSql("update `travel_ctrip_order` set `status` = 9 where `status` = 1"); //保留已落地携程的订票数据(其实主表信息都删掉了,留这个也没什么用)

        $keysArr = RedisIssueMain::getInstance()->getAllKeys();
        foreach ($keysArr as $key){
            RedisIssueMain::getInstance()->del($key);
        }

        MongoDBIssue::getInstance()->delData('issue_info_copy', [["q" => ['i_workflow_id'=>['$gte'=>1]], "limit" => 0]]);
        MongoDBIssue::getInstance()->delData('expense_detailed', [["q" => ['id'=>['$gte'=>'1']], "limit" => 0]]);
        MongoDBIssue::getInstance()->delData('auto_inc_ids', [["q" => ['name'=>['$eq'=>'expense_detailed']], "limit" => 0]]);

//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_link`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_link_trigger`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_node`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_node_approver`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_node_modify_field`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_node_notify`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_node_show_field`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_node_rollback`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_trigger_func`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_workflow`");
//        MysqlCommon::getInstance()->exeSql("Truncate table `pms_system`");

    }

}
