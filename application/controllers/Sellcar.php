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