<?php
use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;

class CenterController extends \Base\Controller_AbstractWeb
{
    public function CenterAction()
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
            $this->commonReturn(400,'用户id不能为空');
        }

        if (empty($postInfo['name'])) {
            $this->commonReturn(400,'用户名不能为空');
        }

        if (empty($postInfo['phone'])) {
            $this->commonReturn(400,'手机号不能为空!');
        }

        if (empty($postInfo['area'])) {
            $this->commonReturn(400,'地区不能为空!');
        }

        $paras['name']  = $postInfo['name'];
        $paras['phone'] = $postInfo['phone'];
        $paras['area']  = $postInfo['area'];

        $up_re = MysqlCommon::getInstance()->updateListByTableName('car_user', $paras, ['id'=>$postInfo['uid']]);
        if (!$up_re) {
            $this->commonReturn(400,'修改失败!');
        }

        $this->commonReturn(200,'修改成功!');
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