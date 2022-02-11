<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class TestController extends \Base\Controller_AbstractWechat
{

    public function indexAction()
    {
        $con = dirname(__FILE__);

        $jsonDir = $con.'/SiegeMatch-2022020201000012.json';

        $jsonString = file_get_contents($jsonDir);

        $data = json_decode($jsonString, true);
        
        Tools::returnAjaxJson($data);
    }

}
