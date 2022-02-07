<?php


use \Mysql\Issue\CustomInfoModel as MysqlIssueCustomInfo;
use \Mysql\Issue\CommonModel as MysqlIssueCommon;
use \Mysql\Issue\ServerModel as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;


/**
 * 表格数据二次处理模块
 */
class TabledataController extends \Base\Controller_AbstractCli
{

    /**
     *  提前保存提案内容数据 php cli/cli.php request_uri='/cli/Tabledata/saveData/id/16317'
     */
    public function saveDataAction()
    {
        $param = $this->getRequest()->getParams();

        try {

            if (empty($param['id'])) {
                throw new \Exception("[保存提案数据脚本]输入参数错误:提案ID为空");
            }
            if (empty($param['action'])) {
                throw new \Exception("[保存提案数据脚本]输入参数错误:操作类型为空");
            }
            $server = MysqlIssueServer::getInstance()->getOneServer(['info_id', 'i_type'], ['id' => $param['id']]);

            switch ($server['i_type']) {

                case 'livebroadcast':
                    if($param['action'] == 'create'){
                        //提案创建时需要将数据写进附表
                        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(['infoJson'], ['id' => $server['info_id']]);
                        $infoArr = json_decode($serverInfo['infoJson'], true);

                        $channel = [];
                        $channel['server_id'] = $param['id'];
                        $channel['stream']    = $infoArr['t_stream'];
                        $channel['channel']   = $infoArr['t_channel'];
                        $channel['status']    = 1; //'状态:0-已取消,1-未审批,2-已审批,3-已使用'
                        $channel['start']     = strtotime($infoArr['t_starttime']);
                        $channel['end']       = strtotime($infoArr['t_endtime']);

                        $addRes = MysqlIssueCommon::getInstance()->addNewData('Attach_LiveBroadcast', $channel);
                        if(!$addRes){
                            throw new \Exception("[保存提案数据脚本]数据库错误:保存数据到表'Attach_LiveBroadcast'时出错");
                        }
                    }
                    break;

                case 'ifengidccom':
                    if($param['action'] == 'create'){
                        //提案创建时,记录host信息
                        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(['infoJson'], ['id' => $server['info_id']]);
                        $infoArr = json_decode($serverInfo['infoJson'], true);

                        $data['sid'] = $param['id'];
                        $data['host'] = $infoArr['host'];

                        $addRes = MysqlIssueCommon::getInstance()->addNewData('IssueIfengidcComDomain', $data);
                        if(!$addRes){
                            throw new \Exception("[保存提案数据脚本]数据库错误:保存数据({$param['id']})到表'IssueIfengidcComDomain'时出错");
                        }
                    }
                    break;

                default:
                    break;
            }

        } catch (\Exception $e) {
            $result = $e->getMessage();

            $this->__sendErrorMsg($result);
        }


    }


    /**
     * 发送错误通知邮件
     * @param $msg string
     * @return array
     */
    private function __sendErrorMsg($msg)
    {
        $php = \Yaf\Registry::get('config')->get('php.path');
        $cmd = "{$php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendErrorEmail/msg/{$msg}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }


}
