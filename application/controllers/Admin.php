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

    /**
     * 发文章
     */
    public function newsAction()
    {

    }

    /**
     * 保存文章内容
     */
    public function setContentAction()
    {
        $postInfo = $this->getRequest()->getPost();
        
        try{

            if (empty($postInfo['type'])) {
                throw new \Exception('未选择文章发布的频道!');
            }

            if (empty($postInfo['title'])) {
                throw new \Exception('文章标题为空!');
            }

            if (empty($postInfo['content'])) {
                throw new \Exception('文章内容为空!');
            }

            $addContent = MysqlCommon::getInstance()->addInfoByTableName("car_news",['title'=>$postInfo['title'],
                'content'=>$postInfo['content'], 'type'=>$postInfo['type']]);
            if(!$addContent){
                throw new \Exception('添加文章失败,请重试!');
            }

            $res['code'] = 200;
            $res['data'] = "添加成功";

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

}
