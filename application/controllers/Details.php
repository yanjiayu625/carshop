<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class DetailsController extends \Base\Controller_AbstractWechat
{

    public function detailsAction()
    {
        $id = $this->getRequest()->getQuery("cid");
        if (empty($id) || !is_numeric($id)) {
            return false;
        }

        $info = MysqlCommon::getInstance()->querySQL("select c.*, b.brand_name, t.name as tags_name from `car_sell_content` c left join car_brand b on c.brand_id=b.id left join car_brand_tags t on c.tags_id=t.id where c.id=$id");
        $info = array_shift($info);
        $this->getView()->assign($info);
    }
    /**
     * 获取详情页面中滚动图片
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getScrollImageAction($id)
    {
        try {
            if (empty($id)) {
                throw new \Exception("内容ID为空");
            }

            $scrollImage =  MysqlCommon::getInstance()->getListByTableName('car_sell_upload_file_list', ['file_dir'],
                ['type' => 1, 'sell_id' => $id]);
//var_dump($scrollImage);die;
//            $res['code'] = 200;
//            $res['data'] = $scrollImage;

        } catch (Exception $e) {
//            $res['code'] = 400;
//            $res['msg'] = $e->getMessage();
        }

//        Tools::returnAjaxJson($res);
//            return 1;
    }

    /**
     * 获取详情页面中具体数据
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getDetailsAction($postInfo)
    {

        if (empty($postInfo)) {
            $postInfo = $this->getRequest()->getPost();
        }

        try {
            if (empty($postInfo['id'])) {
                throw new \Exception("售卖ID为空");
            }
            $detailInfo = $brandList = MysqlCommon::getInstance()->getListByTableName('car_sell_content', null,
                ['sell_id' => $postInfo['id']]);

            $res['code'] = 200;
            $res['data'] = $detailInfo;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 获取详情页面中实拍照片
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getAllImageAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            if (empty($postInfo['id'])) {
                throw new \Exception("内容ID为空");
            }

            $allImage =  MysqlCommon::getInstance()->getListByTableName('car_upload_file_list', ['file_dir'],
                ['type' => 2, 'sell_id' => $postInfo['id']]);

            $res['code'] = 200;
            $res['data'] = $allImage;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }
}
