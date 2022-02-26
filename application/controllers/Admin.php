<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class AdminController extends \Base\Controller_AbstractWechat
{
    public function adminAction()
    {

    }

/*
 * 后台录入车辆信息
 */
    public function setSellContentAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlCommon::getInstance()->addInfoByTableName('car_sell_content', $postInfo);
            $res['code'] = 200;
            $res['data'] = $postInfo;
        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    public function getBrandListAction()
    {
        try{

            $brands = MysqlCommon::getInstance()->getListByTableName("car_brand", null, null, $order = null, $group = null);
            $res['code'] = 200;
            $res['data'] = $brands;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    public function getBrandTagsAction()
    {
        $brand_id = $this->getRequest()->getPost();

        try{

            $tags = MysqlCommon::getInstance()->getListByTableName("car_brand_tags", null, ['brand_id'=>$brand_id], $order = null, $group = null);
            $res['code'] = 200;
            $res['data'] = $tags;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

}
