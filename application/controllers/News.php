<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class NewsController extends \Base\Controller_AbstractWechat
{
    public function newsAction()
    {
        $news = MysqlCommon::getInstance()->getListByTableName("car_news",['id','title', 'content', 'update_time', 'type'],['status'=>1],"id DESC");
        $this->getView()->assign(['news'=>$news]);
    }

    public function contentAction()
    {
        $id = $this->getRequest()->getQuery("id");
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        $news = MysqlCommon::getInstance()->getInfoByTableName("car_news",['id','title', 'content', 'update_time', 'type'],['status'=>1, 'id'=>$id]);
        if(empty($news)) {
            return false;
        }
        $this->getView()->assign(['news'=>$news]);
    }

}
