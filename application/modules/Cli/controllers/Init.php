<?php

use \Mysql\Issue\ServerModel        as MysqlIssueServer;
use \Mysql\Issue\ServerInfoModel    as MysqlIssueServerInfo;
use \Mysql\Issue\UserModel          as MysqlUserInfo;
/**
 * 常用功能,
 */
class InitController extends \Base\Controller_AbstractCli
{
    /**
     * 根据result和status判断提案进行 php cli/cli.php request_uri='/cli/Init/getTokenIssue'
     */
    public function getTokenIssueAction()
    {
        //如果已经不续约,最后更新人为api
        $privilegeList = MysqlIssueServer::getInstance()->getServerList(array('id','info_id','i_applicant','i_department','user','u_time'),
            array('i_type'=>'privilege','i_status'=>4,'i_result'=>1,'u_time >'=>"2017-07-01 00:00:00",'u_time <'=>"2018-07-01 00:00:00"));
        foreach($privilegeList as $privilegeInfo) {
            $serverInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('infoJson'),
                array('id' => $privilegeInfo['info_id']));
            $serverInfo = json_decode($serverInfoJson['infoJson'], true);
            if ($serverInfo['t_item'] == 'VPN'){

                $tokenInfo = MysqlIssueServer::getInstance()->getOneServer(array('id','info_id','c_time','u_time'),
                    array('i_type'=>'token','user'=>$privilegeInfo['user']));
                $tokenInfoJson = MysqlIssueServerInfo::getInstance()->getOneServerInfo(array('checkJson'),
                    array('id' => $tokenInfo['info_id']));
                $tokenInfoArr = json_decode($tokenInfoJson['checkJson'], true);
                $checkList = $tokenInfoArr['list']['check'];
                $leader = '';
                foreach ($checkList as $key => $value){
                    $leader = $key;
                    break;
                }
                if(empty($leader)){
                    if(!empty($tokenInfoArr['leader'])){
                        $leader = $tokenInfoArr['leader'];
                    }
                }
                if(empty($leader)){
                    $userInfo = MysqlUserInfo::getInstance()->getOneUserInfo(array('leader'),array('user'=>$privilegeInfo['user']));
                    if(!empty($userInfo['leader'])){
                        $leader = $userInfo['leader'];
                    }else{
                        $leader = '';

                    }
                }
                if (!empty($leader)){

                    $leaderInfo = MysqlUserInfo::getInstance()->getOneUserInfo(array('cname'),array('user'=>$leader));
                    if(!empty($leaderInfo)){
                        $leader = $leaderInfo['cname'];
                    }else{
                        $leader = '';
                    }

                }

                echo "{$privilegeInfo['user']}#{$privilegeInfo['i_applicant']}#{$privilegeInfo['i_department']}#{$leader}#{$tokenInfo['c_time']}#{$tokenInfo['u_time']}\n";
                
            }

        }

    }


    /**
     * 初始化可用性系统名称 php cli/cli.php request_uri='/cli/Init/initAvailabilitySystem'
     */
    public function initAvailabilitySystemAction()
    {
//
//        $fileDir = APPLICATION_PATH . '/data/excel/技术部各中心管理员与审计员.xlsx';
//
//        $reader = \PHPExcel_IOFactory::createReader('Excel2007'); // 读取 excel 文档
//        $PHPExcel = $reader->load($fileDir); // 文档名称
//
//        //第一个工作表
//
//        $sheet = $PHPExcel->getSheet(5); // 读取第一个工作表(编号从 0 开始)
//
//        $row = $sheet->getHighestRow(); // 取得总行数
//        $column = $sheet->getHighestColumn(); // 取得总列数
//
//        $dept = '运维中心';
//        for ($r = 2; $r <= $row; $r++) {
//            $cArr = array();
//            for ($c = 0; $c <= ord($column) - 65; $c++) {
//                $cell = $sheet->getCellByColumnAndRow($c, $r)->getFormattedValue();
//                if (is_object($cell)) $cell = $cell->__toString();
//                $cArr[] = $cell;
//            }
//            if(!empty($cArr[0]) && !empty($cArr[1]) && !empty($cArr[2])){
//                $data = [];
//                $data['sys_name'] = $cArr[0];
//                $data['admin'] = $cArr[1];
//                $data['reviewer'] = $cArr[2];
//                $data['department'] = '技术部';
//                $data['center'] = $dept;
//                $data['domain'] = '';
//                $data['introduction'] = '';
//
//                $addRes = \Mysql\Issue\CommonModel::getInstance()->addNewData('TechnologyDepartSystem',$data);
//            }
//        }
//
//        die;

        $userList = \Mysql\Issue\CommonModel::getInstance()->getList('TechnologyDepartSystem',['id','admin','reviewer'],null);
        foreach ($userList as $userInfo){


            $adminArr = explode(',',$userInfo['admin']);
            $adUid = [];
            foreach ($adminArr as $admin){
                $uInfo = \Mysql\Issue\CommonModel::getInstance()->getOneInfo('IssueUserInfo',['user'],
                    ['cname'=>$admin, 'department'=>'技术部']);
                if(!empty($uInfo['user'])){
                    $adUid[] =  $uInfo['user'];
                }else{
                    $adUid[] = $admin;
                }
            }
            $adminUidStr = implode(',', $adUid);

            $reviewerArr = explode(',',$userInfo['reviewer']);
            $reUid = [];
            foreach ($reviewerArr as $reviewer){
                $uInfo = \Mysql\Issue\CommonModel::getInstance()->getOneInfo('IssueUserInfo',['user'],
                    ['cname'=>$reviewer, 'department'=>'技术部']);
                if(!empty($uInfo['user'])){
                    $reUid[] =  $uInfo['user'];
                }else{
                    $reUid[] = $reviewer;
                }
            }
            $reviewerUidStr = implode(',', $reUid);

            \Mysql\Issue\CommonModel::getInstance()->updateInfo('TechnologyDepartSystem',
                ['admin'=>$adminUidStr,'reviewer'=>$reviewerUidStr], ['id'=>$userInfo['id']]);
        }



    }
}
