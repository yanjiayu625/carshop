<?php

namespace Base;

/**
 * 控制器抽象类
 */
abstract class Controller_Abstract extends \Yaf\Controller_Abstract {

    /**
     *
     */
    public function init() {
        \Yaf\Dispatcher::getInstance()->disableView();
    }

}
