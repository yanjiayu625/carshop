<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class DetailsController extends \Base\Controller_AbstractWechat
{
    public function detailsAction()
    {
        $this->display("details");
    }

}
