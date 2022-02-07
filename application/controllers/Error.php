<?php

use \Session\Session;
/**
 * 当有未捕获的异常, 则控制流会流到这里
 */
class ErrorController extends \Yaf\Controller_Abstract {

    public function errorAction($exception) {
        var_dump($exception->getMessage());die;
    }

    public function forbiddenAction() {
        $usr = Session::getInstance()->getValue('usr');
        $this->getView()->assign("usr", $usr);
    }

    public function domainforbiddenAction() {
        $usr = Session::getInstance()->getValue('usr');
        $this->getView()->assign("usr", $usr);
    }

    public function browserAction() {
        $type = $this->getRequest()->getQuery("type");
        $msg = '您使用的浏览器可能无法满足系统要求,请使用一下浏览器,谢谢';
        if( $type == 'unknow' ){
            $msg = '无法判别您使用的浏览器,为确保能够正常使用系统,请使用以下浏览器:';

        }elseif( $type == 'low' ){
            $msg = '您浏览器的IE内核版本较低,请升级浏览器!';
        }
        $this->getView()->assign("msg", $msg);
    }

//    public function errorAction($exception) {
//        if ($exception->getCode() > 100000) {
//            //这里可以捕获到应用内抛出的异常
//            echo $exception->getCode();
//            echo $exception->getMessage();
//            return;
//        }
//        switch ($exception->getCode()) {
//            case 404://404
//            case 515:
//            case 516:
//            case 517:
//                //输出404
//                header(\Ifeng\Common::getHttpStatusCode(404));
//                echo '404';
//                exit();
//                break;
//            default :
//                break;
//        }
//        throw $exception;
//    }

}
