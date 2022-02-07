<?php
use \Tools\Tools;
use \Mysql\Issue\NodeModel       as MysqlIssueNode;
use \Mysql\Issue\ServerModel     as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerinfo;
use \Mysql\Daily\DailyModel      as MysqlDaily;
use \Business\Issue\ServerModel  as BusIssueServer;

/**
 * Domain域名管理系统
 */
class Daily_ServerController extends \Base\Controller_AbstractDailyApi
{
    private $_issueTypeTable = array(
        'wirelessnetwork' => 'DailyWireless',
        'intnetwork' => 'DailyLan',
    );

    /**
     * 获取自己巡检提案信息列表
     */
    public function getOwnDailyListAction()
    {
        $query = $this->getRequest()->getQuery();

        try {
            if (empty($query['status'])) {
                throw new \Exception('输入参数错误:任务状态值为空');
            }

            $where = array();
            $where['i_type'] = array_keys($this->_issueTypeTable);
            $where['i_status'] = $query['status'];

            $serverList = MysqlIssueServer::getInstance()->getServerList(null, $where,null,'id desc');

            foreach ($serverList as $k => $server){
                $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('action'),
                    array('server_id'=>$server['id'],'operator'=>$this->_user),null,'id desc');
                $serverList[$k]['action'] = $nodeInfo['action'];
            }

            $res['code'] = 200;
            $res['msg'] = '获取数据成功';
            $res['data'] = $serverList;

        } catch (\Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);

    }

    /**
     * 获取巡检提案信息
     * @return  json
     */
    public function getDailyIssueInfoAction()
    {

        $query = $this->getRequest()->getQuery();

        try {
            if (empty($query['id'])) {
                throw new \Exception('输入参数错误:提案ID为空');
            }

            if (empty($query['type'])) {
                throw new \Exception('输入参数错误:提案类型为空');
            }

            if (!in_array($query['type'], array_keys($this->_issueTypeTable))) {
                throw new \Exception('输入参数错误:提案类型非法');
            }

            $where = array();
            $where['server_id'] = $query['id'];
            $where['user'] = $this->_user;

            $serverList = MysqlDaily::getInstance()->getOneInfo($this->_issueTypeTable[$query['type']], null, $where);

            $res['code'] = 200;
            $res['msg'] = '获取数据成功';
            $res['data'] = $serverList;

        } catch (\Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);


    }

    /**
     * 获取巡检提案信息列表
     */
    public function getDailyCompletedListAction()
    {
        $query = $this->getRequest()->getQuery();

        $where = array();
        $limitStr = "{$query["iDisplayStart"]},{$query["iDisplayLength"]}";

        $where['i_type'] = array_keys($this->_issueTypeTable);

        $where['i_status'] = 4;
        $where['i_result'] = 1;
        $where['exe_person'] = $this->_user;

        if (!empty($query['id'])) {
            $where['id'] = $query['id'];
        }
        if (!empty($query['startTime'])) {
            $where['c_time >='] = $query['startTime'];
        }
        if (!empty($query['endTime'])) {
            $where['c_time <='] = $query['endTime'];
        }

        if (empty($where)) {
            $res['iTotalRecords'] = 0;
            $res['iTotalDisplayRecords'] = 0;
            $res['aaData'] = array();
        } else {
            $total = MysqlIssueServer::getInstance()->getCountByWhere($where);
            $serverList = MysqlIssueServer::getInstance()->getServerList(null, $where, null, null, $limitStr);
            $res['iTotalRecords'] = $total;
            $res['iTotalDisplayRecords'] = $total;
            $res['aaData'] = $serverList;
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 领取提案
     */
    public function receiveAndExeAction()
    {
        $query = $this->getRequest()->getQuery();

        if (!empty($query['id'])) {
            $receive = BusIssueServer::getInstance()->receiveServer($query['id'],'e1',$this->_user,'web');
            if( $receive['status'] ){
                $res['status'] = true;
                $res['msg']    = '提案领取成功';
            }else{
                $res['status'] = false;
                $res['msg']    = $receive['msg'];
            }
        }else{
            $res['status'] = false;
            $res['msg'] = '参数错误:提案ID为空';
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 更新提案详细信息
     */
    public function updateDailyInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlIssueServer::getInstance()->beginTransaction();

            if (empty($postInfo['server_id'])) {
                throw new \PDOException('输入参数错误:提案ID为空');
            }
            $data['server_id'] = $postInfo['server_id'];

            $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('id'),
                array('server_id'=>$postInfo['server_id'],'operator'=>$this->_user,'action'=>'exe'));
            if(empty($nodeInfo)){
                throw new \PDOException('权限错误:该提案您无权进行执行操作');
            }

            if (empty($postInfo['type'])) {
                throw new \PDOException('输入参数错误:提案类型为空');
            }
            if (!in_array($postInfo['type'], array_keys($this->_issueTypeTable))) {
                throw new \PDOException('输入参数错误:提案类型非法');
            }
            $dataTable = $this->_issueTypeTable[$postInfo['type']];

            switch ($postInfo['type']) {
                case 'wirelessnetwork':
                    //处理无线巡检数据
                    if (!\Validate\Validate::isNumber($postInfo['ap_fault'])) {
                        throw new \PDOException('输入参数错误:AP故障数量值为空或值非法');
                    }
                    if (empty($postInfo['ac_health']) || !in_array($postInfo['ac_health'],array(1,2))) {
                        throw new \PDOException('输入参数错误:AC健康状态值为空或非法');
                    }
                    if ($postInfo['ac_health'] == 2 && empty($postInfo['ac_health_status'])) {
                        throw new \PDOException('输入参数错误:AC健康状态说明为空');
                    }
                    if (empty($postInfo['imc']) || !in_array($postInfo['imc'],array(1,2))) {
                        throw new \PDOException('输入参数错误:IMC服务器状态值为空或非法');
                    }
                    if ($postInfo['imc'] == 2 && empty($postInfo['imc_status'])) {
                        throw new \PDOException('输入参数错误:IMC服务器状态说明为空');
                    }
                    if (empty($postInfo['imc_sync']) || !in_array($postInfo['imc_sync'],array(1,2))) {
                        throw new \PDOException('输入参数错误:IMC同步机制检查值为空或非法');
                    }
                    if (empty($postInfo['protal']) || !in_array($postInfo['protal'],array(1,2))) {
                        throw new \PDOException('输入参数错误:Protal认证检查值为空或非法');
                    }
                    if (empty($postInfo['ac_time'])) {
                        throw new \PDOException('输入参数错误:AC运行时间截图图片为空');
                    }

                    $data['ap_fault']           = $postInfo['ap_fault'];
                    $data['ac_health']          = $postInfo['ac_health'];
                    $data['ac_health_status']   = $postInfo['ac_health_status'];
                    $data['imc']                = $postInfo['imc'];
                    $data['imc_status']         = $postInfo['imc_status'];
                    $data['imc_sync']           = $postInfo['imc_sync'];
                    $data['protal']             = $postInfo['protal'];
                    $data['ac_time']            = $postInfo['ac_time'];

                    $serverInfo['t_ap_fault']           = $postInfo['ap_fault'];
                    $serverInfo['t_ac_health']          = $postInfo['ac_health'];
                    $serverInfo['t_ac_health_status']   = $postInfo['ac_health_status'];
                    $serverInfo['t_imc']                = $postInfo['imc'];
                    $serverInfo['t_imc_status']         = $postInfo['imc_status'];
                    $serverInfo['t_imc_sync']           = $postInfo['imc_sync'];
                    $serverInfo['t_protal']             = $postInfo['protal'];

                    $image_file = APPLICATION_PATH . '/public'.$postInfo['ac_time'];
                    $image_info = getimagesize($image_file);
                    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
                    $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
                    $serverInfo['t_ac_time'] = "<img src='{$base64_image}' width='200' />";

                    break;

                case 'intnetwork':
                    //处理内网巡检数据
                    if ( !\Validate\Validate::isNumber($postInfo['shaft_num']) ) {
                        throw new \PDOException('输入参数错误:竖井交换机故障数量值必须为数字');
                    }
                    if ($postInfo['shaft_num'] != 0 && empty($postInfo['shaft_desc'])) {
                        throw new \PDOException('输入参数错误:竖井交换机故障描述不能空');
                    }

                    if ( !\Validate\Validate::isNumber($postInfo['twolayer_num']) ) {
                        throw new \PDOException('输入参数错误:2层交换机故障数量值必须为数字');
                    }
                    if ($postInfo['twolayer_num'] != 0 && empty($postInfo['twolayer_desc'])) {
                        throw new \PDOException('输入参数错误:2层交换机故障描述不能空');
                    }

                    if ( !\Validate\Validate::isNumber($postInfo['ninelayer_num']) ) {
                        throw new \PDOException('输入参数错误:竖井交换机故障数量值必须为数字');
                    }
                    if ($postInfo['ninelayer_num'] != 0 && empty($postInfo['ninelayer_desc'])) {
                        throw new \PDOException('输入参数错误:竖井交换机故障描述不能空');
                    }

                    if ( !\Validate\Validate::isNumber($postInfo['fifteenlayer_num']) ) {
                        throw new \PDOException('输入参数错误:竖井交换机故障数量值必须为数字');
                    }
                    if ($postInfo['fifteenlayer_num'] != 0 && empty($postInfo['fifteenlayer_desc'])) {
                        throw new \PDOException('输入参数错误:竖井交换机故障描述不能空');
                    }

                    $data['shaft_num']          = $postInfo['shaft_num'];
                    $data['shaft_desc']         = $postInfo['shaft_desc'];
                    $data['twolayer_num']       = $postInfo['twolayer_num'];
                    $data['twolayer_desc']      = $postInfo['twolayer_desc'];
                    $data['ninelayer_num']      = $postInfo['ninelayer_num'];
                    $data['ninelayer_desc']     = $postInfo['ninelayer_desc'];
                    $data['fifteenlayer_num']   = $postInfo['fifteenlayer_num'];
                    $data['fifteenlayer_desc']  = $postInfo['fifteenlayer_desc'];

                    $serverInfo['t_shaft_num']          = $postInfo['shaft_num'];
                    $serverInfo['t_shaft_desc']         = $postInfo['shaft_desc'];
                    $serverInfo['t_twolayer_num']       = $postInfo['twolayer_num'];
                    $serverInfo['t_twolayer_desc']      = $postInfo['twolayer_desc'];
                    $serverInfo['t_ninelayer_num']      = $postInfo['ninelayer_num'];
                    $serverInfo['t_ninelayer_desc']     = $postInfo['ninelayer_desc'];
                    $serverInfo['t_fifteenlayer_num']   = $postInfo['fifteenlayer_num'];
                    $serverInfo['t_fifteenlayer_desc']  = $postInfo['fifteenlayer_desc'];

                    break;
                default:
                    throw new \PDOException('系统内部错误:该提案类型无法处理数据模块');
                    break;
            }

            $data['user'] = $this->_user;

            $addRes = MysqlDaily::getInstance()->addNewData($dataTable,$data);
            if (!$addRes) {
                throw new \PDOException("数据保存错误:表{$dataTable}插入数据错误");
            }

            $server = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
                array('id'=>$postInfo['server_id']));
            $updateServerInfoRes = MysqlIssueServerinfo::getInstance()->updateServerInfo(
                array('infoJson' => json_encode($serverInfo,JSON_UNESCAPED_SLASHES)), array('id' => $server['info_id']));
            if (!$updateServerInfoRes) {
                throw new \PDOException("数据保存错误,表IssueServerInfo更新数据错误");
            }

            MysqlIssueServer::getInstance()->commitTransaction();
            BusIssueServer::getInstance()->MysqlToRedisServer($postInfo['server_id']);

            BusIssueServer::getInstance()->exeServer($postInfo['server_id'], '巡检数据录入完毕,任务自动执行完成', 'wangshuo7,daitao',
                'web', $this->_user, '', '');

            $res['status'] = true;
            $res['msg'] = '更新成功';
        } catch (\PDOException $e) {

            MysqlIssueServer::getInstance()->rollbackTransaction();
            $res['status'] = false;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

}
