<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;
use \DAO\Conf\ConfModel as Conf;

/**
 * 内容频道
 */
class ContentController extends \Base\Controller_AbstractWeb
{
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
     * 获取频道列表
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getChannelListAction()
    {

        $res['code'] = 200;
        $res['data'] = Conf::$channelArr;

        Tools::returnAjaxJson($res);
    }

    /**
     * 获取列表
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getContentListAction()
    {
        $sellList = $brandList = MysqlCommon::getInstance()->getListByTableName('car_sell_list', ['id', 'title', 'pic_dir',
            'register_date', 'mileage', 'sell_price'], ['is_home' => 1, 'status' => 1], 'id desc');

        $res['code'] = 200;
        $res['data'] = $sellList;

        Tools::returnAjaxJson($res);
    }

    /**
     * 添加列表信息
     * @author zhangyang7
     * @time   2022-01-28
     * @return json
     */
    public function addSellInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
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

            if (empty(trim($postInfo['title']))) {
                throw new \Exception('列表标题信息为空!');
            }
            $addInfo['title'] = addslashes(trim($postInfo['title']));

            if (empty($postInfo['pic_dir'])) {
                throw new \Exception('列表缩略图片为空!');
            }
            $addInfo['pic_dir'] = trim($postInfo['pic_dir']);

            if (empty($postInfo['register_date'])) {
                throw new \Exception('上牌日期为空!');
            }
            $registerDate = trim($postInfo['register_date']);
            if (!strtotime($registerDate)) {
                throw new \Exception('上牌日期非法!');
            }
            $addInfo['register_date'] = $registerDate;

            if (empty($postInfo['mileage'])) {
                throw new \Exception('里程数为空!');
            }
            $mileage = trim($postInfo['mileage']);
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $mileage)) {
                throw new \Exception('里程数非法!');
            }
            $addInfo['mileage'] = $mileage;

            if (empty($postInfo['sell_price'])) {
                throw new \Exception('售价非法!');
            }
            $sellPrice = trim($postInfo['sell_price']);
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $sellPrice)) {
                throw new \Exception('售价值非法!');
            }
            $addInfo['sell_price'] = $sellPrice;

            if (!isset($postInfo['is_home'])) {
                throw new \Exception('首页推荐参数为空!');
            }
            if (!in_array($postInfo['is_home'], ['0', '1'])) {
                throw new \Exception('首页推荐参数值非法!');
            }
            $addInfo['is_home'] = $postInfo['is_home'];

            $addInfo['status'] = 0;
            $addRes = MysqlCommon::getInstance()->addInfoByTableName('car_sell_list', $addInfo);
            if (!$addRes) {
                throw new \Exception('Mysql操作错误:添加列表信息时失败!');
            }

            $res['code'] = 200;
            $res['msg'] = '添加成功';

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 修改列表信息
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

            if (!isset($postInfo['is_home'])) {
                throw new \Exception('首页推荐参数为空!');
            }
            if (!in_array($postInfo['is_home'], ['0', '1'])) {
                throw new \Exception('首页推荐参数值非法!');
            }
            $modifyInfo['is_home'] = $postInfo['is_home'];

            if (!isset($postInfo['status'])) {
                throw new \Exception('售卖状态参数为空!');
            }
            if (!in_array($postInfo['status'], ['0', '1'])) {
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


    /**
     * 获取详情页面中具体数据
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getDetailsAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            if (empty($postInfo['id'])) {
                throw new \Exception("售卖ID为空");
            }
            $detailInfo = $brandList = MysqlCommon::getInstance()->getListByTableName('car_sell_content', null,
                ['id' => $postInfo['id']]);

            $res['code'] = 200;
            $res['data'] = $detailInfo;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }


    /**
     * 添加详情信息
     * @author zhangyang7
     * @time   2022-01-28
     * @return json
     */
    public function addDetailsAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlCommon::getInstance()->beginTransaction();

            if (empty($postInfo['sell_id'])) {
                throw new \Exception('信息列表ID为空!');
            }
            $sellInfo = MysqlCommon::getInstance()->getInfoByTableName('car_sell_list', ['title'],
                ['id' => $postInfo['sell_id']]);
            if (empty($sellInfo)) {
                throw new \Exception('信息列表ID非法!');
            }
            $addInfo['sell_id'] = $postInfo['sell_id'];

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

            //标题
            if (empty(trim($postInfo['title']))) {
                throw new \Exception('列表标题信息为空!');
            }
            $addInfo['title'] = addslashes(trim($postInfo['title']));

            //来源
            if (empty(trim($postInfo['source']))) {
                throw new \Exception('车辆来源信息为空!');
            }
            $addInfo['source'] = addslashes(trim($postInfo['source']));

            //滚动图片多个用英文逗号分隔
            if (empty($postInfo['scroll_image_dir'])) {
                throw new \Exception('滚动图片为空!');
            }
            $scrollImageArr = explode(',', $postInfo['scroll_image_dir']);

            //全景图片多个用英文逗号分隔
            if (empty($postInfo['all_image_dir'])) {
                throw new \Exception('滚动图片为空!');
            }
            $allImageArr = explode(',', $postInfo['all_image_dir']);


            //数据更新日期
            if (empty($postInfo['update_date'])) {
                throw new \Exception('信息更新日期为空!');
            }
            $updateDate = trim($postInfo['update_date']);
            if (!strtotime($updateDate)) {
                throw new \Exception('信息更新日期非法!');
            }
            $addInfo['update_date'] = $updateDate;

            //上牌日期
            if (empty($postInfo['register_date'])) {
                throw new \Exception('上牌日期为空!');
            }
            $registerDate = trim($postInfo['register_date']);
            if (!strtotime($registerDate)) {
                throw new \Exception('上牌日期非法!');
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

            //新车价格
            if (empty($postInfo['new_price'])) {
                throw new \Exception('售价非法!');
            }
            $newPrice = trim($postInfo['new_price']);
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $newPrice)) {
                throw new \Exception('售价值非法!');
            }
            $addInfo['new_price'] = $newPrice;

            //排量
            if (empty(trim($postInfo['car_engines']))) {
                throw new \Exception('排量信息为空!');
            }
            $addInfo['car_engines'] = addslashes(trim($postInfo['car_engines']));

            //排放标准
            if (empty(trim($postInfo['emission']))) {
                throw new \Exception('排量信息为空!');
            }
            $addInfo['emission'] = addslashes(trim($postInfo['emission']));

            //详细信息校验
            if (empty(trim($postInfo['basic_belong']))) {
                throw new \Exception('归属地信息为空!');
            }
            $addInfo['basic_belong'] = addslashes(trim($postInfo['basic_belong']));

            if (empty(trim($postInfo['basic_models']))) {
                throw new \Exception('车型信息为空!');
            }
            $addInfo['basic_models'] = addslashes(trim($postInfo['basic_models']));

            if (empty(trim($postInfo['basic_color']))) {
                throw new \Exception('颜色信息为空!');
            }
            $addInfo['basic_color'] = addslashes(trim($postInfo['basic_color']));

            if (empty(trim($postInfo['basic_emission']))) {
                throw new \Exception('排量信息为空!');
            }
            $addInfo['basic_emission'] = addslashes(trim($postInfo['basic_emission']));

            if (empty(trim($postInfo['basic_gearbox']))) {
                throw new \Exception('变速箱信息为空!');
            }
            $addInfo['basic_gearbox'] = addslashes(trim($postInfo['basic_gearbox']));

            if (empty(trim($postInfo['basic_import']))) {
                throw new \Exception('国产/进口信息为空!');
            }
            $addInfo['basic_import'] = addslashes(trim($postInfo['basic_import']));

            if (empty(trim($postInfo['basic_register']))) {
                throw new \Exception('上牌日期信息为空!');
            }
            $addInfo['basic_register'] = addslashes(trim($postInfo['basic_register']));

            if (empty(trim($postInfo['basic_fuel']))) {
                throw new \Exception('燃油类型信息为空!');
            }
            $addInfo['basic_fuel'] = addslashes(trim($postInfo['basic_fuel']));

            if (empty(trim($postInfo['basic_emission_rule']))) {
                throw new \Exception('排放标准信息为空!');
            }
            $addInfo['basic_emission_rule'] = addslashes(trim($postInfo['basic_emission_rule']));

            if (empty(trim($postInfo['basic_survey']))) {
                throw new \Exception('年检到期信息为空!');
            }
            $addInfo['basic_survey'] = addslashes(trim($postInfo['basic_survey']));

            if (empty(trim($postInfo['basic_insurance']))) {
                throw new \Exception('保险到期信息为空!');
            }
            $addInfo['basic_insurance'] = addslashes(trim($postInfo['basic_insurance']));

            if (empty(trim($postInfo['basic_purpose']))) {
                throw new \Exception('车辆用途信息为空!');
            }
            $addInfo['basic_purpose'] = addslashes(trim($postInfo['basic_purpose']));

            if (empty(trim($postInfo['basic_maintenance']))) {
                throw new \Exception('定期保养信息为空!');
            }
            $addInfo['basic_maintenance'] = addslashes(trim($postInfo['basic_maintenance']));

            //车主描述
            if (empty(trim($postInfo['owner_desc']))) {
                throw new \Exception('车主描述信息为空!');
            }
            $addInfo['owner_desc'] = addslashes(trim($postInfo['owner_desc']));

            //状态信息  若为0,则为禁用状态,不用更新sell_list表中的状态
            if (!isset($postInfo['status'])) {
                throw new \Exception('状态参数为空!');
            }
            if (!in_array($postInfo['status'], ['0', '1'])) {
                throw new \Exception('状态参数值非法!');
            }
            $addInfo['status'] = $postInfo['status'];

            $addRes = MysqlCommon::getInstance()->addInfoByTableName('car_sell_content', $addInfo);
            if (!$addRes) {
                throw new \Exception('Mysql操作错误:添加详细信息时失败!');
            }

            $updateSellRes = MysqlCommon::getInstance()->updateListByTableName('car_sell_content', ['status' => $addInfo['status']],
                ['id' => $postInfo['sell_id']]);
            if (!$updateSellRes) {
                throw new \Exception('Mysql操作错误:修改列表状态时信息时失败!');
            }

            $imageDataArr = [];
            foreach ($scrollImageArr as $imageDir) {
                $imageDataArr[] = ['sell_id' => $postInfo['sell_id'], 'type' => 1, 'file_dir' => $imageDir];
            }
            foreach ($allImageArr as $imageDir) {
                $imageDataArr[] = ['sell_id' => $postInfo['sell_id'], 'type' => 2, 'file_dir' => $imageDir];
            }
            $addImageRes = MysqlCommon::getInstance()->addMultInfoByTableName('car_sell_upload_file_list', $imageDataArr);
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


    /**
     * 添加详情信息
     * @author zhangyang7
     * @time   2022-01-28
     * @return json
     */
    public function modifyDetailsAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlCommon::getInstance()->beginTransaction();

            if (empty($postInfo['sell_id'])) {
                throw new \Exception('信息列表ID为空!');
            }
            $contentInfo = MysqlCommon::getInstance()->getInfoByTableName('car_sell_content', ['id'],
                ['sell_id' => $postInfo['sell_id']]);
            if (empty($contentInfo)) {
                throw new \Exception('信息列表ID非法!');
            }

            if (empty($postInfo['brand_id'])) {
                throw new \Exception('车辆品牌信息为空!');
            }
            $brandInfo = MysqlCommon::getInstance()->getInfoByTableName('car_brand', ['brand_name'],
                ['id' => $postInfo['brand_id'], 'status' => 1]);
            if (empty($brandInfo)) {
                throw new \Exception('车辆品牌信息非法!');
            }
            $modifyInfo['brand_id'] = $postInfo['brand_id'];

            if (empty($postInfo['tags_id'])) {
                throw new \Exception('车系信息为空!');
            }
            $tagsInfo = MysqlCommon::getInstance()->getInfoByTableName('car_brand_tags', ['brand_name'],
                ['id' => $postInfo['tags_id'], 'brand_id' => $postInfo['brand_id'], 'status' => 1]);
            if (empty($tagsInfo)) {
                throw new \Exception('车系信息非法!');
            }
            $modifyInfo['tags_id'] = $postInfo['tags_id'];

            //标题
            if (empty(trim($postInfo['title']))) {
                throw new \Exception('列表标题信息为空!');
            }
            $modifyInfo['title'] = addslashes(trim($postInfo['title']));

            //来源
            if (empty(trim($postInfo['source']))) {
                throw new \Exception('车辆来源信息为空!');
            }
            $modifyInfo['source'] = addslashes(trim($postInfo['source']));

            //滚动图片多个用英文逗号分隔
            if (empty($postInfo['scroll_image_dir'])) {
                throw new \Exception('滚动图片为空!');
            }
            $scrollImageArr = explode(',', $postInfo['scroll_image_dir']);

            //全景图片多个用英文逗号分隔
            if (empty($postInfo['all_image_dir'])) {
                throw new \Exception('滚动图片为空!');
            }
            $allImageArr = explode(',', $postInfo['all_image_dir']);

            //数据更新日期
            if (empty($postInfo['update_date'])) {
                throw new \Exception('信息更新日期为空!');
            }
            $updateDate = trim($postInfo['update_date']);
            if (!strtotime($updateDate)) {
                throw new \Exception('信息更新日期非法!');
            }
            $modifyInfo['update_date'] = $updateDate;

            //上牌日期
            if (empty($postInfo['register_date'])) {
                throw new \Exception('上牌日期为空!');
            }
            $registerDate = trim($postInfo['register_date']);
            if (!strtotime($registerDate)) {
                throw new \Exception('上牌日期非法!');
            }
            $modifyInfo['register_date'] = $registerDate;

            //里程数
            if (empty($postInfo['mileage'])) {
                throw new \Exception('里程数为空!');
            }
            $mileage = trim($postInfo['mileage']);
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $mileage)) {
                throw new \Exception('里程数非法!');
            }
            $modifyInfo['mileage'] = $mileage;

            //新车价格
            if (empty($postInfo['new_price'])) {
                throw new \Exception('售价非法!');
            }
            $newPrice = trim($postInfo['new_price']);
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $newPrice)) {
                throw new \Exception('售价值非法!');
            }
            $modifyInfo['new_price'] = $newPrice;

            //排量
            if (empty(trim($postInfo['car_engines']))) {
                throw new \Exception('排量信息为空!');
            }
            $modifyInfo['car_engines'] = addslashes(trim($postInfo['car_engines']));

            //排放标准
            if (empty(trim($postInfo['emission']))) {
                throw new \Exception('排量信息为空!');
            }
            $modifyInfo['emission'] = addslashes(trim($postInfo['emission']));

            //详细信息校验
            if (empty(trim($postInfo['basic_belong']))) {
                throw new \Exception('归属地信息为空!');
            }
            $modifyInfo['basic_belong'] = addslashes(trim($postInfo['basic_belong']));

            if (empty(trim($postInfo['basic_models']))) {
                throw new \Exception('车型信息为空!');
            }
            $modifyInfo['basic_models'] = addslashes(trim($postInfo['basic_models']));

            if (empty(trim($postInfo['basic_color']))) {
                throw new \Exception('颜色信息为空!');
            }
            $modifyInfo['basic_color'] = addslashes(trim($postInfo['basic_color']));

            if (empty(trim($postInfo['basic_emission']))) {
                throw new \Exception('排量信息为空!');
            }
            $modifyInfo['basic_emission'] = addslashes(trim($postInfo['basic_emission']));

            if (empty(trim($postInfo['basic_gearbox']))) {
                throw new \Exception('变速箱信息为空!');
            }
            $modifyInfo['basic_gearbox'] = addslashes(trim($postInfo['basic_gearbox']));

            if (empty(trim($postInfo['basic_import']))) {
                throw new \Exception('国产/进口信息为空!');
            }
            $modifyInfo['basic_import'] = addslashes(trim($postInfo['basic_import']));

            if (empty(trim($postInfo['basic_register']))) {
                throw new \Exception('上牌日期信息为空!');
            }
            $modifyInfo['basic_register'] = addslashes(trim($postInfo['basic_register']));

            if (empty(trim($postInfo['basic_fuel']))) {
                throw new \Exception('燃油类型信息为空!');
            }
            $modifyInfo['basic_fuel'] = addslashes(trim($postInfo['basic_fuel']));

            if (empty(trim($postInfo['basic_emission_rule']))) {
                throw new \Exception('排放标准信息为空!');
            }
            $modifyInfo['basic_emission_rule'] = addslashes(trim($postInfo['basic_emission_rule']));

            if (empty(trim($postInfo['basic_survey']))) {
                throw new \Exception('年检到期信息为空!');
            }
            $modifyInfo['basic_survey'] = addslashes(trim($postInfo['basic_survey']));

            if (empty(trim($postInfo['basic_insurance']))) {
                throw new \Exception('保险到期信息为空!');
            }
            $modifyInfo['basic_insurance'] = addslashes(trim($postInfo['basic_insurance']));

            if (empty(trim($postInfo['basic_purpose']))) {
                throw new \Exception('车辆用途信息为空!');
            }
            $modifyInfo['basic_purpose'] = addslashes(trim($postInfo['basic_purpose']));

            if (empty(trim($postInfo['basic_maintenance']))) {
                throw new \Exception('定期保养信息为空!');
            }
            $modifyInfo['basic_maintenance'] = addslashes(trim($postInfo['basic_maintenance']));

            //车主描述
            if (empty(trim($postInfo['owner_desc']))) {
                throw new \Exception('车主描述信息为空!');
            }
            $modifyInfo['owner_desc'] = addslashes(trim($postInfo['owner_desc']));

            //状态信息  若为0,则为禁用状态,不用更新sell_list表中的状态
            if (!isset($postInfo['status'])) {
                throw new \Exception('状态参数为空!');
            }
            if (!in_array($postInfo['status'], ['0', '1'])) {
                throw new \Exception('状态参数值非法!');
            }
            $modifyInfo['status'] = $postInfo['status'];

            $updateContentRes = MysqlCommon::getInstance()->updateListByTableName('car_sell_content', $modifyInfo,
                ['sell_id' => $postInfo['sell_id']]);
            if (!$updateContentRes) {
                throw new \Exception('Mysql操作错误:添加详细信息时失败!');
            }

            $updateSellRes = MysqlCommon::getInstance()->updateListByTableName('car_sell_content', ['status' => $postInfo['status']],
                ['id' => $postInfo['sell_id']]);
            if (!$updateSellRes) {
                throw new \Exception('Mysql操作错误:修改列表状态时信息时失败!');
            }

            $delFileRes = MysqlCommon::getInstance()->deleteListByTableName('car_upload_file_list', ['sell_id'=>$contentInfo['id']]);
            if (!$delFileRes) {
                throw new \Exception('Mysql操作错误:删除详细信息中上传文件时失败!');
            }
            $imageDataArr = [];
            foreach ($scrollImageArr as $imageDir) {
                $imageDataArr[] = ['sell_id' => $contentInfo['id'], 'type' => 1, 'file_dir' => $imageDir];
            }
            foreach ($allImageArr as $imageDir) {
                $imageDataArr[] = ['sell_id' => $contentInfo['id'], 'type' => 2, 'file_dir' => $imageDir];
            }

            $addImageRes = MysqlCommon::getInstance()->addMultInfoByTableName('car_upload_file_list', $imageDataArr);
            if (!$addImageRes) {
                throw new \Exception('Mysql操作错误:添加详细信息上传文件时失败!');
            }

            MysqlCommon::getInstance()->commitTransaction();

            $res['code'] = 200;
            $res['msg'] = '修改成功';

        } catch (Exception $e) {
            MysqlCommon::getInstance()->rollbackTransaction();

            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }


}