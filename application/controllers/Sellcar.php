<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class SellcarController extends \Base\Controller_AbstractWeb
{
    public function SellcarAction()
    {
        $this->display("sellcar");
    }

    public function getInfoAction()
    {
        $params = $this->getRequest()->getQuery();
        if (empty($params)) {
            return false;
        }


        try{
            if (!empty($params['brand'])) {
                $brands = MysqlCommon::getInstance()->getListByTableName('car_brand', null, null);

                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">品牌</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                if (!empty($brands)) {
                    foreach ($brands as $brand) {

                        $list .= "<li data-index='".$brand['id']."' option-name=\"brand\" class=\"selectbrand\">".$brand['sort_letter']." ".$brand['brand_name']."</li>";

                    }
                }
                $list .= "</ul></div>";
                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['subbrand']) && !empty($params['abrandid'])) {
                $tags = MysqlCommon::getInstance()->getListByTableName('car_brand_tags', null, ['brand_id'=>$params['abrandid']]);

                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback_subbrand\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">车系</div></header><ul class=\"brandlist optionlist\">";
                if (!empty($tags)) {
                    foreach ($tags as $tag) {

                        $list .= "<li data-index='".$tag['id']."' option-name=\"series\" data-brand='".$tag['brand_id']."' class=\"selectseries\">".$tag['name']."</li>";

                    }
                }
                $list .= "</ul>";
                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['cx'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">车型</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"小型车\" option-name=\"cx\" class=\"selectcx\">小型车</li>";
                $list .= "<li data-index=\"中型车\" option-name=\"cx\" class=\"selectcx\">中型车</li>";
                $list .= "<li data-index=\"中大型车\" option-name=\"cx\" class=\"selectcx\">中大型车</li>";
                $list .= "<li data-index=\"微型车\" option-name=\"cx\" class=\"selectcx\">微型车</li>";
                $list .= "<li data-index=\"豪华车\" option-name=\"cx\" class=\"selectcx\">豪华车</li>";
                $list .= "<li data-index=\"紧凑车型\" option-name=\"cx\" class=\"selectcx\">紧凑车型</li>";
                $list .= "<li data-index=\"MPV\" option-name=\"cx\" class=\"selectcx\">MPV</li>";
                $list .= "<li data-index=\"SUV\" option-name=\"cx\" class=\"selectcx\">SUV</li>";
                $list .= "<li data-index=\"跑车\" option-name=\"cx\" class=\"selectcx\">跑车</li>";
                $list .= "<li data-index=\"皮卡\" option-name=\"cx\" class=\"selectcx\">皮卡</li>";
                $list .= "<li data-index=\"面包车\" option-name=\"cx\" class=\"selectcx\">面包车</li>";
                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['bsx'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">变速箱</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"自动\" option-name=\"bsx\" class=\"selectbsx\">自动</li>";
                $list .= "<li data-index=\"手动\" option-name=\"bsx\" class=\"selectbsx\">手动</li>";
                $list .= "<li data-index=\"手自一体\" option-name=\"bsx\" class=\"selectbsx\">手自一体</li>";
                $list .= "<li data-index=\"双离合\" option-name=\"bsx\" class=\"selectbsx\">双离合</li>";

                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['import'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">国产/进口</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"国产\" option-name=\"import\" class=\"selectimport\">国产</li>";
                $list .= "<li data-index=\"进口\" option-name=\"import\" class=\"selectimport\">进口</li>";

                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['dl'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">动力类型</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"1\" option-name=\"dl\" class=\"selectdl\">汽油</li>";
                $list .= "<li data-index=\"2\" option-name=\"dl\" class=\"selectdl\">柴油</li>";
                $list .= "<li data-index=\"3\" option-name=\"dl\" class=\"selectdl\">纯电</li>";
                $list .= "<li data-index=\"4\" option-name=\"dl\" class=\"selectdl\">油电混动</li>";

                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['clyt'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">车辆用途</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"营运\" option-name=\"clyt\" class=\"selectclyt\">营运</li>";
                $list .= "<li data-index=\"非营运\" option-name=\"clyt\" class=\"selectclyt\">非营运</li>";

                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['by'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">定期保养</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"是\" option-name=\"by\" class=\"selectby\">是，定期保养</li>";
                $list .= "<li data-index=\"否\" option-name=\"by\" class=\"selectby\">否，未定期保养</li>";

                $res['code'] = 200;
                $res['data'] = $list;
            }

            if (!empty($params['pf'])) {
                $list = " <header class=\"mo-header-select\"><div class=\"mo-header-left\"><a href=\"javascript:;\" id=\"reback\"><i class=\"layui-icon\" style=\"color:#666;\">返回</i></a></div><div class=\"mo-header-title\">排放标准</div></header><div class=\"brandbox\"><ul class=\"brandlist\">";
                $list .= "<li data-index=\"国一\" option-name=\"pf\" class=\"selectpf\">国一</li>";
                $list .= "<li data-index=\"国二\" option-name=\"pf\" class=\"selectpf\">国二</li>";
                $list .= "<li data-index=\"国三\" option-name=\"pf\" class=\"selectpf\">国三</li>";
                $list .= "<li data-index=\"国四\" option-name=\"pf\" class=\"selectpf\">国四</li>";
                $list .= "<li data-index=\"国五\" option-name=\"pf\" class=\"selectpf\">国五</li>";
                $list .= "<li data-index=\"国六\" option-name=\"pf\" class=\"selectpf\">国六</li>";

                $res['code'] = 200;
                $res['data'] = $list;
            }


        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);


    }

    public function carInfoAction()
    {
        $params = $this->getRequest()->getPost();
        if (empty($params)) {
            return false;
        }

        try {
            MysqlCommon::getInstance()->addInfoByTableName('car_sell_content', $params);
            $res['code'] = 200;
            $res['data'] = $params;
        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

}