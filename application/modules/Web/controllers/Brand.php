<?php

use \Tools\Tools;
use \Validate\Validate;
use \Mysql\Common\CommonModel as MysqlCommon;
use \Mongodb\Issue\CommonModel as MongoDBIssue;

/**
 * 车辆品牌
 */
class BrandController extends \Base\Controller_AbstractWeb
{
    //车品类列表
    public function brandListAction()
    {
        echo "123";
        //views 文件夹下必须有一个 brandList.phtml
    }

    /**
     * 品牌列表
     * @author zhangyang7
     * @time   2022-01-28
     * @return json
     */
    public function getBrandListAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            $query = NULL;

            if (!empty(trim($postInfo['brand_name']))) {
                $brandName = addslashes(trim($postInfo['brand_name']));
                $query['brand_name like'] = "%".$brandName."%";
            }

            if (!empty($postInfo['sort_letter'])) {
                $query['sort_letter'] = $postInfo['sort_letter'];
            }

            $brandList = MysqlCommon::getInstance()->getListByTableName('car_brand', ['id', 'brand_name', 'sort_letter',
                'sort_id', 'pic_dir', 'status'], $query, 'sort_id asc');

            $res['code'] = 200;
            $res['data'] = $brandList;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 修改品牌信息
     * @author zhangyang7
     * @time   2022-01-28
     * @return json
     */
    public function modifyBrandInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            if (empty($postInfo['id'])) {
                throw new \Exception("品牌ID为空");
            }

            $existInfo = MysqlCommon::getInstance()->getInfoByTableName('car_brand', ['brand_name'], ['id'=>$postInfo['id']]);
            if(empty($existInfo)){
                throw new \Exception("品牌ID值非法");
            }

            $modifyInfo = [];

            if (!empty(trim($postInfo['brand_name']))) {
                $brandName = addslashes(trim($postInfo['brand_name']));
                $existName = MysqlCommon::getInstance()->getInfoByTableName('car_brand', ['id'], ['id <>' => $postInfo['id'],
                    'brand_name'=>$brandName]);
                if(!empty($existName)){
                    throw new \Exception("品牌名称已存在");
                }
                $modifyInfo['brand_name']  = $brandName;
            }

            if (!empty(trim($postInfo['sort_letter']))) {
                $sortLetter =trim($postInfo['sort_letter']);
                if (!Validate::isUpperLetter($sortLetter)) {
                    throw new \Exception('品牌首字母缩写必须为大写字母!');
                }
                $modifyInfo['sort_letter']  = $sortLetter;
            }

            if (!empty(trim($postInfo['sort_id']))) {
                $sortId =trim($postInfo['sort_id']);
                if (!Validate::isNumber($sortId)) {
                    throw new \Exception('品牌排序数字必须为数字!');
                }
                $modifyInfo['sort_id']  = $sortId;
            }

            if(empty($modifyInfo)){
                throw new \Exception('品牌信息修改数据为空!');
            }

            $modifyRes = MysqlCommon::getInstance()->updateListByTableName('car_brand', $modifyInfo, ['id'=>$postInfo['id']]);
            if(!$modifyRes){
                throw new \Exception('Mysql操作错误:修改品信信息时失败!');
            }

            $res['code'] = 200;
            $res['msg'] = '修改成功';

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

}