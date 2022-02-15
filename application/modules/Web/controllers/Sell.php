<?php

use \Tools\Tools;
use \Validate\Validate;
use \DAO\Data\FileModel;
use \Mysql\Common\CommonModel as MysqlCommon;
use \Mongodb\Issue\CommonModel as MongoDBIssue;

/**
 * 卖车模块
 */
class SellController extends \Base\Controller_AbstractWeb
{

    /**
     * 图片上传 附带压缩
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/6/8 9:58
     */
    /**
     * 上传附件
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function uploadFileAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            $type = $this->getRequest()->getQuery('type');

            if (empty($postInfo['file_type'])) {
                throw new \Exception("上传文件类型不能为空");
            }

            if(!in_array($postInfo['file_type'], ['image', 'video'])){
                throw new \Exception("上传文件类型不能非法");
            }

            if (empty($_FILES['file'])) {
                throw new \Exception("上传文件为空");
            }

            $fileInfo = $_FILES['file'];

            if (!is_uploaded_file($fileInfo["tmp_name"])) {
                throw new \Exception("上传文件不存在");
            }

            if ($postInfo['file_type'] == 'image' && $fileInfo["size"] > 1024000) {
                throw new \Exception("图片文件太大,不能超过10M");
            }
            if ($postInfo['file_type'] == 'video' && $fileInfo["size"] > 2048000) {
                throw new \Exception("视频文件太大,不能超过10M");
            }

            $fileDir = '/upload/' . $type . '/' . date("Ymd");

            if (!file_exists(STORAGE_DIR . $fileDir)) {
                mkdir(STORAGE_DIR . $fileDir, 0777, true);
            }

            $fileDir .= '/' . $fileInfo['name'];

            $fileName = STORAGE_DIR . $fileDir;

            if (!move_uploaded_file($fileInfo["tmp_name"], $fileName)) {
                throw new \Exception('上传文件失败');
            }

            $res['code'] = 200;
            $res['data'] = $fileDir;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }


    /**
     * 售卖列表
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getSellListAction()
    {
        $list = $brandList = MysqlCommon::getInstance()->getListByTableName('car_sell_list', ['id', 'title', 'pic_dir',
            'register_date', 'mileage', 'sell_price'], ['is_home'=>1,'status'=>1], 'id desc');

        $res['code'] = 200;
        $res['data'] = $list;

        Tools::returnAjaxJson($res);
    }

    /**
     * 修改售卖信息
     * @author zhangyang7
     * @time   2022-01-28
     * @return json
     */
    public function modifySellInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            if (empty($postInfo['id'])) {
                throw new \Exception("售卖ID为空");
            }

            $existInfo = MysqlCommon::getInstance()->getInfoByTableName('car_sell_list', ['brand_name'], ['id'=>$postInfo['id']]);
            if(empty($existInfo)){
                throw new \Exception("售卖信息ID值非法");
            }

            if (!empty(trim($postInfo['brand_name']))) {
                $brandName = addslashes(trim($postInfo['brand_name']));
                $existName = MysqlCommon::getInstance()->getInfoByTableName('car_brand', ['id'], ['id <>' => $postInfo['id'],
                    'brand_name'=>$brandName]);
                if(!empty($existName)){
                    throw new \Exception("品牌名称已存在");
                }
                $modifyInfo['brand_name']  = $brandName;
            }

            if (!empty($postInfo['sort_letter'])) {
                $sortLetter =trim($postInfo['sort_letter']);
                if (!Validate::isUpperLetter($sortLetter)) {
                    throw new \Exception('品牌首字母缩写必须为大写字母!');
                }
                $modifyInfo['sort_letter']  = $sortLetter;
            }

            if (!empty($postInfo['sort_id'])) {
                $sortId =trim($postInfo['sort_id']);
                if (!Validate::isNumber($sortId)) {
                    throw new \Exception('品牌排序数字必须为数字!');
                }
                $modifyInfo['sort_id']  = $sortId;
            }

            if (!empty($postInfo['pic_dir'])) {
                $modifyInfo['pic_dir'] = trim($postInfo['pic_dir']);
            }

            if(empty($postInfo['status'])){
                throw new \Exception('品牌状态参数为空!');
            }
            if(!in_array($postInfo['status'], ['1', '2'])){
                throw new \Exception('品牌状态参数值非法!');
            }
            $modifyInfo['status'] = $postInfo['status'];

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