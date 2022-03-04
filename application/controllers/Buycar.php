<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class buycarController extends \Base\Controller_AbstractWeb
{
    public function buycarAction()
    {
        $params = $this->getRequest()->getQuery();
        $arr = [
            's'=>isset($params['s'])?$params['s']:0,
            'b'=>isset($params['b'])?$params['b']:0,
            'p'=>isset($params['p'])?$params['p']:0,
        ];

//        array_shift($params);
        $lists = $this->getListByFilterAction($params);
        $brands = $this->getBrandAction();
        $this->getView()->assign(["lists" => $lists, "params" =>$arr, "brands"=>$brands]);
        $this->display("buycar");
    }

    /**
     * 获取car_brand
     */
    public function getBrandAction() {
        try {
            $brandInfo = MysqlCommon::getInstance()->getListByTableName('car_brand', ['id','brand_name','pic_dir'], ['status'=>1],  " sort_id ASC");
            return $brandInfo;
        } catch(Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * 为买车页条件查询数据
     */
    public function getListByFilterAction($params)
    {
        // 排序方式
        if (isset($params['s'])) {
            switch ($params['s']) {
                case 1:
                    $sort = " ORDER BY c.true_price ASC";
                    break;
                case 2:
                    $sort = " ORDER BY c.true_price DESC";
                    break;
                case 3:
                    $sort = " ";
                    break;
                case 4:
                    $sort = " ";
                    break;
            }
            unset($params['s']);
        } else {
            $sort = " ORDER BY c.id DESC";
        }

        if (!empty($params)) {
            $filter = " WHERE ";
        } else {
            $filter = "";
        }

        // 汽车品牌
        if (isset($params['b'])) {
            $filter .= "c.brand_id=".$params['b']." ";
        }

        // 车型
        if (isset($params['m'])) {

            if (isset($params['b'])) {
                $filter .= " AND ";
            }

            switch ($params['m']) {
                case 1:
                    $filter .= "c.basic_models='小型车' ";
                    break;
                case 2:
                    $filter .= "c.basic_models='中型车' ";
                    break;
                case 3:
                    $filter .= "c.basic_models='中大型车' ";
                    break;
                case 4:
                    $filter .= "c.basic_models='微型车' ";
                    break;
                case 5:
                    $filter .= "c.basic_models='豪华车' ";
                    break;
                case 6:
                    $filter .= "c.basic_models='紧凑车型' ";
                    break;
                case 7:
                    $filter .= "c.basic_models='MPV' ";
                    break;
                case 8:
                    $filter .= "c.basic_models='SUV' ";
                    break;
                case 9:
                    $filter .= "c.basic_models='跑车' ";
                    break;
                case 10:
                    $filter .= "c.basic_models='皮卡' ";
                    break;
                case 11:
                    $filter .= "c.basic_models='面包车' ";
                    break;
            }
        }

        // 价格
        if (isset($params['p'])) {

            if (isset($params['m']) || isset($params['b'])) {
                $filter .= " AND ";
            }

            switch ($params['p']) {
                case 1:
                    $filter .= "c.true_price<3 ";
                    break;
                case 2:
                    $filter .= "c.true_price>=3 AND c.true_price<5 ";
                    break;
                case 3:
                    $filter .= "c.true_price>=5 AND c.true_price<8 ";
                    break;
                case 4:
                    $filter .= "c.true_price>=8 AND c.true_price<10 ";
                    break;
                case 5:
                    $filter .= "c.true_price>=10 AND c.true_price<20 ";
                    break;
                case 6:
                    $filter .= "c.true_price>=20 AND c.true_price<30 ";
                    break;
                case 7:
                    $filter .= "c.true_price>=30 ";
                    break;
            }
        }

        // TODO::车龄后面补充
        if (isset($params['a'])) {

            if (isset($params['m']) || isset($params['b']) || isset($params['p'])) {
                $filter .= " AND ";
            }

            switch ($params['a']) {
                case 1:
//                    $filter .= "c.true_price<3 ";
                    break;
                case 2:
//                    $filter .= "c.true_price>=3 AND c.true_price<5 ";
                    break;
                case 3:
//                    $filter .= "c.true_price>=5 AND c.true_price<8 ";
                    break;
                case 4:
//                    $filter .= "c.true_price>=8 AND c.true_price<10 ";
                    break;
            }
        }

        // 里程
        if (isset($params['k'])) {

            if (isset($params['m']) || isset($params['b']) || isset($params['p']) || isset($params['a'])) {
                $filter .= " AND ";
            }

            switch ($params['k']) {
                case 1:
                    $filter .= "c.mileage<=1 ";
                    break;
                case 2:
                    $filter .= "c.mileage<=3 ";
                    break;
                case 3:
                    $filter .= "c.mileage<=5 ";
                    break;
                case 4:
                    $filter .= "c.mileage<=8 ";
                    break;
                case 5:
                    $filter .= "c.mileage>8 ";
                    break;
            }
        }

        // 变速箱
        if (isset($params['t'])) {

            if (isset($params['m']) || isset($params['b']) || isset($params['p']) || isset($params['a']) || isset($params['k'])) {
                $filter .= " AND ";
            }

            switch ($params['t']) {
                case 1:
                    $filter .= "c.basic_gearbox='自动' ";
                    break;
                case 2:
                    $filter .= "c.basic_gearbox='手动' ";
                    break;
                case 3:
                    $filter .= "c.basic_gearbox='手自一体' ";
                    break;
                case 4:
                    $filter .= "c.basic_gearbox='双离合' ";
                    break;
            }
        }

        // 排量
        if (isset($params['g'])) {

            if (isset($params['m']) || isset($params['b']) || isset($params['p']) || isset($params['a']) || isset($params['k']) || isset($params['t'])) {
                $filter .= " AND ";
            }

            switch ($params['g']) {
                case 1:
                    $filter .= "c.basic_emission<=1 ";
                    break;
                case 2:
                    $filter .= "c.basic_emission>=1.1 AND c.basic_emission<1.6 ";
                    break;
                case 3:
                    $filter .= "c.basic_emission>=1.6 AND c.basic_emission<2 ";
                    break;
                case 4:
                    $filter .= "c.basic_emission>=2 AND c.basic_emission<2.5 ";
                    break;
                case 5:
                    $filter .= "c.basic_emission>=2.5 AND c.basic_emission<3 ";
                    break;
                case 6:
                    $filter .= "c.basic_emission>=3 AND c.basic_emission<4 ";
                    break;
                case 7:
                    $filter .= "c.basic_emission>=4 ";
                    break;
            }
        }

        // 排放标准
        if (isset($params['e'])) {

            if (isset($params['m']) || isset($params['b']) || isset($params['p']) || isset($params['a']) || isset($params['k']) || isset($params['t']) || isset($params['g'])) {
                $filter .= " AND ";
            }

            switch ($params['e']) {
                case 1:
                    $filter .= "c.emission='国一' ";
                    break;
                case 2:
                    $filter .= "c.emission='国二' ";
                    break;
                case 3:
                    $filter .= "c.emission='国三' ";
                    break;
                case 4:
                    $filter .= "c.emission='国四' ";
                    break;
                case 5:
                    $filter .= "c.emission='国五' ";
                    break;
                case 6:
                    $filter .= "c.emission='国六' ";
                    break;
            }
        }
        $brands = MysqlCommon::getInstance()->querySQL("select c.*, b.brand_name, t.name as tags_name from `car_sell_content` c left join car_brand b on c.brand_id=b.id left join car_brand_tags t on c.tags_id=t.id $filter $sort ");

        return $brands;

    }

    // 买车列表
    public function ListAction()
    {
        // TODO::暂未添加搜索条件，待后续完善
        $postInfo = $this->getRequest()->getPost();
        if (!empty($postInfo)) {
            if ($postInfo['location'] == "index") {
                $sort = "order by id DESC";
                $count = "limit 0,10";
            } else if ($postInfo['location'] == "buycar") {
                $sort = "order by id DESC";
                $count = "";
            }
        }
        try{

//            $brands = MysqlCommon::getInstance()->getListByTableName("car_sell_content", null, null, $order = null, $group = null);
            $brands = MysqlCommon::getInstance()->querySQL("select c.*, b.brand_name, t.name as tags_name from `car_sell_content` c left join car_brand b on c.brand_id=b.id left join car_brand_tags t on c.tags_id=t.id $sort $count");



            $res['code'] = 200;
            $res['data'] = $brands;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);

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

    /**
     * 筛选页面
     */
    public function filterAction()
    {
        $params = $this->getRequest()->getQuery();

        $arr = [
            's'=>isset($params['s'])?$params['s']:0,
            'b'=>isset($params['b'])?$params['b']:0,
            'm'=>isset($params['m'])?$params['m']:0,
            'p'=>isset($params['p'])?$params['p']:0,
            'a'=>isset($params['a'])?$params['a']:0,
            'k'=>isset($params['k'])?$params['k']:0,
            't'=>isset($params['t'])?$params['t']:0,
            'g'=>isset($params['g'])?$params['g']:0,
            'e'=>isset($params['e'])?$params['e']:0
        ];

        $this->getView()->assign($arr);
        $this->display("filter");
    }

}