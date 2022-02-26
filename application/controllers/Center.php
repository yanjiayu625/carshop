<?php

class CenterController extends \Base\Controller_AbstractWeb
{
    public function CenterAction()
    {
        $this->display("center");
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