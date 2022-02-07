<?php
    /**
     * IdcOs系统
     */
class DeviceController extends \Base\Controller_AbstractIdcosIndex
{

    //网段列表
    public function listAction()
    {
    }

    //设备详细情况
    public function detailAction()
    {
        $id = $this->getRequest()->getQuery("id");

        $this->getView()->assign("id", $id);

    }

    //编辑
    public function editAction()
    {
        $id = $this->getRequest()->getQuery("id");

        $this->getView()->assign("id", $id);

    }

    //设备详细情况录入
    public function newAction()
    {
    }

    /**
     * 批量导入设备
     * @param   file csv
     * @return  json
     */
    public function uploadAction()
    {
        $flag = true;

        $file = $this->getRequest()->getFiles("csv");

        if ($flag === true) {
            if (!is_uploaded_file($file["tmp_name"])) {
                $msg = "上传Excel文件不存在!";
                $flag = false;
            }
        }

//        if ($flag === true) {
//            if (substr($file['name'], -5) != '.xlsx') {
//                $msg = "上传文件格式不正确";
//                $flag = false;
//            }
//        }

        //获取CSV中的数据
        if ($flag === true) {
            $csvData = array();

            $col = array(1 => 'sn', 2 => 'ip', 3 => 'mac',4 => 'oobip', 5 => 'ostmpname', 6 => 'hardwarename');

            $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
            $PHPExcel = $reader->load($file['tmp_name']); // 文档名称

            $sheet = $PHPExcel->getSheet(0); // 读取第一个工作表(编号从 0 开始)

            $row = $sheet->getHighestRow(); // 取得总行数
            $column = $sheet->getHighestColumn(); // 取得总列数

            for ($r = 2; $r <= $row; $r++) {
                $cArr = array();

                for ($c = 0; $c <= ord($column) - 65; $c++) {
                    if($sheet->getCellByColumnAndRow($c, $r)->getValue() == null){
                        $cArr[$col[$c+1]] = '';
                    }else{
                        $cArr[$col[$c+1]] =  $sheet->getCellByColumnAndRow($c, $r)->getValue();
                    }
                }
                $csvData[] = $cArr;
            }

            if( empty($csvData) ){
                $this->getView()->assign("uploadStatus", false);
                $this->getView()->assign("msg", '文件内容不能为空');
            }else{
                $redisKey = \Business\IdcOs\DeviceModel::saveCsvData($csvData);

                $this->getView()->assign("tempRedisKey", $redisKey);
                $this->getView()->assign("uploadStatus", true);
            }

        }else{
            $this->getView()->assign("uploadStatus", false);
            $this->getView()->assign("msg", $msg);
        }

    }

    //等待安装的设备列表
    public function waitingAction()
    {
    }

    //正在安装的设备列表
    public function installingAction()
    {
    }

    //安装失败的设备列表
    public function failureAction()
    {
    }

    //等待安装的设备列表
    public function completeAction()
    {
    }

    //安装日志查询
    public function logAction()
    {
    }

}
