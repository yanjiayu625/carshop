<?php

namespace DAO\Data;

use DAO\Data\FileModel as File;
use Intervention\Image\Facades\Image;
use Mysql\Common\CommonModel as MysqlCommon;
use Yaf\Registry;

class ImageModel {
    private static $_tableName = 'image';
    private $_id = null;
    private $_fileId = null;
    private $_file = null;
    private $_thumb = null;
    private $_medium = null;
    private $_scale = null;

    public function __construct(FileModel $file, $thumb = null, $medium = null, $scale = null)
    {
        $this->_file = $file;
        $this->_fileId = $file->getId();
        $this->_thumb = $thumb;
        $this->_medium = $medium;
        $this->_scale = $scale;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function getId()
    {
        return $this->_id;
    }

    /**
     * 获取文件信息
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 18:26
     * @return FileModel|null
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * ID获取图片
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 18:26
     * @param $id
     * @return ImageModel|null
     */
    public static function getImageById($id)
    {
        $imageInfo = MysqlCommon::getInstance()->getInfoByTableName(self::$_tableName, null, ['id' => $id]);
        if (empty($imageInfo))
            return null;

        $file = FileModel::getFileById($imageInfo['file_id']);
        $image = new self($file, $imageInfo['thumb'], $imageInfo['medium']);
        $image->setId($imageInfo['id']);
        return $image;
    }

    /**
     * 通过文件获取图片
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/16 18:26
     * @param $fileId
     * @return ImageModel|null
     */
    public static function getImageByFileId($fileId)
    {
        $imageInfo = MysqlCommon::getInstance()->getInfoByTableName(self::$_tableName, null, ['file_id' => $fileId]);
        if (empty($imageInfo))
            return null;

        $image = new self($imageInfo['file_id'], $imageInfo['thumb'], $imageInfo['medium']);
        return $image;
    }

    private function _generateImage ($type, int $width, int $height, $quality = null)
    {
        if (!is_string($type)){
            return false;
        }

        if (is_null($this->_file)) {
            return false;
        }

        if (!file_exists($this->_file->getFilePath()))
            throw new \Exception('File Not Exists!');

        $image = imagecreatefromstring(file_get_contents($this->_file->getFilePath()));

        if (!$image) {
            throw new \Exception('Image Open Failed');
        }

        $imageInfo = getimagesize($this->_file->getFilePath());
        if (empty($imageInfo)) {
            throw new \Exception('Image Info Error!');
        }

        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];

        if ($srcWidth >= $srcHeight) {
            // 宽 >= 高
            $height = $srcHeight * (floatval($width) / $srcWidth);
        } else {
            // 宽 < 高
            $width = $srcWidth * (floatval($height) / $srcHeight);
        }

        $newImage = @imagecreatetruecolor($width, $height);
        if (!$newImage) {
            throw new \Exception('Create Image Failed!');
        }
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        $copyRes = @imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
        if (!$copyRes) {
            throw new \Exception('Image Copy Resampled Failed');
        }

        $pathInfo = @pathinfo($this->_file->getFilePath());
        $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . ".$type" . "." . $pathInfo['extension'];

        $detectType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->_file->getFilePath());

        switch ($detectType) {
            case 'image/jpeg':
                imagejpeg($newImage, $newPath, $quality);
                break;
            case 'image/png':
                imagepng($newImage, $newPath);
                break;
            case 'image/gif':
                imagegif($newImage, $newPath);
                break;
            case 'image/wbmp':
                imagewbmp($newImage, $newPath);
                break;
        }

        imagedestroy($image);

        return $newPath;
    }

    public function generateThumb(int $width = 210, int $height = 210)
    {
        $this->_thumb = $this->_generateImage('thumb', $width, $height, 85);
        return $this->_thumb;
    }

    public function generateMedium(int $width = 800, int $height = 800)
    {
        $this->_medium = $this->_generateImage('medium', $width, $height, 85);
        return $this->_medium;
    }

    public function copyImage($type)
    {
        $pathInfo = @pathinfo($this->_file->getFilePath());
        $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . ".$type" . "." . $pathInfo['extension'];

        $newFile = file_put_contents($newPath, file_get_contents($this->_file->getFilePath()));

        switch ($type) {
            case 'thumb':
                $this->_thumb = $newPath;
                break;
            case 'medium':
                $this->_medium = $newPath;
                break;
        }

        return $newPath;
    }
    
    /**
     * 等比例压缩到40K
     * @author tanghan <tanghan@ifeng.com>
     * @time 2020/4/20 14:18
     */
    public function generateScale()
    {
        $imageInfo = getimagesize($this->_file->getFilePath());
        if ($imageInfo === false)
            throw new \Exception('get image size failed!');

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        $limit = 40 * 1024;
        $size = floatval(filesize($this->_file->getFilePath()));

        // 3.35 控制最后的图片压缩为40K
        $scale = $size <= $limit ? 1 : sqrt($limit/ $size);

        $newWidth = $width * $scale;
        $newHeight = $height * $scale;
        $this->_scale = $this->_generateImage('scale', $newWidth, $newHeight, 100);

        return $this->_scale;
    }

    public function thumbUrl()
    {
        if (empty($this->_thumb))
            return '';

        return $this->_imageUrl($this->_thumb);
    }

    public function mediumUrl()
    {
        if (empty($this->_medium))
            return '';
        return $this->_imageUrl($this->_medium);
    }

    public function typeValid()
    {
        $file = $this->getFile();

        $filePath = $file->getFilePath();
        $detectType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);

        return strpos($detectType, 'image/') === 0;
    }

    public function save()
    {
        if (is_null($this->_fileId)) {
            return false;
        }

        $data = [
            "file_id" => $this->_fileId,
            'thumb' => $this->_thumb ?? '',
            'medium' => $this->_medium ?? '',
        ];

        try {
            MysqlCommon::getInstance()->beginTransaction();
            $transaction = true;

            $addRes = MysqlCommon::getInstance()->addInfoByTableName(self::$_tableName, $data);

            if (!$addRes) {
                throw new \Exception('图片入库失败');
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

    private function _imageUrl($path)
    {
        $relPath = str_replace(APPLICATION_PATH, '', $path);

        $domain = Registry::get('config')->get('Wechat.domain');
        if (empty($domain))
            throw new Exception('服务器错误: 未配置域名');

        return  $domain . $relPath;
    }
}