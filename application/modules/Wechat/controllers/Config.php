<?php

use \DAO\IdcOs\ConfigModel as DAOIdcOsConfig;
use \Mysql\IdcOs\ServerbrandModel as MysqlIdcOsServer;
/**
 * 后台管理
 */
class ConfigController extends \Base\Controller_AbstractIdcosIndex {

    //网段管理
    public function networkAction()
    {

    }

    //添加网段
    public function newNetworkAction()
    {

    }

    //修改网段
    public function updateNetworkAction()
    {

    }

    //克隆网段
    public function cloneNetworkAction()
    {

    }

    //操作系统管理
    public function osAction()
    {

    }

    //添加操作系统
    public function newOsAction()
    {

    }

    //修改操作系统
    public function updateOsAction()
    {

    }

    //克隆操作系统
    public function cloneOsAction()
    {

    }

    //查看操作系统
    public function detailOsAction()
    {

    }

    //系统模板管理
    public function systemAction()
    {

    }

    //添加系统模板系统
    public function newsystemAction()
    {

    }

    //硬件配置管理
    public function hardwareAction()
    {

    }

    //添加硬件配置
    public function newhardwareAction()
    {

    }

    //编辑硬件配置
    public function updatehardwareAction()
    {
        $id = $this->getRequest()->getQuery('id');

        $hardwareList = DAOIdcOsConfig::getInstance()->getHardware();
        $res = new stdClass();
        foreach( $hardwareList as $hardware ){
            if( $hardware['id'] == $id ){
                $res = $hardware;
                break;
            }
        }

        $name   = $res['name'];
        $brand  = $res['brand'];
        $line   = $res['line'];
        $model  = $res['model'];

        $serverBrandList = MysqlIdcOsServer::getInstance()->getAllBrand();

        $brandList  = array();
        $lineList   = array();
        $modelList  = array();
        foreach($serverBrandList as $serverBrand){
            if( !in_array($serverBrand['brand'],$brandList) ){
                $brandList[] = $serverBrand['brand'];
            }
            if( $serverBrand['brand'] == $brand && !in_array($serverBrand['line'],$lineList) ){
                $lineList[] = $serverBrand['line'];
            }
            if( $serverBrand['brand'] == $brand && $serverBrand['line'] == $line && !in_array($serverBrand['model'],$modelList) ){
                $modelList[] = $serverBrand['model'];
            }
        }

        $this->getView()->assign("name", $name);
        $this->getView()->assign("brand", $brand);
        $this->getView()->assign("line", $line);
        $this->getView()->assign("model", $model);
        $this->getView()->assign("brandlist", $brandList);
        $this->getView()->assign("linelist", $lineList);
        $this->getView()->assign("modellist", $modelList);
    }

    //克隆硬件配置
    public function clonehardwareAction()
    {
        $id = $this->getRequest()->getQuery('id');

        $hardwareList = DAOIdcOsConfig::getInstance()->getHardware();
        $res = new stdClass();
        foreach( $hardwareList as $hardware ){
            if( $hardware['id'] == $id ){
                $res = $hardware;
                break;
            }
        }

        $name   = $res['name'];
        $brand  = $res['brand'];
        $line   = $res['line'];
        $model  = $res['model'];

        $serverBrandList = MysqlIdcOsServer::getInstance()->getAllBrand();

        $brandList  = array();
        $lineList   = array();
        $modelList  = array();
        foreach($serverBrandList as $serverBrand){
            if( !in_array($serverBrand['brand'],$brandList) ){
                $brandList[] = $serverBrand['brand'];
            }
            if( $serverBrand['brand'] == $brand && !in_array($serverBrand['line'],$lineList) ){
                $lineList[] = $serverBrand['line'];
            }
            if( $serverBrand['brand'] == $brand && $serverBrand['line'] == $line && !in_array($serverBrand['model'],$modelList) ){
                $modelList[] = $serverBrand['model'];
            }
        }
        
        $this->getView()->assign("name", $name);
        $this->getView()->assign("brand", $brand);
        $this->getView()->assign("line", $line);
        $this->getView()->assign("model", $model);
        $this->getView()->assign("brandlist", $brandList);
        $this->getView()->assign("linelist", $lineList);
        $this->getView()->assign("modellist", $modelList);
    }

    //克隆硬件配置
    public function detailhardwareAction()
    {
        $id = $this->getRequest()->getQuery('id');

        $hardwareList = DAOIdcOsConfig::getInstance()->getHardware();
        $res = new stdClass();
        foreach( $hardwareList as $hardware ){
            if( $hardware['id'] == $id ){
                $res = $hardware;
                break;
            }
        }

        $name   = $res['name'];
        $brand  = $res['brand'];
        $line   = $res['line'];
        $model  = $res['model'];

        $serverBrandList = MysqlIdcOsServer::getInstance()->getAllBrand();

        $brandList  = array();
        $lineList   = array();
        $modelList  = array();
        foreach($serverBrandList as $serverBrand){
            if( !in_array($serverBrand['brand'],$brandList) ){
                $brandList[] = $serverBrand['brand'];
            }
            if( $serverBrand['brand'] == $brand && !in_array($serverBrand['line'],$lineList) ){
                $lineList[] = $serverBrand['line'];
            }
            if( $serverBrand['brand'] == $brand && $serverBrand['line'] == $line && !in_array($serverBrand['model'],$modelList) ){
                $modelList[] = $serverBrand['model'];
            }
        }
        
        $this->getView()->assign("name", $name);
        $this->getView()->assign("brand", $brand);
        $this->getView()->assign("line", $line);
        $this->getView()->assign("model", $model);
        $this->getView()->assign("brandlist", $brandList);
        $this->getView()->assign("linelist", $lineList);
        $this->getView()->assign("modellist", $modelList);
    }
    //机房位置管理
    public function locationAction()
    {

    }

    //添加机房
    public function newLocationAction()
    {

    }

    //修改机房
    public function updateLocationAction()
    {

    }

}
