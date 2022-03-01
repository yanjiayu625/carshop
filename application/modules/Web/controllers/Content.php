<?php

use \Tools\Tools;
use \Mysql\Common\CommonModel as MysqlCommon;
use \DAO\Conf\ConfModel as Conf;

/**
 * 内容频道
 */
class ContentController extends \Base\Controller_AbstractWeb
{
    /**
     * 上传附件
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function uploadFileAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            $type = $this->getRequest()->getQuery('type');

            if (empty($postInfo['file_type'])) {
                throw new \Exception("上传文件类型不能为空");
            }

            if (!in_array($postInfo['file_type'], ['image', 'video'])) {
                throw new \Exception("上传文件类型不能非法");
            }

            if (empty($_FILES['file'])) {
                throw new \Exception("上传文件为空");
            }

            $fileInfo = $_FILES['file'];

            if (!is_uploaded_file($fileInfo["tmp_name"])) {
                throw new \Exception("上传文件不存在");
            }

            if ($postInfo['file_type'] == 'image' && $fileInfo["size"] > 1024000) {
                throw new \Exception("图片文件太大,不能超过10M");
            }
            if ($postInfo['file_type'] == 'video' && $fileInfo["size"] > 2048000) {
                throw new \Exception("视频文件太大,不能超过10M");
            }

            $fileDir = '/upload/' . $type . '/' . date("Ymd");

            if (!file_exists(STORAGE_DIR . $fileDir)) {
                mkdir(STORAGE_DIR . $fileDir, 0777, true);
            }

            $fileDir .= '/' . $fileInfo['name'];

            $fileName = STORAGE_DIR . $fileDir;

            if (!move_uploaded_file($fileInfo["tmp_name"], $fileName)) {
                throw new \Exception('上传文件失败');
            }

            $res['code'] = 200;
            $res['data'] = $fileDir;

        } catch (Exception $e) {
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 获取频道列表
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getChannelListAction()
    {

        $res['code'] = 200;
        $res['data'] = Conf::$channelArr;

        Tools::returnAjaxJson($res);
    }

      /**
     * 获取列表
     * @author zhangyang7
     * @time   2022-02-15
     * @return json
     */
    public function getContentListAction()
    {
        $postInfo = $this->getRequest()->getPost('');

        try {
            if(empty($postInfo['pageNum'])){
                throw new Exception('页数为空');
            }
            $pageNum = $postInfo['pageNum'];

            if(empty($postInfo['pageSize'])){
                throw new Exception('每页数量为空');
            }
            $pageSize =$postInfo['pageSize'];

            $limitStr = ($pageNum - 1) * $pageSize . ',' . $pageSize;
            $where = ['status' => 1];

            $contentCount= MysqlCommon::getInstance()->getCountByWhere('content_list', $where);
            $contentList = MysqlCommon::getInstance()->getListLimitByTableName('content_list', ['id', 'title',
                'abstract', 'thumbnail_dir'], $where, null, null, $limitStr);

            $res['code'] = 200;
            $res['data'] = [
                'total' => $contentCount,
                'list' => $contentList,
            ];

        } catch (\Exception $e) {

            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

    /**
     * 获取列表
     * @author zhangyang7
     * @time   2022-02-27
     * @return json
     */
    public function getContentInfoAction()
    {
        $postInfo = $this->getRequest()->getPost('');

        try {
            if(empty($postInfo['content_id'])){
                throw new Exception('页数为空');
            }
            $contentInfo = MysqlCommon::getInstance()->getInfoByTableName('content_list', ['id', 'title',
                'abstract', 'thumbnail_dir'], ['id'=>$postInfo['content_id']]);
            //以后优化成读取redis
            $contentInfo['details'] = MysqlCommon::getInstance()->getInfoByTableName('content_details', ['details'],
                ['content_id'=>$postInfo['content_id']]);

            $res['code'] = 200;
            $res['data'] = $contentInfo;

        } catch (\Exception $e) {

            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }


    /**
     * 添加内容列表信息
     * @author zhangyang7
     * @time   2022-02-28
     * @return json
     */
    public function addContentInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlCommon::getInstance()->beginTransaction();
            if (empty($postInfo['channel_id'])) {
                throw new \Exception('频道ID为空!');
            }

            $addInfo['channel_id'] = $postInfo['channel_id'];

            if (empty($postInfo['title'])) {
                throw new \Exception('车系信息为空!');
            }

            if (empty(trim($postInfo['title']))) {
                throw new \Exception('内容标题信息为空!');
            }
            $addInfo['title'] = trim($postInfo['title']);

            if (empty(trim($postInfo['abstract']))) {
                throw new \Exception('内容摘要信息为空!');
            }
            $addInfo['abstract'] = trim($postInfo['abstract']);

            if (empty($postInfo['thumbnail_dir'])) {
                throw new \Exception('列表缩略图片为空!');
            }
            $addInfo['thumbnail_dir'] = trim($postInfo['thumbnail_dir']);

            if (empty($postInfo['content'])) {
                throw new \Exception('详细内容为空!');
            }
            $addInfo['content'] = trim($postInfo['content']);

            $addInfo['status'] = 1;
            $addRes = MysqlCommon::getInstance()->addInfoByTableName('content_list', $addInfo);
            if (!$addRes) {
                throw new \Exception('添加信息内容时失败!');
            }

            $detailsInfo['content_id'] = $addRes;
            $detailsInfo['details'] = $postInfo['content'];

            $addContent = MysqlCommon::getInstance()->addInfoByTableName('content_details', $detailsInfo);
            if (!$addContent) {
                throw new \Exception('添加信息内容时失败!');
            }
            MysqlCommon::getInstance()->commitTransaction();

            $res['code'] = 200;
            $res['msg'] = '添加成功';

        } catch (Exception $e) {
            MysqlCommon::getInstance()->rollbackTransaction();
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }


    /**
     * 修改内容列表信息
     * @author zhangyang7
     * @time   2022-02-28
     * @return json
     */
    public function modifyContentInfoAction()
    {
        $postInfo = $this->getRequest()->getPost();

        try {
            MysqlCommon::getInstance()->beginTransaction();

            if (empty($postInfo['content_id'])) {
                throw new \Exception('内容ID为空!');
            }

            if (empty($postInfo['channel_id'])) {
                throw new \Exception('频道ID为空!');
            }

            $modifyInfo['channel_id'] = $postInfo['channel_id'];

            if (empty($postInfo['title'])) {
                throw new \Exception('车系信息为空!');
            }

            if (empty(trim($postInfo['title']))) {
                throw new \Exception('内容标题信息为空!');
            }
            $modifyInfo['title'] = trim($postInfo['title']);

            if (empty(trim($postInfo['abstract']))) {
                throw new \Exception('内容摘要信息为空!');
            }
            $modifyInfo['abstract'] = trim($postInfo['abstract']);

            if (empty($postInfo['thumbnail_dir'])) {
                throw new \Exception('列表缩略图片为空!');
            }
            $modifyInfo['thumbnail_dir'] = trim($postInfo['thumbnail_dir']);

            if (empty($postInfo['content'])) {
                throw new \Exception('详细内容为空!');
            }
            $modifyInfo['content'] = trim($postInfo['content']);

            if (empty($postInfo['status'])) {
                throw new \Exception('状态为空!');
            }
            $modifyInfo['status'] = $postInfo['status'];

            $modifyRes = MysqlCommon::getInstance()->updateListByTableName('content_list', $modifyInfo ,
                ['id' => $postInfo['content_id']]);
            if (!$modifyRes) {
                throw new \Exception('修改信息内容时失败!');
            }

            $detailsInfo['details'] = $postInfo['content'];

            $modifyContent = MysqlCommon::getInstance()->updateListByTableName('content_details', $detailsInfo,
                ['content_id' => $postInfo['content_id']]);
            if (!$modifyContent) {
                throw new \Exception('修改内容详情时失败!');
            }
            MysqlCommon::getInstance()->commitTransaction();

            $res['code'] = 200;
            $res['msg'] = '修改成功';

        } catch (Exception $e) {
            MysqlCommon::getInstance()->rollbackTransaction();
            $res['code'] = 400;
            $res['msg'] = $e->getMessage();
        }

        Tools::returnAjaxJson($res);
    }

}