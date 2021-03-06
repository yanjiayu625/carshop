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
        if (empty($info)) {
            return false;
        }
        $bid = $info['brand_id'];
        $price = $info['true_price'];
        $price_s = $price-3;
        $price_e = $price+3;

        $sameBrand = MysqlCommon::getInstance()->querySQL("select c.*, b.brand_name, t.name as tags_name from `car_sell_content` c left join car_brand b on c.brand_id=b.id left join car_brand_tags t on c.tags_id=t.id where c.brand_id='$bid' AND c.id<>$id ORDER BY c.id DESC limit 4");
        $samePrice = MysqlCommon::getInstance()->querySQL("select c.*, b.brand_name, t.name as tags_name from `car_sell_content` c left join car_brand b on c.brand_id=b.id left join car_brand_tags t on c.tags_id=t.id where c.true_price>=$price_s AND c.true_price<=$price_e AND c.id<>$id ORDER BY c.id DESC limit 4");

        // 获取用户收藏状态 TODO::用户uid暂时用测试数据
        $uid = 14;
        $collect = MysqlCommon::getInstance()->getInfoByTableName("car_collect",['id'],["uid"=>$uid, "cid"=>$id]);
        if(empty($collect)) {
            $collect_status = "收藏";
        } else {
            $collect_status = "取消收藏";
        }
        $this->getView()->assign(['info'=>$info, 'sameBrand'=>$sameBrand, 'samePrice'=>$samePrice, 'collect_status'=>$collect_status]);
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

    /**
     * 收藏与取消收藏
     */
    public function collectAction()
    {
        $postInfo = $this->getRequest()->getQuery();
        if (!isset($postInfo['carid']) || empty($postInfo['carid'])) {
            return false;
        }
        // TODO::测试uid，上线后替换
        $uid = 14;

        $cid = $postInfo['carid'];

        try {
            $result =  MysqlCommon::getInstance()->getInfoByTableName('car_collect', ['id'],['uid'=>$uid,'cid'=>$cid]);

            if (empty($result)) {
                // 加入收藏
                $ad = MysqlCommon::getInstance()->addInfoByTableName("car_collect", ['uid'=>$uid, 'cid'=>$cid]);
                if ($ad) {
                    $res['data'] = 1;
                }

            } else {
                // 取消收藏
                MysqlCommon::getInstance()->deleteListByTableName("car_collect", ['uid'=>$uid, 'cid'=>$cid]);
                $res['data'] = 3;
            }
        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }
        Tools::returnAjaxJson($res);
    }
}
