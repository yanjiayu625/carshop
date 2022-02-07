<?php

use \Mysql\Issue\TreeModel              as MysqlIssueTree;
use \Mysql\Issue\ServerModel            as MysqlIssueServer;
use \Mysql\Issue\UserModel              as MysqlIssueUserInfo;
use \Mysql\Issue\NodeModel              as MysqlIssueNode;
use \Mysql\Issue\ServerInfoModel        as MysqlIssueServerInfo;
use \Mysql\Issue\CustomInfoModel        as MysqlIssueCustom;
use \Mysql\Issue\ServerRelateModel      as MysqlIssueRelate;
use \ExtInterface\Ldap\ApiModel         as ExtLdapApi;
use \Business\Issue\ServerModel         as BusIssueServer;
use \Business\Issue\ProcessStatusModel  as BusIssueProcessStatus;
        
use \ExtInterface\Ioi\ApiModel          as ExtIoiApi;
use \ExtInterface\Cmdb\NewapiModel      as ExtCmdbApi;
use \Business\IdcOs\ConfigModel         as BIdcOsConf;
use \ExtInterface\IdcOs\ApiModel        as ExtIdcOsApi;
use \ExtInterface\Duty\ApiModel         as ExtDutyApi;
use \ExtInterface\It\ApiModel           as ExtItApi;

/**
 * 自动创建主从,相关提案
 */
class AutocreateController extends \Base\Controller_AbstractCli
{

    private $_php = '';

    /**
     * 根据提案类型,创建相关提案  php cli/cli.php request_uri='/cli/Autocreate/create/id/16157'
     */
    public function createAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id'])) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');
            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_type'), array('id' => $param['id']));
            
            if (!empty($server['i_type'])) {

                switch ($server['i_type']) {
                    case 'batchonline':
                    case 'serveronline':
                        $this->__createBatchOnlineSon($param['id']);
                        break;

                    case 'batchoffline':
                    case 'serveroffline':
                        $this->__createBatchOfflineMonitor($param['id']);
                        break;

                    case 'batchmigrate':
                    case 'servermigrate':
                        $this->__createBatchMigrateMonitor($param['id']);
                        break;

                    case 'ifengcom':
                        $this->__createHttpsCertificate($param['id']);
                        break;

                    case 'serverosrefresh':
                        $this->__createBatchOsRefresh($param['id']);
                        break;

                    //变更类提案发送通知给 Idc-Mc 系统
                    case 'puppetchange':
                        $this->__sendMsgToIdcMc($param['id']);
                        break;
                    case 'batchoperation':
                        $this->__sendMsgToIdcMc($param['id']);
                        break;
                    case 'baseops':
                        $this->__sendMsgToIdcMc($param['id']);
                        break;
                    case 'puppetmerge':
                        $this->__sendMsgToIdcMc($param['id']);
                        break;
                    case 'datacenter':
                        $this->__sendMsgToIdcMc($param['id']);
                        break;

                    default:

                        break;
                }
            }
        }
    }


    /**
     * 创建批量服务器上线的子提案,4个
     * @param $id string
     */
    private function __createBatchOnlineSon($id)
    {
        $sonIssue = array('batchonlineneed' => '服务器系统需求整理', 'batchonlineimplement' => '综布实施',
            'batchonlineshelves' => '服务器上架', 'batchonlinenewwork' => '网络环境按需部署');

        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'), array('id' => $id));
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $online = json_decode($customInfo['info'], true);

        $serverArr['i_urgent'] = $server['i_urgent'];
        $serverArr['i_risk'] = $server['i_risk'];
        $serverArr['i_countersigned'] = '';
        $serverArr['i_applicant'] = '系统';
        $serverArr['i_email'] = 'pms@ifeng.com';
        $serverArr['i_department'] = '技术部 - 运维中心';

        //判断是否重开
        $reopen = false;
        $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('id'), array('action' => 'reopen', 'server_id' => $id));
        if (!empty($nodeInfo)) {
            $reopen = true;
        }

        $html = "<table class='table-bordered' style='text-align: center;width:100%'>" .
            "<tbody><tr>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>机型</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>品牌</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>型号</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>高度</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>数量</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='36%'>CPU</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>MEM</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>HDD</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>SSD</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>NIC-1G</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>NIC-10G</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>Raid-Card</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>Vsan使用</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>上线机房</th>" .
            "</tr>" .
            "</tbody><tbody>";
        foreach ($online as $rows) {
            $rowHtml = '<tr>';
            foreach ($rows as $c) {
                if ($c['chg'] == 0) {
                    $color = '';
                } else {
                    $color = 'color:#e12b31';
                }
                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;{$color}'>" . $c['val'] . "</td>";
            }
            $rowHtml .= "</tr>";

            $html .= $rowHtml;
        }
        $html .= "</tbody></table>";

        foreach ($sonIssue as $son => $text) {
            $serverArr['i_title'] = $server['i_title'] . ' - ' . $text;
            $serverArr['i_type'] = $son;
            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1', 'level2', 'level3', 'level4'), array('name' => $son));
            $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));
            $serverArr['t_custom_requirement'] = $html;

            if (!$reopen) {
                $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
                if ($createRes['status']) {
                    $data['type'] = 'son';
                    $data['master'] = $id;
                    $data['m_type'] = 'batchonline';
                    $data['slave'] = $createRes['data'];
                    $data['s_type'] = $son;
                    $data['status'] = 1;
                    $addRes = MysqlIssueRelate::getInstance()->addSingleData($data);
                    if (!$addRes) {
                        $this->__sendExtApiErrorMsg('zhangyang7', $id, 'autoCreateIssue', "保存主从提案信息时msyql错误");
                    }

                } else {
                    $this->__sendExtApiErrorMsg('zhangyang7', $id, 'autoCreateIssue', "创建子提案{$text}失败");
                }
            } else {
                $sonInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('slave'),
                    array('master' => $id, 's_type' => $son));
                $serverArr['id'] = $sonInfo['slave'];
                $reopenRes = BusIssueServer::getInstance()->reopenServer($serverArr, 'api', 'pms', array());

                if (!$reopenRes['status']) {
                    $this->__sendExtApiErrorMsg('zhangyang7', $id, 'autoCreateIssue', "重开子提案{$sonInfo['slave']}失败");
                }
            }
        }
    }

    /**
     * 变更类提案执行开始时,发送通知到综合管理平台系统
     * @param $id string
     */
    private function __sendMsgToIdcMc($id){

        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title'), array('id' => $id));

        $postData = array('msg'=>$server['i_title'],'url'=>$id,'time'=>time(),"from"=>"pms");

        $pushRes = ExtDutyApi::getInstance()->pushMsg($postData);

        if($pushRes === false || $pushRes['status'] != 200){
            $error = json_encode($pushRes, JSON_UNESCAPED_UNICODE);
            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'sendMsgToIdcMc', "发送消息失败,错误信息:{$error}");
        }

    }

    /**
     * 根据提案类型,创建相关提案 php cli/cli.php request_uri='/cli/Autocreate/checkFinish/id/16704'
     */
    public function checkFinishAction()
    {

        $param = $this->getRequest()->getParams();

        if (isset($param['id'])) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');
            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_type'), array('id' => $param['id']));

            if (!empty($server['i_type'])) {

                switch ($server['i_type']) {
                    case 'batchonline':

                        break;
                    case 'batchonlineneed':
                        $this->__checkFinishBatchOnlineSon($param['id']);
                        break;
                    case 'batchonlineimplement':
                        $this->__checkFinishBatchOnlineSon($param['id']);
                        break;
                    case 'batchonlineshelves':
                        $this->__checkFinishBatchOnlineSon($param['id']);
                        break;
                    case 'batchonlinenewwork':
                        $this->__checkFinishBatchOnlineSon($param['id']);
                        break;

                    case 'batchonlineidcos':
                        $this->__checkFinishBatchOnlineIdcos($param['id']);
                        break;
                    case 'batchonlinevsan':
                        $this->__checkFinishBatchOnlineVsan($param['id']);
                        break;

                    case 'batchonlineservercheck':
                        $this->__checkFinishBatchOnlineComplete($param['id']);
                        break;
                    case 'batchonlinevsancheck':
                        $this->__checkFinishBatchOnlineComplete($param['id']);
                        break;

                    case 'serverfailurescoordinate':
                        $this->__checkFinishFailuresCoordinateComplete($param['id']);
                        break;

                    case 'batchofflinemonitor':
                        $this->__checkFinishMonitorOfflineComplete($param['id']);
                        break;

                    case 'batchofflineserver':
                        $this->__checkFinishServerOfflineComplete($param['id']);
                        break;
                    case 'batchmigrateserver':
                        $this->__checkFinishServerOfflineComplete($param['id']);
                        break;

                    case 'batchofflinecmdb':
                        $this->__checkFinishBatchOfflineSon($param['id']);
                        break;
                    case 'batchofflinenetwork':
                        $this->__checkFinishBatchOfflineSon($param['id']);
                        break;
                    case 'batchofflinejumpbox':
                        $this->__checkFinishBatchOfflineSon($param['id']);
                        break;

                    case 'batchofflinedelmonitor':
                        $this->__closeBatchOffline($param['id']);
                        break;

                    case 'batchmigrateonlinereinstall':
                        $this->__closeBatchMigrate($param['id']);
                        break;
                    case 'batchmigrateonline':
                        $this->__closeBatchMigrate($param['id']);
                        break;

                    case 'batchosrefreshphysicalin':
                        $this->__closeBatchOsRefresh($param['id']);
                        break;
                    case 'batchosrefreshphysicalout':
                        $this->__closeBatchOsRefresh($param['id']);
                        break;
                    case 'batchosrefreshvsan':
                        $this->__closeBatchOsRefresh($param['id']);
                        break;
                    case 'batchosrefreshexsi':
                        $this->__closeBatchOsRefresh($param['id']);
                        break;

                    default:

                        break;
                }
            }
        }
    }

    /**
     * 创建批量服务器上线的子提案完成状态,创建装机提案
     * @param $id string
     */
    private function __checkFinishBatchOnlineSon($id)
    {
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $sonList = MysqlIssueRelate::getInstance()->getServerRelateList(array('slave', 's_type'),
            array('master' => $relateInfo['master'], 's_type' => array('batchonlineneed', 'batchonlineimplement',
                'batchonlineshelves', 'batchonlinenewwork')));

        $finish = true;
        $needId = '';
        foreach ($sonList as $son) {
            $server = MysqlIssueServer::getInstance()->getOneServer(array('id'),
                array('id' => $son['slave'], 'i_status' => 4, 'i_result' => 1));
            if (empty($server)) {
                $finish = false;
            }
            if ($son['s_type'] == 'batchonlineneed') {
                $needId = $son['slave'];
            }
        }

        if ($finish) {
            $needServer = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
                array('id' => $needId));
            $needServerInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $needServer['info_id']));
            $needInfoArr = json_decode($needServerInfo['infoJson'], true);
            $needdoc = $needInfoArr['t_requirement_doc'];

            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'),
                array('id' => $relateInfo['master']));

            $serverType = 'batchonlineidcos';
            $typeName = '服务器装机流程';

            $add['t_needsdocument'] = empty($needdoc) ? '无需求文档,请联系相关负责人' : $needdoc;
            $add['t_change'] = '无';

            $this->__createSonIssue($relateInfo['master'], $server, $serverType, $typeName, $add);
        }
    }


    /**
     * 创建批量服务器上线的子提案完成状态,创建装机提案
     * @param $id string
     */
    private function __checkFinishBatchOnlineIdcos($id)
    {
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'),
            array('id' => $relateInfo['master']));

        $idcServer = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
            array('id' => $id));
        $idcServerInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $idcServer['info_id']));
        $idcInfoArr = json_decode($idcServerInfo['infoJson'], true);
        $idcdoc = $idcInfoArr['t_install_os'];

        $serverType = 'batchonlineservercheck';
        $typeName = '服务器安装状态检查';

        $add['t_onlinedoc'] = empty($idcdoc) ? '' : $idcdoc;
        $add['t_change'] = '无';

        $this->__createSonIssue($relateInfo['master'], $server, $serverType, $typeName, $add);

        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $relateInfo['master']));
        $online = json_decode($customInfo['info'], true);

        $html = "<table class='table-bordered' style='text-align: center;width:100%'>" .
            "<tbody><tr>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>机型</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>品牌</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>型号</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>高度</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>数量</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='36%'>CPU</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>MEM</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>HDD</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>SSD</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>NIC-1G</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>NIC-10G</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>Raid-Card</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>Vsan使用</th>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8'>上线机房</th>" .
            "</tr>" .
            "</tbody><tbody>";
        $vsan = false;
        foreach ($online as $rows) {
            if ($rows[12]['val'] == 'Y') {
                $vsan = true;
            }
            $rowHtml = '<tr>';
            foreach ($rows as $c) {
                if ($c['chg'] == 0) {
                    $color = '';
                } else {
                    $color = 'color:#e12b31';
                }
                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;{$color}'>" . $c['val'] . "</td>";
            }
            $rowHtml .= "</tr>";

            $html .= $rowHtml;
        }
        $html .= "</tbody></table>";
        if ($vsan) {

            $serverType = 'batchonlinevsan';
            $typeName = 'Vsan集群部署';

            $vadd['t_custom_requirement'] = $html;
            $vadd['t_idcos_info'] = empty($idcdoc) ? '' : $idcdoc;
            $this->__createSonIssue($relateInfo['master'], $server, $serverType, $typeName, $vadd);
        }

    }

    /**
     * 检测Vsan安装完成,并创建检测状态
     * @param $id string
     */
    private function __checkFinishBatchOnlineVsan($id)
    {
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'),
            array('id' => $relateInfo['master']));

        $vsan = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
            array('id' => $id));
        $vsanInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id'=>$vsan['info_id']));
        $infoArr = json_decode($vsanInfo['infoJson'],true);

        $serverType = 'batchonlinevsancheck';
        $typeName   = 'Vsan-VM安装状态检查';

        $add['t_vsandoc'] = $infoArr['t_batch_clone_file'];
        $add['t_change'] = '无';

        $this->__createSonIssue($relateInfo['master'], $server, $serverType, $typeName, $add);

    }

    /**
     * 检测交付报告,并完结提案
     * @param $id string
     */
    private function __checkFinishBatchOnlineComplete($id)
    {
        $this->__sendInformEmail($id, 'wendi');

        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $sonList = MysqlIssueRelate::getInstance()->getServerRelateList(array('slave', 's_type'),
            array('master' => $relateInfo['master']));

        $finish = true;
        foreach ($sonList as $son) {
            if ($son['slave'] == $id) {
                continue;
            }
            $server = MysqlIssueServer::getInstance()->getOneServer(array('id'),
                array('id' => $son['slave'], 'i_status' => 4, 'i_result' => 1));
            if (empty($server)) {
                $finish = false;
                break;
            }
        }

        if ($finish) {
            $remarks = '所有子提案均已完成';
            $informs = '';
            BusIssueServer::getInstance()->exeServer($relateInfo['master'], $remarks, $informs, 'api', 'pms', '', '');
        }
    }

    /**
     * 检测故障报修协调提案完成
     * @param $id string
     */
    private function __checkFinishFailuresCoordinateComplete($id)
    {
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('type' => 'son', 'm_type' => 'serverfailures', 'slave' => $id));

        $remarks = '服务器中断时间已经确认,请在子提案中查询,谢谢';
        $informs = '';
        BusIssueServer::getInstance()->exeServer($relateInfo['master'], $remarks, $informs,
            'api', 'api_break_time', 'e4', '0');
    }

    /**
     * 发送通知邮件
     * @param $user string
     * @param $id string
     */
    private function __sendInformEmail($id, $user)
    {
        $cmd = "{$this->_php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendMsgEmail/id/{$id}/user/{$user}/action/inform' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 创建批量服务器上线的子提案完成状态,创建装机提案
     * @param $master string  主提案ID
     * @param $server array 主提案内容
     * @param $serverType string 子提案类型
     * @param $typeName string 子提案名称
     * @param $add array 内容信息
     */
    private function __createSonIssue($master, $server, $serverType, $typeName, $add)
    {

        $data['type'] = 'son';
        $data['master'] = $master;
        $data['m_type'] = 'batchonline';
        $data['s_type'] = $serverType;
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('id'), $data);

        if (empty($relateInfo)) {
            
            $serverArr['i_urgent'] = $server['i_urgent'];
            $serverArr['i_risk'] = $server['i_risk'];
            $serverArr['i_countersigned'] = '';
            $serverArr['i_applicant'] = '系统';
            $serverArr['i_email'] = 'pms@ifeng.com';
            $serverArr['i_department'] = '技术部 - 运维中心';

            $serverArr['i_title'] = $server['i_title'] . ' - ' . $typeName;
            $serverArr['i_type'] = $serverType;
            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));
            $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));


            $createRes = BusIssueServer::getInstance()->createServer(array_merge($serverArr, $add), 'api', 'pms', false);
            if ($createRes['status']) {
                $data['type'] = 'son';
                $data['master'] = $master;
                $data['m_type'] = 'batchonline';
                $data['slave'] = $createRes['data'];
                $data['s_type'] = $serverType;
                $data['status'] = 1;
                $addRes = MysqlIssueRelate::getInstance()->addSingleData($data);
                if (!$addRes) {
                    $this->__sendExtApiErrorMsg('zhangyang7', $master, 'autoCreateIssue', "主提案{$master},保存子提案信息时出错");
                }

            } else {
                $this->__sendExtApiErrorMsg('zhangyang7', $master, 'autoCreateIssue', "主提案{$master},创建{$typeName}时失败");
            }
        }
    }


    /**
     * 更新操作,重新将文件更新
     */
    public function checkUpdateAction()
    {

        $param = $this->getRequest()->getParams();

        if (isset($param['id']) && isset($param['nid'])) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');
            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_type'), array('id' => $param['id']));

            if (!empty($server['i_type'])) {

                switch ($server['i_type']) {
                    case 'batchonlineneed':
                        $this->__updateBatchOnlineNeed($param['id'], $param['nid']);
                        break;

                    case 'batchonlineidcos':
                        $this->__updateBatchOnlineIdcos($param['id'], $param['nid']);
                        break;


                    default:

                        break;
                }
            }
        }
    }

    /**
     * 更新需求整理文档
     * @param $id string
     * @param $nid string
     */
    private function __updateBatchOnlineNeed($id, $nid)
    {
        $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('remarks', 'operator', 'end_time'), array('id' => $nid));

        $needServer = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
            array('id' => $id));
        $needServerInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $needServer['info_id']));
        $needInfoArr = json_decode($needServerInfo['infoJson'], true);
        $needdoc = $needInfoArr['t_requirement_doc'];

        $add['t_needsdocument'] = empty($needdoc) ? '无需求文档,请联系相关负责人' : $needdoc;
        $add['t_change'] = "{$nodeInfo['operator']}在{$nodeInfo['end_time']}更新了内容";

        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $sonInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('slave'),
            array('master' => $relateInfo['master'], 's_type' => 'batchonlineidcos'));

        $server = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
            array('id' => $sonInfo['slave']));
        $updateServerInfoRes = MysqlIssueServerinfo::getInstance()->updateServerInfo(
            array('infoJson' => json_encode($add, JSON_UNESCAPED_SLASHES)), array('id' => $server['info_id']));
        if (!$updateServerInfoRes) {
            $this->__sendExtApiErrorMsg('zhangyang7', $server['info_id'], 'autoCreateIssue', "更新文档时错误");
        }

        BusIssueServer::getInstance()->MysqlToRedisServer($sonInfo['slave']);
    }

    /**
     * 更新装机完成文档
     * @param $id string
     * @param $nid string
     */
    private function __updateBatchOnlineIdcos($id, $nid)
    {
        $nodeInfo = MysqlIssueNode::getInstance()->getOneNode(array('remarks', 'operator', 'end_time'), array('id' => $nid));

        $idcServer = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
            array('id' => $id));
        $idcServerInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $idcServer['info_id']));
        $idcInfoArr = json_decode($idcServerInfo['infoJson'], true);
        $idcDoc = $idcInfoArr['t_install_os'];

        $add['t_onlinedoc'] = empty($idcDoc) ? '无服务器安装完成文档,请联系相关负责人' : $idcDoc;
        $add['t_change'] = "{$nodeInfo['operator']}在{$nodeInfo['end_time']}更新了内容";

        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $sonInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('slave'),
            array('master' => $relateInfo['master'], 's_type' => 'batchonlineservercheck'));

        $server = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
            array('id' => $sonInfo['slave']));
        $updateServerInfoRes = MysqlIssueServerinfo::getInstance()->updateServerInfo(
            array('infoJson' => json_encode($add, JSON_UNESCAPED_SLASHES)), array('id' => $server['info_id']));
        if (!$updateServerInfoRes) {
            $this->__sendExtApiErrorMsg('zhangyang7', $server['info_id'], 'autoCreateIssue', "更新文档时错误");
        }

        BusIssueServer::getInstance()->MysqlToRedisServer($sonInfo['slave']);

        //更新Vsan提案内容
        $sonInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('slave'),
            array('master' => $relateInfo['master'], 's_type' => 'batchonlinevsan'));
        if (!empty($sonInfo['slave'])) {
            $server = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
                array('id' => $sonInfo['slave']));
            $serverInfo = MysqlIssueServerinfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $server['info_id']));
            $addArr = json_decode($serverInfo['infoJson'], true);

            $addArr['t_idcos_info'] = empty($idcDoc) ? '无服务器安装完成文档,请联系相关负责人' : $idcDoc;
            $addArr['t_change'] = "{$nodeInfo['operator']}在{$nodeInfo['end_time']}更新了服务器安装完成文档";

            $updateServerInfoRes = MysqlIssueServerinfo::getInstance()->updateServerInfo(
                array('infoJson' => json_encode($addArr, JSON_UNESCAPED_SLASHES)), array('id' => $server['info_id']));
            if (!$updateServerInfoRes) {
                $this->__sendExtApiErrorMsg('zhangyang7', $server['info_id'], 'autoCreateIssue', "更新文档时错误");
            }
            BusIssueServer::getInstance()->MysqlToRedisServer($sonInfo['slave']);
        }
    }


    /**
     * 取消所有子提案
     */
    public function cancelSonAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id'])) {

            $sonList = MysqlIssueRelate::getInstance()->getServerRelateList(array('slave'),
                array('master' => $param['id']));
            if (!empty($sonList)) {

                foreach ($sonList as $son) {
                    $set['i_status'] = 3;
                    $set['i_result'] = 4;
                    $cancelRes = MysqlIssueServer::getInstance()->updateServerInfo($set, array('id' => $son['slave']));
                    if (!$cancelRes) {
                        $this->__sendExtApiErrorMsg('zhangyang7', $son['slave'], 'autoCreateIssue', "取消子提案时错误");
                    } else {
                        BusIssueServer::getInstance()->MysqlToRedisServer($son['slave']);
                    }
                    MysqlIssueNode::getInstance()->deleteNode(array('server_id' => $son['slave'], 'status' => 0));
                }
            }
            
            //取消提案时,判断是否为申请VPN
            $server = MysqlIssueServer::getInstance()->getOneServer(['i_title'],['id'=>$param['id']]);
//            if(in_array($server['i_title'],["【特权申请】VPN","【特权申请】香港代理"])){
            if(strpos($server['i_title'],'【特权申请】') !== false){
                ExtItApi::getInstance()->delVpn($param['id']);
            }
        }


    }


    /************************ 服务器批量下线 和 迁移 *************************/

    /**
     * 创建批量下线提案,监控下线提案
     * @param $id string  主提案ID
     */
    private function __createBatchOfflineMonitor($id)
    {
        $this->__monitorOffline($id, 'offline');
    }

    /**
     * 创建批量迁移提案,监控下线提案
     * @param $id string  主提案ID
     */
    private function __createBatchMigrateMonitor($id)
    {
        $this->__monitorOffline($id, 'migrate');
    }

    /**
     * 监控下线子提案 下线和迁移
     * @param $id string  主提案ID
     * @param $type string  下线或迁移
     */
    private function __monitorOffline($id, $type)
    {

        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'), array('id' => $id));
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $offlineArr = json_decode($customInfo['info'], true);

        $hostnameArr = array();
        foreach ($offlineArr as $offline) {
            if(!empty($offline)){
                $hostnameArr[] = $offline[1]['val'];
            }
        }

        $serverType = 'batchofflinemonitor';

        $serverArr['i_type'] = $serverType;
        $serverArr['i_urgent'] = $server['i_urgent'];
        $serverArr['i_risk'] = $server['i_risk'];
        $serverArr['i_countersigned'] = '';
        $serverArr['i_applicant'] = '系统';
        $serverArr['i_email'] = 'pms@ifeng.com';
        $serverArr['i_department'] = '技术部 - 运维中心';

        switch ($type) {
            case 'offline':
                $serverArr['i_title'] = "{$server['i_title']} - [下线]监控下线子提案";
                break;
            case 'migrate':
                $serverArr['i_title'] = "{$server['i_title']} - [迁移下线]监控下线子提案";
                break;
            default:
                break;
        }

        $serverArr['i_father'] = $id;

        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));
        $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

        $serverArr['t_hostname_list'] = implode('<br>', $hostnameArr);

        $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
        if (!$createRes['status']) {
            $this->__sendExtApiErrorMsg('zhangyang7', $id, 'autoCreateIssue', "主提案{$id},创建监控下线子提案时失败");
        }
    }

    /**
     * 创建批量迁移\下线提案-监控下线提案
     * @param $id string  主提案ID
     */
    private function __checkFinishMonitorOfflineComplete($id)
    {
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $server = MysqlIssueServer::getInstance()->getOneServer(array('id', 'i_title', 'i_type', 'i_urgent', 'i_risk',
            'info_id'), array('id' => $relateInfo['master']));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);

        $serverArr['i_urgent']          = $server['i_urgent'];
        $serverArr['i_risk']            = $server['i_risk'];
        $serverArr['i_countersigned']   = '';
        $serverArr['i_applicant']       = '系统';
        $serverArr['i_email']           = 'pms@ifeng.com';
        $serverArr['i_department']      = '技术部 - 运维中心';

        $serverArr['i_father'] = $server['id'];

        switch ($server['i_type']) {
            case 'batchoffline':
            case 'serveroffline':
                $title = "服务器下线";
                $serverArr['t_offline_info'] = $infoArr['t_custom_server_batch_offline'];
                $serverArr['i_title'] = "{$server['i_title']} - [下线] {$title} 子提案";
                $serverArr['i_type'] = 'batchofflineserver';
                break;

            case 'batchmigrate':
            case 'servermigrate':
                $title = "服务器迁移下线";
                $serverArr['t_offline_info'] = $infoArr['t_custom_server_batch_migrate'];
                $serverArr['i_title'] = "{$server['i_title']} - [迁移下线] {$title} 子提案";
                $serverArr['i_type'] = 'batchmigrateserver';
                break;
            default:
                break;
        }

        $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
            array('level1', 'level2', 'level3', 'level4'), array('name' => $serverArr['i_type']));
        $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

        $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
        if (!$createRes['status']) {
            $this->__sendExtApiErrorMsg('zhangyang7', $server['id'], 'autoCreateIssue', "主提案{$server['id']},创建 {$title} 时失败");
        }


    }

    /**
     * 创建批量迁移\下线提案-服务器下线提案
     * @param $id string  主提案ID
     */
    private function __checkFinishServerOfflineComplete($id)
    {
        $sonIssue = array('batchofflinecmdb' => '更新CMDB信息',
            'batchofflinenetwork' => '更新网络信息' ,'batchofflinejumpbox' => '删除跳板机权限');

        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $server = MysqlIssueServer::getInstance()->getOneServer(array('id', 'i_title', 'i_type', 'i_urgent', 'i_risk',
            'info_id'), array('id' => $relateInfo['master']));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id' => $server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'], true);

        $serverArr['i_urgent'] = $server['i_urgent'];
        $serverArr['i_risk'] = $server['i_risk'];
        $serverArr['i_countersigned'] = '';
        $serverArr['i_applicant'] = '系统';
        $serverArr['i_email'] = 'pms@ifeng.com';
        $serverArr['i_department'] = '技术部 - 运维中心';

        $serverArr['i_father'] = $server['id'];

        foreach($sonIssue as $serverType => $serverName ){

            switch ($server['i_type']) {
                case 'batchoffline':
                case 'serveroffline':
                    if($serverType == 'batchofflinecmdb'){
                        //将服务器下线机房位置信息,同步到更新CMDB子提案中
                        $sonServer = MysqlIssueServer::getInstance()->getOneServer(array('info_id'),
                            array('id' => $id));
                        $sonServerInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                            array('id' => $sonServer['info_id']));
                        $sonInfoArr = json_decode($sonServerInfo['infoJson'], true);
                        $serverArr['t_offline_info']  = $sonInfoArr['t_offline_info'];

                    }else{
                        $serverArr['t_offline_info']  = $infoArr['t_custom_server_batch_offline'];
                    }
                    $serverArr['i_title'] = "{$server['i_title']} - [下线]{$serverName}子提案";
                    break;

                case 'batchmigrate':
                case 'servermigrate':
                    $serverArr['t_offline_info']  = $infoArr['t_custom_server_batch_migrate'];
                    $serverArr['i_title'] = "{$server['i_title']} - [迁移下线]{$serverName}子提案";
                    break;
                default:
                    break;
            }

            $serverArr['i_type'] = $serverType;

            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1','level2','level3','level4'),array('name' => $serverType));
            $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

            $createRes = BusIssueServer::getInstance()->createServer($serverArr,'api','pms',false);
            if(!$createRes['status']){
                $this->__sendExtApiErrorMsg('zhangyang7',$server['id'],'autoCreateIssue',"主提案{$server['id']},创建{$serverName}时失败");
            }
        }


    }

    /**
     * 检测批量服务器下线的子提案完成状态,创建删除监控提案
     * @param $id string
     */
    private function __checkFinishBatchOfflineSon($id)
    {
        $relateInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $sonList = MysqlIssueRelate::getInstance()->getServerRelateList(array('slave', 's_type'),
            array('master' => $relateInfo['master'], 's_type' => array('batchofflineserver', 'batchofflinecmdb',
                'batchofflinenetwork', 'batchofflinejumpbox')));

        $finish = true;
        foreach ($sonList as $son) {
            $server = MysqlIssueServer::getInstance()->getOneServer(array('id'),
                array('id' => $son['slave'], 'i_status' => 4, 'i_result' => 1));
            if (empty($server)) {
                $finish = false;
                break;
            }
        }

        if ($finish) {

            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_type', 'i_urgent', 'i_risk'),
                array('id' => $relateInfo['master']));
            $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'),
                array('sid' => $relateInfo['master']));
            $offlineArr = json_decode($customInfo['info'], true);

            $hostnameArr = array();

            $serverType = 'batchofflinedelmonitor';

            $serverArr['i_type'] = $serverType;
            $serverArr['i_father'] = $relateInfo['master'];
            $serverArr['i_urgent'] = $server['i_urgent'];
            $serverArr['i_risk'] = $server['i_risk'];
            $serverArr['i_countersigned'] = '';
            $serverArr['i_applicant'] = '系统';
            $serverArr['i_email'] = 'pms@ifeng.com';
            $serverArr['i_department'] = '技术部 - 运维中心';

            switch ($server['i_type']) {
                case 'batchoffline':
                case 'serveroffline':
                    foreach ($offlineArr as $offline) {
                        if(!empty($offline)){
                            $hostnameArr[] = $offline[1]['val'];
                        }
                    }
                    $serverArr['i_title'] = "{$server['i_title']} - [下线]删除监控子提案";
                    break;
                case 'batchmigrate':
                case 'servermigrate':
                    foreach ($offlineArr as $offline) {
                        if(!empty($offline)) {
                            if ($offline[10]['val'] == '是') {
                                $hostnameArr[] = $offline[1]['val'];
                            }
                        }
                    }
                    $serverArr['i_title'] = "{$server['i_title']} - [迁移下线]删除监控子提案";
                    break;
                default:
                    break;
            }

            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));
            $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

            $serverArr['t_hostname_list'] = implode('<br>', $hostnameArr);

            $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
            if (!$createRes['status']) {
                $this->__sendExtApiErrorMsg('zhangyang7', $id, 'autoCreateIssue', "主提案{$id},创建删除监控子提案时失败");
            }

        }
    }

    /**
     * 服务器监控删除,关闭迁移下线提案或新开迁移上线提案
     * @param $id string
     */
    private function __closeBatchOffline($id)
    {
        $masterInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));

        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_type'),
            array('id' => $masterInfo['master']));

        switch ($server['i_type']) {
            case 'batchoffline':
            case 'serveroffline':
                $remarks = '所有子提案均已完成';
                BusIssueServer::getInstance()->exeServer($masterInfo['master'], $remarks, '', 'api', 'pms', '', '');
                break;
            case 'batchmigrate':
            case 'servermigrate':
                $this->__createBatchOnline($masterInfo['master']);
                break;
            default:
                break;
        }
    }


    /**
     * 迁移上线服务器装机(重装或非重装),提醒申请人和关闭主提案
     * @param $id string
     */
    private function __closeBatchMigrate($id)
    {
        $masterInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_type', 'user'),
            array('id' => $masterInfo['master']));
        BusIssueProcessStatus::getInstance()->sendInformMsg($id, $server['user'], 'inform');

        $sonList = MysqlIssueRelate::getInstance()->getServerRelateList(array('slave', 's_type'),
            array('master' => $masterInfo['master'], 's_type' => array('batchmigrateonline', 'batchmigrateonlinereinstall')));

        $finish = true;
        foreach ($sonList as $son) {
            $server = MysqlIssueServer::getInstance()->getOneServer(array('id'),
                array('id' => $son['slave'], 'i_status' => 4, 'i_result' => 1));
            if (empty($server)) {
                $finish = false;
                break;
            }
        }
        if ($finish) {
            $remarks = '所有子提案均已完成';
            BusIssueServer::getInstance()->exeServer($masterInfo['master'], $remarks, '', 'api', 'pms', '', '');
        }
    }

    /**
     * 创建迁移上线子提案,包括重装,非重装
     * @param $id string
     */
    private function __createBatchOnline($id)
    {
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'), array('id' => $id));
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $offlineArr = json_decode($customInfo['info'], true);

        $headHtml = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>SN</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>内网IP</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>公网IP</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>管理卡IP</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>原机房</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原模块</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>原机架</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>原U位</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>目标机房</th>".
            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>是否重装</th>".
            "</tr></tbody><tbody>";

        $footHtml = "</tbody></table>";

        $midHtml = array('batchmigrateonlinereinstall' => '', 'batchmigrateonline' => '');
        $excelInfo = array();
        foreach ($offlineArr as $offline) {
            if(!empty($offline)){
                $rowHtml = '<tr>';
                foreach ($offline as $c) {
                    if ($c['chg'] == 0) {
                        $color = '';
                    } else {
                        $color = 'color:#e12b31';
                    }
                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;{$color}'>" . $c['val'] . "</td>";
                }
                $rowHtml .= "</tr>";

                if ($offline[10]['val'] == '是') {
                    $midHtml['batchmigrateonlinereinstall'] .= $rowHtml;
                    $excelInfo['batchmigrateonlinereinstall'][] = array($offline[0]['val'], $offline[1]['val'],
                        $offline[2]['val'],$offline[3]['val'], $offline[4]['val'], $offline[5]['val'], $offline[6]['val'],
                        $offline[7]['val'], $offline[8]['val']);
                } else {
                    $midHtml['batchmigrateonline'] .= $rowHtml;
                    $excelInfo['batchmigrateonline'][] = array($offline[0]['val'], $offline[1]['val'],
                        $offline[2]['val'],$offline[3]['val'], $offline[4]['val'], $offline[5]['val'], $offline[6]['val'],
                        $offline[7]['val'], $offline[8]['val']);
                }
            }
        }

        $sonIssue = array('batchmigrateonlinereinstall' => '服务器上线(重装系统)', 'batchmigrateonline' => '服务器上线(非重装系统)');

        $serverArr['i_urgent'] = $server['i_urgent'];
        $serverArr['i_risk'] = $server['i_risk'];
        $serverArr['i_countersigned'] = '';
        $serverArr['i_applicant'] = '系统';
        $serverArr['i_email'] = 'pms@ifeng.com';
        $serverArr['i_department'] = '技术部 - 运维中心';

        $serverArr['i_father'] = $id;

        foreach ($sonIssue as $serverType => $typeName) {

            if (empty($midHtml[$serverType])) {
                continue;
            }

            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1', 'level2', 'level3', 'level4'), array('name' => $serverType));
            $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

            $serverArr['i_type'] = $serverType;

            $serverArr['i_title'] = "{$server['i_title']} - [迁移上线]{$typeName}子提案";

            $serverArr['t_server_list'] = $headHtml . $midHtml[$serverType] . $footHtml;


            $excelFileInfo = $this->__createExcelFile($server['i_title'], $serverType, $excelInfo[$serverType]);
            $excelDir = $excelFileInfo['dir'] . $excelFileInfo['title'] . '.xlsx';

            $red = "<span style='color:#ff0000'>{必须更新迁移后的内网IP,机房,模块,机架,U位信息}</span>";

            $serverArr['t_excel_file'] = "<a href=\"{$excelDir}\">{$excelFileInfo['title']}</a> {$red}";

            $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
            if (!$createRes['status']) {
                $this->__sendExtApiErrorMsg('zhangyang7', $id, 'autoCreateIssue', "主提案{$id},创建{$typeName}子提案时失败");
            }

        }
    }

    /**
     * 创建迁移上线子提案,包括重装,非重装
     * @param $title string
     * @param $type string
     * @param $dataArr array
     * @return string
     */
    private function __createExcelFile($title, $type, $dataArr)
    {
        //创建新的PHPExcel对象
        $phpExcel = new \PHPExcel();
        $phpExcel->getProperties();

        $tableHeader = array(
            'A1' => array('服务器SN号', '9BCD9B'), 'B1' => array('迁移前-主机名', '9BCD9B'),
            'C1' => array('迁移前-内网IP', '9BCD9B'), 'D1' => array('迁移前-外网IP', '9BCD9B'),
            'E1' => array('迁移前-管理卡IP', '9BCD9B'), 'F1' => array('迁移前-机房', '9BCD9B'),
            'G1' => array('迁移前-模块', '9BCD9B'), 'H1' => array('迁移前-机架', '9BCD9B'),
            'I1' => array('迁移前-U位置', '9BCD9B'), 'J1' => array('迁移后-主机名', 'FA8072'),
            'K1' => array('迁移后-内网IP', 'FFFF00'), 'L1' => array('迁移后-管理卡IP', 'FFFF00'),
            'M1' => array('迁移后-机房', 'FFFF00'), 'N1' => array('迁移后-模块', 'FFFF00'),
            'O1' => array('迁移后-机架', 'FFFF00'), 'P1' => array('迁移后-U位置', 'FFFF00')
        );
        $tableWidth = array('A' => 15, 'B' => 20, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 15,
            'I' => 20, 'J' => 15, 'K' => 15, 'L' => 20, 'M' => 20,'N' => 20, 'O' => 20, 'P' => 20);

        switch ($type) {
            case 'batchmigrateonlinereinstall':
                $title = "{$title} [迁移上线] 重装操作系统信息";
                $tableHeader['J1'][1] = 'FFFF00';
                break;
            case 'batchmigrateonline':
                $title = "{$title} [迁移上线] 非重装操作系统信息";

                break;
            default:
                break;
        }

        foreach ($tableHeader as $k => $header) {
            $phpExcel->setActiveSheetIndex(0)->setCellValue($k, $header[0]);
            $phpExcel->getActiveSheet()->getStyle($k)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_BLACK);
            $phpExcel->getActiveSheet()->getStyle($k)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            $phpExcel->getActiveSheet()->getStyle($k)->getFill()->getStartColor()->setRGB($header[1]);
        }

        foreach ($tableWidth as $k => $width) {
            $phpExcel->getActiveSheet()->getColumnDimension($k)->setWidth($width);
        }

        $row = 2;
        foreach ($dataArr as $data) {
            $phpExcel->getActiveSheet()->setCellValue('A' . $row, $data[0]);
            $phpExcel->getActiveSheet()->setCellValue('B' . $row, $data[1]);
            $phpExcel->getActiveSheet()->setCellValue('C' . $row, $data[2]);
            $phpExcel->getActiveSheet()->setCellValue('D' . $row, $data[3]);
            $phpExcel->getActiveSheet()->setCellValue('E' . $row, $data[4]);
            $phpExcel->getActiveSheet()->setCellValue('F' . $row, $data[5]);
            $phpExcel->getActiveSheet()->setCellValue('G' . $row, $data[6]);
            $phpExcel->getActiveSheet()->setCellValue('H' . $row, $data[7]);
            $phpExcel->getActiveSheet()->setCellValue('I' . $row, $data[8]);
            $row++;
        }

//        $title = iconv("utf-8", "gb2312", $title);
        //重命名表
        $phpExcel->getActiveSheet()->setTitle('数据');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $phpExcel->setActiveSheetIndex(0);

        $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');

        $dir = "/download/excel/{$type}/" . date('Ymd');
        $file_dir = APPLICATION_PATH . "/public" . $dir;
        if (!file_exists($file_dir)) {
            mkdir($file_dir, 0777, true);
        }
        $objWriter->save($file_dir . '/' . $title . '.xlsx');

        return array('dir' => $dir . "/", 'title' => $title);
    }

    /**************** HTTPS 证书********************/
    /**
     * ifeng.com 域名申请后,是否创建HTTPS证书子提案
     * @param id string
     */
    private function __createHttpsCertificate($id){
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_urgent','i_risk','i_title','info_id','user'),
            array('id'=>$id));
        $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
            array('id'=>$server['info_id']));
        $infoArr = json_decode($serverInfo['infoJson'],true);

        if($infoArr['t_https'] === '1'){
            $serverArr['i_title'] = "{$server['i_title']} - 申请HTTPS证书";
            $serverArr['i_type'] = 'httpscertificate';
            $serverArr['i_urgent'] = $server['i_urgent'];
            $serverArr['i_risk'] = $server['i_risk'];
            $serverArr['i_countersigned'] = '';
            $serverArr['i_email'] = "{$server['user']}@ifeng.com";
            $serverArr['i_father'] = $id;
            $serverArr['t_domain'] = $infoArr['t_custom_ifeng_domain'];

            $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                array('level1','level2','level3','level4'),array('name' => 'httpscertificate'));
            $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

            $userInfo = MysqlIssueUserInfo::getInstance()->getOneUserInfo(
                array('cname', 'department', 'center', 'group'), array('user' => $server['user']));
            if (empty($userInfo)) {
                $ldapInfo = ExtLdapApi::getInstance()->getAllUserInfo($server['user']);
                $userName = $ldapInfo['name'];
                $depart = implode(' - ', array_filter(array_values($ldapInfo['org'])));
            } else {
                $userName = $userInfo['cname'];
                unset($userInfo['cname']);
                $depart = implode(' - ', array_filter(array_values($userInfo)));
            }

            $serverArr['i_applicant'] = $userName;
            $serverArr['i_department'] = $depart;

            $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
            if (!$createRes['status']) {
                $this->__sendExtApiErrorMsg('likx1', $id, 'autoCreateIssue', "主提案{$id},创建'httpscertificate'子提案时失败");
            }
        }

    }

    /**************** 批量重装 START ********************/
    /**
     * 批量重装,分组创建子提案
     * @param id string
     */
    private function __createBatchOsRefresh($id){

        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_urgent', 'i_risk'), array('id' => $id));
        $customInfo = MysqlIssueCustom::getInstance()->getOneCustom(array('info'), array('sid' => $id));
        $refreshArr = json_decode($customInfo['info'], true);

        $serverArr['i_urgent'] = $server['i_urgent'];
        $serverArr['i_risk'] = $server['i_risk'];
        $serverArr['i_countersigned'] = '';
        $serverArr['i_applicant'] = '系统';
        $serverArr['i_email'] = 'pms@ifeng.com';
        $serverArr['i_department'] = '技术部 - 运维中心';
        $serverArr['i_father'] = $id;

        $sonIssue = ['batchosrefreshphysicalin' => '内部服务器系统重装', 'batchosrefreshphysicalout' => '外部服务器系统重装', 'batchosrefreshvsan' => 'Vsan集群VM重装', 'batchosrefreshexsi' => 'Esxi-VM重装'];

        foreach ($refreshArr as $key => $son) {
            if (!empty($son) && array_key_exists($key, $sonIssue)) {
                $treeInfo = MysqlIssueTree::getInstance()->getOneTreeInfo(
                    array('level1', 'level2', 'level3', 'level4'), array('name' => $key));

                $serverArr['i_name'] = implode('>', array_filter(array_values($treeInfo)));

                $serverArr['i_type'] = $key;
                $serverArr['i_title'] = $server['i_title'] . ' - ' .$sonIssue[$key]. '子提案';

                //物理服务器自动重装
                if (in_array($key, ['batchosrefreshphysicalin', 'batchosrefreshphysicalout'])) {
                    $son = $this->__ioiAutoServerOsRefresh($son);
                }

                $serverArr['t_custom_'.$key] = json_encode($son);

                $createRes = BusIssueServer::getInstance()->createServer($serverArr, 'api', 'pms', false);
                if (!$createRes['status']) {
                    $this->__sendExtApiErrorMsg('likx1', $id, 'autoCreateIssue', "创建子提案'.$key.'失败");
                } else {
                    $createRes['data'];
                    //物理服务器带bond,发邮件提醒
                    if (in_array($key, ['batchosrefreshphysicalin', 'batchosrefreshphysicalout'])) {
                        foreach ($son as $single) {
                            if (!empty($single['bond'])) {
                                //发邮件
                                $this->__sendAttentionMsg(
                                    'likx1, wangwc1, lisai, likuo',
                                    "提案{$createRes['data']}:{$server['i_title']}",
                                    "提案{$createRes['data']}:重装服务器存在bond,请关注重装情况!"
                                );
                                break;
                            }

                        }
                    }
                }
            }
            unset($serverArr['t_custom_'.$key]);
        }

    }

    /**
     * 检测自动重装条件 并走自动重装流程
     * @param $info array
     * @return array
     */
    private function __ioiAutoServerOsRefresh($info)
    {
        $autoOs = ['CentOS-6.5-Server', 'CentOS-7.2-Server', 'Ubuntu-16.04-Server', 'VMware-ESXi6.5', 'Ubuntu-16.04-Desktop'];
        $reInstallInfo = [];

        foreach ($info as $key => $single) {

            if (!in_array($single['os'], $autoOs)) {
                continue;
            } else {
                $token = ExtIdcOsApi::getInstance()->getToken('zhangyang7', 'Jx3tVm$C372s2xxkjJ3=');
                ExtIdcOsApi::getInstance()->setToken($token);

                //获取os信息
                $osRes = BIdcOsConf::getOsTmpSelect();
                if (!$osRes) {
                    file_put_contents(APPLICATION_PATH."/data/log/msg.log", date('Y-m-d H:i:s', time()).' 重装未获取到 OS'.PHP_EOL, FILE_APPEND);
                    continue;
                } else {
                    $reInstallInfo['ostmpname'] = $single['os'];
                    $reInstallInfo['ostmpid'] = array_search($single['os'], $osRes) ;

                    //判断数据来源 获取mac
                    $macRes = ExtCmdbApi::getInstance()->getReinstallInfo($single['sn']);
                    if ($macRes['code'] != 200) {
                        file_put_contents(APPLICATION_PATH."/data/log/msg.log", date('Y-m-d H:i:s', time()).' 重装未获取到 MAC'.PHP_EOL, FILE_APPEND);
                        continue;
                    } else {
                        $reInstallInfo['oobip'] = $macRes['data']['oob_ip'];
                        $reInstallInfo['macaddr'] = $macRes['data']['mac'];

                        //获取idc信息
                        $idcRes = ExtIdcOsApi::getInstance()->getIdcbyIp($single['ip']);
                        if (!$idcRes) {
                            file_put_contents(APPLICATION_PATH."/data/log/msg.log", date('Y-m-d H:i:s', time()).' 重装未获取到 IDC'.PHP_EOL, FILE_APPEND);
                            continue;
                        } else {
                            $reInstallInfo['idc'] = $idcRes['idcname'];
                            $reInstallInfo['idccode'] = $idcRes['idccode'];

                            $reInstallInfo['sn'] = $single['sn'];
                            $reInstallInfo['ip'] = $single['ip'];
                            $reInstallInfo['hardwarename'] = '';
                            $reInstallInfo['hardwareid'] = '';
                            $reInstallInfo['stagewanted'] = 2;
                            $reInstallInfo['hostname'] = $this->__createTempHostname($single['ip'], $single['sn']);
                        }
                    }
                }
            }

            if (!empty($reInstallInfo)) {
                //调用ioi自动重装接口
                $device = json_encode($reInstallInfo);
                $reInstallRes = ExtIoiApi::getInstance()->postDevice($device);

                file_put_contents(APPLICATION_PATH."/data/log/msg.log", date('Y-m-d H:i:s', time()).' 重装传值结果:'.json_encode($reInstallRes).' 重装传值: '.$device.PHP_EOL, FILE_APPEND);

                //标记自动重装标识
                if ($reInstallRes['code'] == 200) {
                    $info[$key]['auto'] = '自动';
                }
            }

        }

        return $info;
    }


    /**
     * 生成重装主机名
     * @param $ip string
     * @param $sn string
     * @return string
     */
    private function __createTempHostname($ip, $sn)
    {
        $time = date('YmdHi', time());
        $left = 'fromifos';
        $mid = $sn.$time;
        $ipExplode = explode('.', $ip);
        $right = $ipExplode['3'].'v'.$ipExplode['2'];

        return $left.'-'.$mid.'-'.$right;
    }



    /**
     * 批量重装,检测所有子提案完成后关闭主提案
     */
    private function __closeBatchOsRefresh($id) {
        $masterInfo = MysqlIssueRelate::getInstance()->getOneServerRelate(array('master'),
            array('slave' => $id, 'type' => 'son'));
        $server = MysqlIssueServer::getInstance()->getOneServer(array('i_title', 'i_type', 'user'),
            array('id' => $masterInfo['master']));
        BusIssueProcessStatus::getInstance()->sendInformMsg($id, $server['user'], 'inform');

        $sonList = MysqlIssueRelate::getInstance()->getServerRelateList(array('slave', 's_type'),
            array('master' => $masterInfo['master'], 's_type' => array('batchosrefreshphysicalin', 'batchosrefreshphysicalout', 'batchosrefreshvsan', 'batchosrefreshexsi')));

        $finish = true;
        foreach ($sonList as $son) {
            $server = MysqlIssueServer::getInstance()->getOneServer(array('id'),
                array('id' => $son['slave'], 'i_status' => 4, 'i_result' => 1));
            if (empty($server)) {
                $finish = false;
                break;
            }
        }
        if ($finish) {
            $remarks = '所有子提案均已完成';
            BusIssueServer::getInstance()->exeServer($masterInfo['master'], $remarks, '', 'api', 'pms', '', '');
        }
    }
    /**************** 批量重装 END ********************/


    /**************** 终止提案检测 start ********************/
    /**
     * 根据提案类型,创建相关提案  php cli/cli.php request_uri='/cli/Autocreate/stop/id/14647'
     */
    public function stopAction()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['id'])) {

            $this->_php = \Yaf\Registry::get('config')->get('php.path');
            $server = MysqlIssueServer::getInstance()->getOneServer(array('i_type'), array('id' => $param['id']));

            if (!empty($server['i_type'])) {

                switch ($server['i_type']) {
                    case 'batchofflineserver':
                        $this->__stopBatchOffline($param['id']);
                        break;

                    default:

                        break;
                }
            }
        }
    }




    /**************** 终止提案检测 end ********************/

    /**
     * 发送报警通知邮件
     * @param $uid string
     * @param $id string
     * @param $api string
     * @param $err string
     * @return array
     */
    private function __sendExtApiErrorMsg($uid, $id, $api, $err)
    {
        $cmd = "{$this->_php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendExtApiErrorEmail/uid/{$uid}/id/{$id}/api/{$api}/err/{$err}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

    /**
     * 发送提醒Email和Rtx
     * @param $uid string
     * @param $id string
     * @param $msg string
     */
    private function __sendAttentionMsg($uid, $subject, $msg)
    {
        $cmd = "{$this->_php} " . APPLICATION_PATH . "/cli/cli.php request_uri='/cli/email/sendEmailAndRtx/uid/{$uid}/subject/{$subject}/msg/{$msg}' &";
        $r = popen($cmd, 'r');
        pclose($r);
    }

}
