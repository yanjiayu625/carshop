<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class IndexController extends \Base\Controller_AbstractWechat
{
    public function indexAction()
    {
    }

    public function getSellListAction()
    {
        $list = $brandList = MysqlCommon::getInstance()->getListByTableName('car_sell_list', ['title', 'pic_dir',
            'register_date', 'mileage', 'sell_price'], ['is_home'=>1,'status'=>1], 'id desc');

        $res['code'] = 200;
        $res['data'] = $list;

        Tools::returnAjaxJson($res);
    }



}
