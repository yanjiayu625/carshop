<?php

namespace Base;
/**
 * 默认CLI模块控制器抽象类
 * @package Base
 * @author zhangyang
 */
abstract class Controller_AbstractCli extends \Yaf\Controller_Abstract
{
    public function init()
    {
        if( $this->getRequest()->getMethod() != 'CLI' ){
            Tools::redirect('/index');
        }else{
            \Yaf\Dispatcher::getInstance()->disableView();
        }
    }
}
