<?php

namespace DAO\Data;

use Yaf\Registry;
use Jobby\Exception;
use Mysql\Common\CommonModel as MysqlCommon;

class FileModel
{
    private static $_tableName = 'file';
    private $_id = null;
    private $_uid = null;
    private $_cname = null;
    private $_purpose = null;
    private $_project = null;
    private $_path = null;

    public function __construct($uid, $cname, $purpose, $project, $path)
    {
        $this->_uid = $uid;
        $this->_cname = $cname;
        $this->_purpose = $purpose;
        $this->_project = $project;
        $this->_path = $path;
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 17:54
     * @param $id
     * @return FileModel
     */
    public static function getFileById($id)
    {
        $fileInfo = MysqlCommon::getInstance()->getInfoByTableName(self::$_tableName, null, ['id' => $id]);
        if (empty($fileInfo))
            return null;

        $file = new self($fileInfo['uid'], $fileInfo['cname'], $fileInfo['purpose'], $fileInfo['project'], $fileInfo['path']);
        $file->setId($id);

        return $file;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getFilePath()
    {
        return $this->_path;
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 17:42
     */
    public function save()
    {
        $data = [
            'uid' => $this->_uid,
            'cname' => $this->_cname,
            'purpose' => $this->_purpose,
            'project' => $this->_project,
            'path' => $this->_path,
        ];

        try {
            MysqlCommon::getInstance()->beginTransaction();
            $transaction = true;
            $addRes = MysqlCommon::getInstance()->addInfoByTableName(self::$_tableName, $data);

            if (!$addRes) {
                throw new \Exception('文件入库失败');
            }

            MysqlCommon::getInstance()->commitTransaction();
            $transaction = false;

            $this->_id = $addRes;
            return true;
        } catch (\Exception $exception) {
            if (isset($transaction) && $transaction) {
                MysqlCommon::getInstance()->rollbackTransaction();
            }

            return false;
        }
    }

    /**
     * 更新文件信息
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 18:03
     * @param array $options
     * @return bool
     */
    public function update($options = [])
    {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                switch ($key) {
                    case 'uid':
                        $this->_uid = $value;
                        break;
                    case 'cname':
                        $this->_cname = $value;
                        break;
                    case 'purpose':
                        $this->_purpose = $value;
                        break;
                    case 'project':
                        $this->_project = $value;
                        break;
                    case 'path':
                        $this->_path = $value;
                }
            }
        }

        if (empty($this->_id))
            return false;

        $data = [
            'uid' => $this->_uid,
            'cname' => $this->_cname,
            'purpose' => $this->_purpose,
            'project' => $this->_project,
            'path' => $this->_path,
            'u_time' => date('Y-m-d H:i:s')
        ];

        try {
            MysqlCommon::getInstance()->beginTransaction();
            $transaction = true;

            $updateRes = MysqlCommon::getInstance()->updateListByTableName(self::$_tableName, $data, ['id' => $this->_id]);
            if (!$updateRes) {
                throw new \Exception('');
            }

            MysqlCommon::getInstance()->commitTransaction();
            $transaction = false;

            return true;
        } catch (\Exception $exception) {
            if (isset($transaction) && $transaction) {
                MysqlCommon::getInstance()->rollbackTransaction();
            }
            return false;
        }
    }

    /**
     * 返回访问URL
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 18:08
     * @return string
     */
    public function url()
    {
        $relPath = str_replace(APPLICATION_PATH, '', $this->_path);

        $domain = Registry::get('config')->get('system.host');
        if (empty($domain))
            throw new Exception('服务器错误: 未配置域名');

        return  $domain . $relPath;
    }

    public function getImage()
    {
        if (empty($this->_id) || !$image = ImageModel::getImageByFileId($this->_id)) {
            $image = new ImageModel($this);
        }

        return $image;
    }

    /**
     * 文件上传
     * @author likexin
     * @time   2020-08-11 10:37:46
     * @param 所属用户 $uid
     * @param 属性 $purpose
     * @param 项目 $project
     * @param 文件信息 $fileInfo
     * @param 处理参数 $options
     * @return void
     */
    public static function stashFile($uid, $purpose = 'uploads', $project = 'default', array $fileInfo = [], array $options = [])
    {
        if (empty($purpose) || empty($project) || empty($fileInfo)) {
            throw new \Exception('暂存文件参数为空');
        }

        $dir = STORAGE_DIR . "$purpose/$project/";
        if ($purpose != 'templates') {
            $dir .= date('Ymd') . '/';
        }
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = $dir . $fileInfo['name'];
        if (!move_uploaded_file($fileInfo['tmp_name'], $filePath)) {
            throw new \Exception('上传文件失败');
        }
        $uidInfo = MysqlCommon::getInstance()->getInfoByTableName('data_center_all_user', ['name'], ['uid' => $uid]);
        if (empty($uidInfo)) {
            throw new \Exception('用户信息查询失败');
        }

        $file = new self($uid, $uidInfo['name'],  $purpose, $project, $filePath);
        $file->save();

        $detectedType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
        if ($detectedType === false) {
            throw new \Exception('mime type error!');
        }

        // 图片压缩
        if (strpos($detectedType, 'image/') === 0 && !empty($options['compression'])) {

            $image = $file->getImage();

            if ($fileInfo['size'] < 40 * 1024 || in_array($detectedType, ['image/x-icon'])) {
                // 小于40K 不进行压缩
                $image->copyImage('thumb');
                $image->copyImage('medium');
            } else {
                $image->generateThumb();
                $image->generateMedium();
            }

            $image->save();
        }

        return $file->url();
    }
}
