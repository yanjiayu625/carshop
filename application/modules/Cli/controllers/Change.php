<?php

use \Validate\Validate;
use \Mysql\Issue\ServerModel     as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel as MysqlIssueServerInfo;
use \Mysql\Issue\CustomInfoModel as MysqlIssueCustomInfo;
use \Business\Issue\ServerModel  as BusIssueServer;
use \Mysql\Issue\CliModel        as MysqlIssueCLi;
use \ExtInterface\Cmdb\NewapiModel as ExtNewCmdbApi;
use \ExtInterface\Dcm\ApiModel as ExtDcmApi;

/**
 * 变更提案内容模块
 */
class ChangeController extends \Base\Controller_AbstractCli
{

    /**
     *  变更提案详细内容数据 php cli/cli.php request_uri='/cli/Change/changeTableContent/id/25882/cid/1'
     */
    public function changeTableContentAction()
    {
        $param = $this->getRequest()->getParams();

        try {

            if (empty($param['id'])) {
                throw new \Exception("输入参数错误:提案ID为空");
            }

            if (empty($param['cid'])) {
                throw new \Exception("输入参数错误:CLI执行ID为空");
            }

            $server = MysqlIssueServer::getInstance()->getOneServer(['info_id'], ['id' => $param['id']]);
            $serverInfo = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $server['info_id']));
            $infoArr = json_decode($serverInfo['infoJson'], true);

            foreach ($infoArr as $key => $val) {

                switch ($key) {

                    case 't_custom_head':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $head = array();
                        foreach ($dataArr as $data) {
                            $head[] = $data['name'] . ' ' . $data['mobile'];
                        }
                        $infoArr[$key] = implode('<br>', $head);
                        break;

                    case 't_custom_domain':
                        $infoArr[$key] = $val . '.staff.ifeng.com';
                        break;

                    case 't_custom_ifengidc_domain':
                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='20%'>域名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>类型</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='30%'>data</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>ttl</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>操作类型</th>" .
                            "</tr></tbody><tbody>";

                        foreach ($dataArr as $data) {
                            if (!empty($data['id'])) {
                                $domainInfo = ExtDcmApi::getInstance()->getDomainInfoById($data['id']);
                            }

                            if ($data['change_type'] == 'update') {
                                $data['host'] = $domainInfo['data']['host'];
                                $data['type'] = $domainInfo['data']['type'];

                                if ($domainInfo['data']['data'] != $data['data']) {
                                    $data['data'] = $domainInfo['data']['data'].' -> '.$data['data'];
                                }
                                if ($domainInfo['data']['ttl'] != $data['ttl']) {
                                    $data['ttl'] = $domainInfo['data']['ttl'].' -> '.$data['ttl'];
                                }
                            } elseif ($data['change_type'] == 'delete' || $data['change_type'] == 'no_change') {
                                $data['host'] = $domainInfo['data']['host'];
                                $data['type'] = $domainInfo['data']['type'];
                                $data['data'] = $domainInfo['data']['data'];
                                $data['ttl'] = $domainInfo['data']['ttl'];
                            }

                            if ($data['change_type'] == 'add') {
                                $changeType = '增加';
                            } elseif ($data['change_type'] == 'update') {
                                $changeType = '更新';
                            } elseif ($data['change_type'] == 'delete') {
                                $changeType = '删除';
                            } else {
                                $changeType = '无变更';
                            }

                            $rowHtml = '<tr>';
                            $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $data['host'].'.ifengidc.com' . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $data['type'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $data['data'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $data['ttl'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $changeType . "</td>";
                            $rowHtml .= "</tr>";

                            $html .= $rowHtml;
                        }
                        $html .= "</tbody></table>";

                        $infoArr[$key] = $html;
                        break;


                    case 't_custom_ifengimg_domain':
                    case 't_custom_other_domain':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $domain = array();
                        foreach ($dataArr as $data) {
                            $domain[] = '运营商:' . $data['isp'] .
                                ' 域名:' . $data['hostname'] . $data['secondLevelDomain'] . ' '
                                . $data['AC'] . ':' . $data['addr'];
                        }
                        $infoArr[$key] = implode('<br>', $domain);
                        break;

                    case 't_custom_ifeng_domain':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $domain = array();
                        foreach ($dataArr as $data) {
                            $domain[] = '运营商:' . $data['isp'] .
                                ' 域名:' . $data['hostname'] . $data['secondLevelDomain'] . ' '
                                . $data['AC'] . ':' . $data['addr'];
                        }
                        $infoArr[$key] = implode('<br>', $domain);
                        break;

                    case 't_custom_server_online':
                        $infoArr[$key] = $val;
                        break;

                    case 't_custom_server_batch_online':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
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
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10'>上线机房</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
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

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_server_batch_offline':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $total = count($dataArr);
                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody>
                            <tr><th colspan='9' style='text-align:left !important; padding: 8px 20px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;'>总计:  {$total} 台</th></tr><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>SN号</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='22%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>内网IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>公网IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>管理卡IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原模块</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原机架</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原U位</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
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

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_server_batch_migrate':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $total = count($dataArr);
                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody>
                            <tr><th colspan='11' style='text-align:left !important; padding: 8px 20px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;'>总计:  {$total} 台</th></tr><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>内网IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>公网IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>管理卡IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>原机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原模块</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>原机架</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>原U位</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>目标机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>是否重装</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
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

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_server_batch_refresh':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>操作系统</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
                            if (!empty($rows)) {
                                foreach ($rows as $c) {
                                    $rowHtml = '<tr>';
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['hostname'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['os'] . "</td>";
                                    $rowHtml .= "</tr>";
                                    $html .= $rowHtml;
                                }
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batchosrefreshphysicalin':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>操作系统</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>BOND</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>自动重装</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['hostname'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['os'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['bond'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'><span class='label label-success'>" . $c['auto'] . "</span></td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batchosrefreshphysicalout':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>操作系统</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>BOND</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>自动重装</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['hostname'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['os'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['bond'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'><span class='label label-success'>" . $c['auto'] . "</span></td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batchosrefreshvsan':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>操作系统</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['hostname'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['os'] . "</td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batchosrefreshexsi':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>操作系统</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['hostname'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['os'] . "</td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batchinputmanualinfo':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        switch (key($dataArr)) {
                            case 'vcenter':
                                $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>SN</th>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>内网IP</th>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>逻辑CPU核数</th>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>内存总容量(G)</th>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>逻辑空间总容量(G)</th>".
                                    "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>操作系统版本</th>".
                                    "</tr></tbody><tbody>";
                                break;
                            default:
                                break;
                        }

                        foreach ($dataArr[key($dataArr)] as $rows){
                            $rowHtml = '<tr>';
                            foreach ($rows as $c){
                                if($c['chg'] == 0 ){$color = ''; }else{ $color = 'color:#e12b31'; }
                                $rowHtml .=  "<td style='padding: 5px 3px; text-align: center;{$color}'>" .$c['val']."</td>";
                            }
                            $rowHtml .= "</tr>";

                            $html .= $rowHtml;
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    // 系统运维相关--申请公钥推送
                    case 't_custom_pushkey_ip_list':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);
                        $ipArr = explode("\n", $dataArr['ip']);

                        $ipListArr = [];
                        foreach ($ipArr as $ip) {
                            $psInfo = ExtNewCmdbApi::getInstance()->getPhysicsServerInfoByIp($ip);
                            if ($psInfo['code'] == '200' && !empty($psInfo['data']['sn'])) {
                                $host = $psInfo['data']['hostname'];
                            } else {
                                $vsInfo = ExtNewCmdbApi::getInstance()->getVirtualServerInfoByIp($ip);
                                if($vsInfo['code'] == '200' && !empty($vsInfo['data']['sn'])){
                                    $host = $vsInfo['data']['hostname'];
                                }else{
                                    $host = '';
                                }
                            }
                            $ipListArr[] = $ip.'    '.$host;
                        }

                        $ipList = implode('<br>', $ipListArr);
                        $infoArr[$key] = $ipList;
                        break;

                    // 系统权限相关--申请服务器权限
                    case 't_custom_server_permission_ip_list':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='box col-md-12 details'>";
                        foreach ($dataArr as $data) {
                            //连续IP 10.50.1.[10-20]
//                            $iplist = $this->__checkIpStrToArray($data['IPlist'][0]);
//
//                            if ($data['status'] == '1') {
//                                $html .= '<tr><td width="200px" class="red">' . $data['name'] . '</td><td class="red">' . $iplist . '</td></tr>';
//                            } else {
//                                $html .= '<tr><td width="200px">' . $data['name'] . '</td><td>' . $iplist . '</td></tr>';
//                            }

                            //非连续IP 10.50.1.10
                            $iplist = '';
                            $ipArr = explode(',', $data['IPlist'][0]);

                            foreach ($ipArr as $ipStr) {
                                $pos = strpos($ipStr, ':');
                                if ($pos !== false) {
                                    $ip = substr($ipStr, 0, $pos);
                                } else {
                                    $ip = $ipStr;
                                }
                                $psInfo = ExtNewCmdbApi::getInstance()->getPhysicsServerInfoByIp($ip);
                                if ($psInfo['code'] == '200' && !empty($psInfo['data']['sn'])) {
                                    $host = $psInfo['data']['hostname'];
                                } else {
                                    $vsInfo = ExtNewCmdbApi::getInstance()->getVirtualServerInfoByIp($ip);
                                    if($vsInfo['code'] == '200' && !empty($vsInfo['data']['sn'])){
                                        $host = $vsInfo['data']['hostname'];
                                    }else{
                                        $host = '';
                                    }
                                }
                                $iplist .= $ipStr.'    '.$host.'<br>';
                            }

                            if ($data['status'] == '1') {
                                $html .= "<tr><td width='200px' class='red'>" . $data['name'] . "</td><td class='red'>" . $iplist . '</td></tr>';
                            } else {
                                $html .= "<tr><td width='200px'>" . $data['name'] . "</td><td>" . $iplist . '</td></tr>';
                            }

                        }
                        $html .= '</table><div class="clear"></div>';
                        $infoArr[$key] = $html;
                        break;

                    // 系统权限相关--续订服务器权限
                    case 't_custom_renew_server_permission_ip_list':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = '';
                        $html .= "<table style='width:400px' class='box col-md-12 table table-bordered'><tbody>";
                        $html .= "<tr><td width='70%'>服务器</td><td  width='30%'>续订</td></tr>";
                        foreach ($dataArr as $data) {
                            $html .= "<tr><td>{$data['ip']}</td>";
                            if ($data['renew'] === 'true') {
                                $renew = '是';
                                $color = '';
                            } elseif ($data['renew'] === 'false') {
                                $renew = '否';
                                $color = '#f00';
                            } else {
                                $renew = '未确认';
                                $color = '';
                            }
                            $html .= "<td  style='color:$color'>$renew</td></tr>";
                        }
                        $html .= "</tbody></table>";

                        $infoArr[$key] = $html;
                        
                        break;

                    case 't_create_relate_issue':
                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        if ($dataArr == null) {
                            $html = $val;
                        } else {
                            $html = '';
                            foreach ($dataArr as $data) {
                                $type = $data['type'] == 'son' ? '子服务' : '相关服务';
                                $check = $data['check'] == '1' ? '不免审' : '免审';
                            }
                            $html .= "审批后自动创建 {$check} {$type} ,服务类型:{$data['name']}<br>";
                        }

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_yum_software':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $soft = array();
                        foreach ($dataArr as $data) {
                            $soft[] = '名称:' . $data['name'] . ' ' . '版本:' . $data['version'];
                        }
                        $infoArr[$key] = implode('<br>', $soft);
                        break;

                    case 't_custom_nat_ip':
                    case 't_custom_nat_ip_syq':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $ip = array();
                        foreach ($dataArr as $data) {
                            $port = empty($data['port']) ? '' : '目的端口:'.$data['port'];
                            $type = empty($data['type']) ? '' : '协议:'.$data['type'];
                            $ip[] = $data['ip'] . ' ' . $type . ' ' . $port;
                        }
                        $infoArr[$key] = implode('<br>', $ip);
                        break;

                    case 't_custom_coordination':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = '';
                        foreach ($dataArr as $data) {
                            $html .= "<table class='box col-md-12 table table-bordered'><tbody>";
                            $html .= "<tr><td width='100px'>内网IP</td><td>{$data['ip']}</td></tr>";
                            $html .= "<tr width='100px'><td width='100px'>协调内容</td><td>{$data['desc']}</td></tr>";

                            $timeStr = empty($data['time']) ? '需要业务负责人和运维负责人确认' : $data['time'];
                            $html .= "<tr width='100px'><td>协调时间</td><td><span style='color: red'>$timeStr</span></td></tr></tbody></table><br>";
                        }
                        $infoArr[$key] = $html;

                        break;

                    case 't_custom_machine':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = $url = '';
                        foreach ($dataArr as $data) {
                            $html .= "<table class='box col-md-12 table table-bordered'><tbody>";
                            $html .= "<tr><td width='100px'>品牌型号</td><td>{$data['brand']} {$data['model']}</td><td width='100px'>内网IP</td><td>{$data['int_ip']}</td></tr>";
                            $html .= "<tr><td width='100px'>SN</td><td>{$data['sn']}</td><td width='100px'>公网IP</td><td>{$data['ext_ip']}</td></tr>";
                            $html .= "<tr><td width='100px'>主机名</td><td>{$data['hostname']}</td><td width='100px'>管理卡IP</td><td>{$data['oob_ip']}</td></tr>";
                            $html .= "<tr><td width='100px'>机房位置</td><td>{$data['idc_name']} {$data['idc_module']} {$data['cabinet_num']}  {$data['u_bit']}</td><td width='100px'>入账日期</td><td>{$data['arrival']}</td></tr>";

                            if (!empty($data['type'])) {
                                $typeStr = implode(',', $data['type']);
                                $html .= "<tr><td width='100px'>故障类型</td><td colspan='3'>{$typeStr}</td></tr>";
                            }

                            $html .= "<tr><td>故障描述</td><td colspan='3'>{$data['desc']}</td></tr>";

                            if (!empty($data['server'])) {
                                foreach ($data['server'] as $serverId) {
                                    $url .= " <a href = 'detail?id={$serverId}'><span class='btn btn-primary btn-sm js_showDetail' >{$serverId}</span></a>";
                                }

                                $html .= "<tr><td width='100px'>已报修次数</td><td>{$data['num']} 次</td><td width='100px'>已报修提案</td><td>{$url}</td></tr>";
                            }

                            if (!empty($data['img'])) {
                                $imgArr = explode(',', $data['img']);
                                foreach ($imgArr as $k => $v) {
                                    $image_file = APPLICATION_PATH . '/public' . $v;
                                    $image_info = getimagesize($image_file);
                                    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
                                    $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
                                    $imgArr[$k] = "<img style='margin-top:10px' width='500px' src='{$base64_image}' alt=''>";
                                }
                                $imgStr = implode('', $imgArr);
                            } else {
                                $imgStr = '';
                            }
                            $html .= "<tr><td>截图</td><td colspan='3'>{$imgStr}</td></tr>";
                            $html .= "</tbody></table>";
                            $html .= "<br>";
                        }

                        $infoArr[$key] = $html;

                        break;
                    
                    case  't_custom_machine_exe':
                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $data = json_decode($customInfo['info'], true);

                        $html = '';
                        if (!empty($data['type'])) {
                            $html .= "<table class='box col-md-12 table table-bordered'><tbody>";
                            $typeStr = implode(',', $data['type']);
                            $html .= "<tr><td width='100px'>故障类型</td><td colspan='3'>{$typeStr}</td></tr>";
                            $html .= "<tr><td width='100px'>维修说明</td><td colspan='3'>{$data['desc']}</td></tr>";
                            $html .= "</tbody></table>";
                            $html .= "<br>";
                        }

                        $infoArr[$key] = $html;
                        
                        break;

                    case 't_custom_createaccount':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center' width='5%'>序号</th>" .
                            "<th style='text-align:center' width='15%'>姓名</th>" .
                            "<th style='text-align:center' width='15%'>账号</th>" .
                            "<th style='text-align:center' width='15%'>手机号</th>" .
                            "<th style='text-align:center' width='15%'>邮箱群组</th>" .
                            "<th style='text-align:center' width='15%'>实际账号</th>" .
                            "<th style='text-align:center' width='15%'>初始密码</th>" .
                            "</tr></tbody><tbody>";
                        $id = 1;
                        foreach ($dataArr as $user) {
                            $rowHtml = '<tr>';
                            $rowHtml .= "<td>{$id}</td>";

                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['name'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['uid'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['mobile'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['email'] . "</td>";

                            $rowHtml .= "<td></td><td></td>";
                            $rowHtml .= "</tr>";

                            $html .= $rowHtml;
                            $id++;
                        }
                        $html .= "</tbody></table>";

                        $infoArr[$key] = $html;

                        break;

                    case 't_custom_yidian_ifeng':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center' width='5%'>序号</th>" .
                            "<th style='text-align:center' width='20%'>员工姓名</th>" .
                            "<th style='text-align:center' width='25%'>所属部门</th>" .
                            "<th style='text-align:center' width='25%'>一点办公区IP</th>" .
                            "<th style='text-align:center' width='25%'>需访问凤凰网IP</th>" .
                            "</tr></tbody><tbody>";
                        $id = 1;
                        foreach ($dataArr as $user) {
                            $rowHtml = '<tr>';
                            $rowHtml .= "<td>{$id}</td>";

                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['name'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['department'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['yidianip'] . "</td>";
                            $rowHtml .= "<td style='padding: 5px 3px'>" . $user['ifengip'] . "</td>";

                            $rowHtml .= "</tr>";

                            $html .= $rowHtml;
                            $id++;
                        }
                        $html .= "</tbody></table>";

                        $infoArr[$key] = $html;

                        break;

                    case 't_custom_firmwarechanges_excel':

                        break;

                    case 't_custom_idc_change':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $typeStr = array('add' => '添加', 'del' => '删除', 'none' => '无');
                        $idcStr = array('name' => '机房名称', 'short' => '机房缩写', 'address' => '机房地址',
                            'account_manager' => '客户经理', 'mobile' => '经理电话', 'email' => '经理邮件', 'duty_phone' => '值班电话',
                            'licensor' => '授权人', 'work_order_email' => '工单邮箱');

                        $html = "<table class=\"table-bordered\" width='100%'>
                                 <tbody><tr><td >机房</td ><td>操作类型:{$typeStr[$dataArr['idc']['type']]}</td></tr>";

                        if ($dataArr['idc']['type'] == 'add') {
                            $html .= "<tr><td>详情</td><td>";
                            foreach ($dataArr['idc']['idc_info'] as $k => $idcInfo) {
                                $html .= "<p>{$idcStr[$k]}:{$idcInfo}</p>";
                            }
                        } elseif ($dataArr['idc']['type'] == 'del') {
                            $html .= "<tr><td>详情</td><td>";
                            $html .= "<p>机房名称:{$dataArr['idc']['idc_info']['name']}</p>";
                        }

                        $html .= "</td></tr><tr><td>机柜</td><td>操作类型:{$typeStr[$dataArr['cabinet']['type']]}</td></tr>";

                        if ($dataArr['cabinet']['type'] == 'add') {
                            $html .= "<tr><td>详情</td><td>";
                            $html .= "机房名称:{$dataArr['cabinet']['idc_info']['name']}";
                            foreach ($dataArr['cabinet']['cabinet_info'] as $cabinetInfo) {
                                $html .= "<p>模块名称:{$cabinetInfo['idc_module']} 机柜号:{$cabinetInfo['cabinet_num']}</p>";
                            }
                        } elseif ($dataArr['cabinet']['type'] == 'del') {
                            $html .= "<tr><td>详情</td><td>";
                            $html .= "机房名称:{$dataArr['cabinet']['idc_info']['name']}";
                            foreach ($dataArr['cabinet']['cabinet_info'] as $cabinetInfo) {
                                $html .= "<p>机柜信息:{$cabinetInfo['name']}</p>";
                            }
                        }

                        $html .= "</td></tr></tbody></table>";

                        $infoArr[$key] = $html;

                        break;

                    case 't_custom_apply_network_access':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        if ($infoArr['t_type_name'] == 5 || $infoArr['t_type_name'] == 6) {
                            $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>IP地址</th>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='20%'>Mac地址</th>" .
                                "</tr></tbody><tbody>";
                            foreach ($dataArr as $c) {
                                if (!empty($c)) {
                                    $rowHtml = '<tr>';
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['mac'] . "</td>";
                                    $rowHtml .= "</tr>";
                                    $html .= $rowHtml;
                                }
                            }

                        } else {
                            $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>源IP</th>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='6%'>源端口</th>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='5%'>协议</th>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>目的IP</th>" .
                                "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='6%'>目的端口</th>" .
                                "</tr></tbody><tbody>";
                            foreach ($dataArr as $c) {
                                if (!empty($c)) {
                                    $rowHtml = '<tr>';
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sourceip'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sourceport'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['protocol'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['aimip'] . "</td>";
                                    $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['aimport'] . "</td>";
                                    $rowHtml .= "</tr>";
                                    $html .= $rowHtml;
                                }
                            }
                        }

                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batch_offline_virtual_server':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>主机名</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['hostname'] . "</td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_switch_batch_online':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>模块</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>机柜</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>U位</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>是否新增</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
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

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_switch_batch_offline':

                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>主机名</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原模块</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原机柜</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>原U位</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>退库机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>退库模块</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>退库区域</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
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

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_virtual_machine_changes':
                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='12%'>虚拟机IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>CPU</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>内存</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>网卡</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='8%'>硬盘</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $rows) {
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

                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_batch_offline_storage_device':
                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>SN</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原品牌</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原机房</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原模块</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原机柜</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>原U位</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='10%'>下线位置</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['sn'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['brand'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['idc_name'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['idc_module'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['cabinet_num'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['u_bit'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['idc'] . "</td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    case 't_custom_server_list':
                        $customInfo = MysqlIssueCustomInfo::getInstance()->getOneCustom(array('info'),
                            array('sid'=>$param['id'],'type'=>$key));
                        $dataArr = json_decode($customInfo['info'], true);

                        $html = "<table class='table-bordered' style='text-align: center;width:100%'><tbody><tr>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>IP</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>端口</th>" .
                            "<th style='text-align:center !important; padding: 8px 2px;overflow: hidden;text-overflow: ellipsis;white-space: pre-line;' width='15%'>协议</th>" .
                            "</tr></tbody><tbody>";
                        foreach ($dataArr as $c) {
                            if (!empty($c)) {
                                $rowHtml = '<tr>';
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['ip'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['port'] . "</td>";
                                $rowHtml .= "<td style='padding: 5px 3px; text-align: center;'>" . $c['protocol'] . "</td>";
                                $rowHtml .= "</tr>";
                                $html .= $rowHtml;
                            }
                        }
                        $html .= "</tbody></table>";
                        $infoArr[$key] = $html;
                        break;

                    default:

                        break;
                }
            }

            $updateRes = MysqlIssueServerInfo::getInstance()->updateServerInfo(
                array('infoJson' => json_encode($infoArr, JSON_UNESCAPED_UNICODE)), array('id' => $server['info_id']));
            if(!$updateRes){
                throw new \Exception("变更提案详细内容时:更新提案详细内容时Mysql出错");
            }else{
                BusIssueServer::getInstance()->MysqlToRedisServer($param['id']);
            }

            $result = 'success';

        } catch (\Exception $e) {

            $result = $e->getMessage();

        }

        MysqlIssueCLi::getInstance()->updateCliInfo(array('status'=>1,'result'=>$result),array('id'=>$param['cid']));

    }

    /**
     * 检验IP合法性,并组装解析后的数组
     * @param $ipString string
     * @return bool|string
     */
    private function __checkIpStrToArray($ipString)
    {
        $ipStrArr = explode(",", trim($ipString));
        $ipData = array();
        $res = '';

        if (!empty($ipStrArr)) {
            foreach ($ipStrArr as $ipStr) {
                if (!empty($ipStr)) {
                    $ipNum = array();
                    $ipArr = explode('.', trim($ipStr));
                    $ipNum[0] = array((int)$ipArr[0]);

                    if (count($ipArr) != 4) {
                        throw new \Exception("{$ipStr} 格式错误,请确认");
                    }

                    if (!Validate::isInt($ipArr[0]) || $ipArr[0] < 0 || $ipArr[0] > 255) {
                        throw new \Exception("{$ipStr} 中 {$ipArr[0]} 错误,请确认");
                    }

                    for ($x = 1; $x <= 3; $x++) {
                        if (strstr($ipArr[$x], '-') !== false) {

                            $ipSArr = explode('-', $ipArr[$x]);

                            if (substr($ipSArr[0], 0, 1) != '[') {
                                throw new \Exception("{$ipStr} 中 {$ipArr[$x]} 错误,请确认");
                            } else {
                                if (substr($ipSArr[0], 1) < 0 || substr($ipSArr[0], 1) > 255) {
                                    throw new \Exception("{$ipStr} 中 {$ipArr[$x]} 错误,请确认");
                                }
                            }

                            if (substr($ipSArr[1], -1, 1) != ']') {
                                throw new \Exception("{$ipStr} 中 {$ipArr[$x]} 错误,请确认");
                            } else {
                                if (substr($ipSArr[1], 0, -1) < 0 || substr($ipSArr[1], 0, -1) > 255) {
                                    throw new \Exception("{$ipStr} 中 {$ipArr[$x]} 错误,请确认");
                                }
                            }

                            if (substr($ipSArr[0], 1) > substr($ipSArr[1], 0, -1)) {
                                throw new \Exception("{$ipStr} 中 {$ipArr[$x]} 错误,请确认");
                            }

                            $sIp = substr($ipSArr[0], 1);
                            $eIp = substr($ipSArr[1], 0, -1);

                            for ($i = $sIp; $i <= $eIp; $i++) {
                                $ipNum[$x][] = (int)$i;
                            }

                        } else {
                            if (!Validate::isInt($ipArr[$x]) || $ipArr[$x] < 0 || $ipArr[$x] > 255) {
                                throw new \Exception("{$ipStr} 中 {$ipArr[$x]} 错误,请确认");
                            } else {
                                $ipNum[$x] = array((int)$ipArr[$x]);
                            }
                        }
                    }
                }

                foreach ($ipNum[0] as $ip0) {
                    foreach ($ipNum[1] as $ip1) {
                        foreach ($ipNum[2] as $ip2) {
                            foreach ($ipNum[3] as $ip3) {
                                $ipData[] = $ip0 . '.' . $ip1 . '.' . $ip2 . '.' . $ip3;
                            }
                        }
                    }
                }

            }

            $res = implode('<br>', array_unique($ipData));
        }

        return $res;
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
