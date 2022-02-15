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

            if (!in_array($postInfo['file_type'], ['image', 'video'])) {
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
            'register_date', 'mileage', 'sell_price'], ['is_home' => 1, 'status' => 1], 'id desc');

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

            $existInfo = MysqlCommon::getInstance()->getInfoByTableName('car_sell_list', ['title'], ['id' => $postInfo['id']]);
            if (empty($existInfo)) {
                throw new \Exception("售卖信息ID值非法");
            }

            if (!empty(trim($postInfo['title']))) {
                $modifyInfo['title'] = addslashes(trim($postInfo['title']));
            }

            if (!empty($postInfo['pic_dir'])) {
                $modifyInfo['pic_dir'] = trim($postInfo['pic_dir']);
            }

            if (!empty($postInfo['register_date'])) {
                $registerDate = trim($postInfo['register_date']);
                if (!strtotime($registerDate)) {
                    throw new \Exception('上牌日期非法!');
                }
                $modifyInfo['register_date'] = $registerDate;
            }

            if (!empty($postInfo['mileage'])) {
                $mileage = trim($postInfo['mileage']);
                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $mileage)) {
                    throw new \Exception('里程数非法!');
                }
                $modifyInfo['mileage'] = $mileage;
            }

            if (!empty($postInfo['sell_price'])) {
                $sellPrice = trim($postInfo['sell_price']);
                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $sellPrice)) {
                    throw new \Exception('里程数非法!');
                }
                $modifyInfo['sell_price'] = $sellPrice;
            }

            if(!isset($postInfo['is_home'])){
                throw new \Exception('首页推荐参数为空!');
            }
            if(!in_array($postInfo['is_home'], ['0', '1'])){
                throw new \Exception('首页推荐参数值非法!');
            }
            $modifyInfo['is_home'] = $postInfo['is_home'];

            if(!isset($postInfo['status'])){
                throw new \Exception('售卖状态参数为空!');
            }
            if(!in_array($postInfo['status'], ['0', '1'])){
                throw new \Exception('售卖状态参数值非法!');
            }
            $modifyInfo['status'] = $postInfo['status'];

            if (empty($modifyInfo)) {
                throw new \Exception('售卖列表信息修改数据为空!');
            }

            $modifyRes = MysqlCommon::getInstance()->updateListByTableName('car_sell_list', $modifyInfo, ['id' => $postInfo['id']]);
            if (!$modifyRes) {
                throw new \Exception('Mysql操作错误:修改售卖信息时失败!');
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