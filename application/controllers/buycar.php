<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class BuycarController extends \Base\Controller_AbstractWeb
{
    public function BuycarAction()
    {
        $this->display("buycar");
    }

    /**
     * 卖家上传车辆信息
     */
    public function postCarInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlCommon::getInstance()->beginTransaction();

            if (empty($postInfo['brand_id'])) {
                throw new \Exception('车辆品牌信息为空!');
            }
            $brandInfo = MysqlCommon::getInstance()->getInfoByTableName('car_brand', ['brand_name'],
                ['id' => $postInfo['brand_id'], 'status' => 1]);
            if (empty($brandInfo)) {
                throw new \Exception('车辆品牌信息非法!');
            }
            $addInfo['brand_id'] = $postInfo['brand_id'];

            if (empty($postInfo['tags_id'])) {
                throw new \Exception('车系信息为空!');
            }
            $tagsInfo = MysqlCommon::getInstance()->getInfoByTableName('car_brand_tags', ['brand_name'],
                ['id' => $postInfo['tags_id'], 'brand_id' => $postInfo['brand_id'], 'status' => 1]);
            if (empty($tagsInfo)) {
                throw new \Exception('车系信息非法!');
            }
            $addInfo['tags_id'] = $postInfo['tags_id'];

            //车身图片多个用英文逗号分隔
            if (empty($postInfo['all_image_dir'])) {
                throw new \Exception('车身图片为空!');
            }
            $allImageArr = explode(',', $postInfo['all_image_dir']);

            //上牌日期
            if (empty($postInfo['register_date'])) {
                throw new \Exception('购买日期为空!');
            }
            $registerDate = trim($postInfo['register_date']);
            if (!strtotime($registerDate)) {
                throw new \Exception('购买日期非法!');
            }
            $addInfo['register_date'] = $registerDate;

            //里程数
            if (empty($postInfo['mileage'])) {
                throw new \Exception('里程数为空!');
            }
            $mileage = trim($postInfo['mileage']);
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $mileage)) {
                throw new \Exception('里程数非法!');
            }
            $addInfo['mileage'] = $mileage;

            if (empty(trim($postInfo['color']))) {
                throw new \Exception('颜色信息为空!');
            }
            $addInfo['color'] = addslashes(trim($postInfo['color']));

            if (empty(trim($postInfo['owner_name']))) {
                throw new \Exception('卖家名称为空!');
            }
            $addInfo['owner_name'] = addslashes(trim($postInfo['owner_name']));

            if (empty(trim($postInfo['owner_mobile']))) {
                throw new \Exception('卖家手机号为空!');
            }
            //网上找的
            if (!preg_match("/^1((34[0-8]\d{7})|((3[0-3|5-9])|(4[5-7|9])|(5[0-3|5-9])|(66)|(7[2-3|5-8])|(8[0-9])|(9[1|8|9]))\d{8})$/", trim($postInfo['owner_mobile']))) {
                throw new \Exception('变速箱信息为空!');
            }
            $addInfo['owner_mobile'] = addslashes(trim($postInfo['owner_mobile']));

            //车主描述
            if (empty(trim($postInfo['owner_desc']))) {
                throw new \Exception('车主描述信息为空!');
            }
            $addInfo['owner_desc'] = addslashes(trim($postInfo['owner_desc']));

            $addInfo['status'] = 1;

            $addRes = MysqlCommon::getInstance()->addInfoByTableName('car_buy_car_list', $addInfo);
            if (!$addRes) {
                throw new \Exception('Mysql操作错误:添加详细信息时失败!');
            }
            
            $imageDataArr = [];
            foreach ($allImageArr as $imageDir) {
                $imageDataArr[] = ['buy_id'=>$addRes, 'type' => 1, 'file_dir' => $imageDir];
            }
            $addImageRes = MysqlCommon::getInstance()->addMultInfoByTableName('car_buy_upload_file_list', $imageDataArr);
            if (!$addImageRes) {
                throw new \Exception('Mysql操作错误:添加详细信息图片时失败!');
            }

            $res['code'] = 200;
            $res['msg'] = '添加成功';

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }
}