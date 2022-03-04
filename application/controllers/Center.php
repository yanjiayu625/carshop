<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class CenterController extends \Base\Controller_AbstractWeb
{
    public function centerAction()
    {
        $this->display("center");
    }

    //用户中心 - 完善资料
    public function editUserAction()
    {

        $postInfo = $this->getRequest()->getPost();

        //默认整一个
        $postInfo['uid'] = 1;

        if (empty($postInfo['uid'])) {
            $this->commonReturn(400, '用户id不能为空');
        }

        if (empty($postInfo['name'])) {
            $this->commonReturn(400, '用户名不能为空');
        }

        if (empty($postInfo['phone'])) {
            $this->commonReturn(400, '手机号不能为空!');
        }

        if (empty($postInfo['area'])) {
            $this->commonReturn(400, '地区不能为空!');
        }

        $paras['name'] = $postInfo['name'];
        $paras['phone'] = $postInfo['phone'];
        $paras['area'] = $postInfo['area'];

        $up_re = MysqlCommon::getInstance()->updateListByTableName('car_user', $paras, ['id' => $postInfo['uid']]);
        if (!$up_re) {
            $this->commonReturn(400, '修改失败!');
        }

        $this->commonReturn(200, '修改成功!');
    }

    //用户中心 - 修改密码
    public function editUserPassAction()
    {

        $postInfo = $this->getRequest()->getPost();

        //默认整一个
        $postInfo['uid'] = 1;

        if (empty($postInfo['uid'])) {
            $this->commonReturn(400, '用户id不能为空');
        }

        if (empty($postInfo['old_pass'])) {
            $this->commonReturn(400, '请输入原密码');
        }

        if (empty($postInfo['new_pass'])) {
            $this->commonReturn(400, '请输入新密码');
        }

        if ($postInfo['new_pass'] != $postInfo['new_pass_repeat']) {
            $this->commonReturn(400, '两次输入密码不一致!');
        }
        $pass = $this->encryptionPass($postInfo['old_pass']);
        $userInfo = MysqlCommon::getInstance()->getInfoByTableName('car_user', ['password'], ['id' => $postInfo['uid']]);

        if ($pass != $userInfo['password']) {
            $this->commonReturn(400, '原密码错误!');
        }

        $paras['password'] = $this->encryptionPass($postInfo['new_pass']);
        $up_re = MysqlCommon::getInstance()->updateListByTableName('car_user', $paras, ['id' => $postInfo['uid']]);
        if (!$up_re) {
            $this->commonReturn(400, '密码修改失败!');
        }

        $this->commonReturn(200, '密码修改成功!');
    }

    //获取地区 - 省 列表
    public function getProvinceListAction()
    {

        $fields = ['code', 'parentCode', 'name', 'fullName', 'initial'];
        $areaInfo = MysqlCommon::getInstance()->getListLimitByTableName('ly_region', $fields, ['parentCode' => '100000']);
        $this->commonReturn(200, '获取成功!', $areaInfo);
    }

    //获取地区 - 市 列表
    public function getCityListAction()
    {
        $postInfo = $this->getRequest()->getPost();
        if (empty($postInfo['code'])) {
            $this->commonReturn(400, '省份code不能为空');
        }
        $fields = ['code', 'parentCode', 'name', 'fullName', 'initial'];
        $areaInfo = MysqlCommon::getInstance()->getListLimitByTableName('ly_region', $fields, ['parentCode' => $postInfo['code']]);
        $this->commonReturn(200, '获取成功!', $areaInfo);
    }

    public function wxHeaderAction(){
        $appId = 'wx1647b3429377748f';
        $redirect_uri = urlencode("http://121.196.217.164/center/wx");
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appId . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
        Header("HTTP/1.1 303 See Other");
        Header("Location: $url");
        exit;
    }

    public function wxLoginAction()
    {

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET["echostr"];

        $token = '12345';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            echo $echostr;
        } else {
            return false;
        }

    }

    public function wxAction()
    {
        $appId = 'wx1647b3429377748f';
        $secret = '7c837f2ff2887845c13558742232a43d';
        $code = $_GET["code"];

        if ($code) {
            $get_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appId . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
            $get_token = $this->curl_request($get_token_url);
            if(isset($get_token['errcode'])){
                $this->commonReturn(400, '微信code已失效!');
            }

            $get_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $get_token['access_token'] . "&openid=" . $get_token['openid'] . "&lang=zh_CN";
            $get_info = $this->curl_request($get_info_url);
            if(isset($get_token['errcode'])){
                $this->commonReturn(400, '微信code已失效!');
            }

            $userInfo = MysqlCommon::getInstance()->getInfoByTableName('car_user', ['id'], ['wx_open_id' => $get_info['openid']]);
            if (isset($userInfo['id'])) {
                $this->commonReturn(200, '微信登陆成功!');
            }

            $add_params["name"] = $get_info['nickname'];
            $add_params["wx_name"] = $get_info['nickname'];
            $add_params["wx_img"] = $get_info['headimgurl'];
            $add_params["wx_sex"] = $get_info['sex'];
            $add_params["wx_open_id"] = $get_info['openid'];
            $add_params["type"] = 'wx';
            $add_re = MysqlCommon::getInstance()->addInfoByTableName('car_user', $add_params);
            if ($add_re) {
                $this->commonReturn(200, '微信登陆成功!');
            }
            $this->commonReturn(400, '微信登陆失败!');
        }
    }


    /**
     * 预约申请
     */
    public function OrderAction()
    {
        $this->display("order");
    }


    /**
     * 砍价申请
     */
    public function BargainAction()
    {
        $this->display("bargain");
    }

    /**
     * 基本资料
     */
    public function DataAction()
    {
        $this->display("data");
    }

    /**
     * 身份认证
     */
    public function ApproveAction()
    {
        $this->display("approve");
    }

    /**
     *
     */
    public function UpPwdAction()
    {
        $this->display("uppwd");
    }


}