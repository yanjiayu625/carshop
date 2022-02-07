<?php

namespace DAO\Data;

use DAO\AbstractModel;
use Mysql\Common\CommonModel;

class MessageTemplateModel extends AbstractModel
{
    private const TABLE_MESSAGE_TEMPLATE = 'perf_message_template';

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/11 7:26 下午
     * @param array $filter
     * @return array
     */
    private static function getTemplateData(array $filter = [])
    {
        return CommonModel::getInstance()->getListByTableName(self::TABLE_MESSAGE_TEMPLATE, ['*'], $filter);
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/11 7:31 下午
     * @param int $id
     * @param array $values
     * @return array
     */
    private static function updateData(int $id, array $values)
    {
        $values['u_time'] = date('Y-m-d H:i:s');
        return CommonModel::getInstance()->updateListByTableName(self::TABLE_MESSAGE_TEMPLATE, $values, ['id' => $id]);
    }
    
    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/16 3:55 下午
     * @return array
     */
    public static function getAllTemplateList()
    {
        return self::getTemplateData(['id != ' => '0']);
    }
    
    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/12 2:34 下午
     * @param string $code
     * @return mixed
     */
    public static function getTemplateByCode(string $code)
    {
        return self::getTemplateData(compact('code'))[0] ?? [];
    }
    
    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/11 7:27 下午
     * @param int|null $id
     * @return bool
     */
    public static function templateExists(?int $id)
    {
        return !empty(self::getTemplateData(compact('id')));
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/15 5:10 下午
     * @param string|null $code
     * @return bool
     */
    public static function templateCodeExists(?string $code)
    {
        return !empty(self::getTemplateData(compact('code')));
    }

    /**
     * @param int $id
     * @param string $purpose
     * @param string $wechatTitle
     * @param string $wechatContent
     * @param string $emailTitle
     * @param string $emailContent
     * @return array
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/11 7:31 下午
     */
    public static function updateTemplate(int $id, string $purpose, string $instructions,
          string $wechatTitle, string $wechatContent, string $emailTitle, string $emailContent)
    {
        return self::updateData($id, [
            'purpose' => $purpose,
            'instructions' => $instructions,
            'wechat_title' => $wechatTitle,
            'wechat_content' => $wechatContent,
            'email_title' => $emailTitle,
            'email_content' => $emailContent,
        ]);
    }

    /**
     * 模板占位符为 <placeholder>
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/11 9:33 下午
     * @param string $code
     * @param array $params
     * @return array
     */
    public static function renderTemplate(string $code, array $params = [])
    {
        $templateInfo = self::getTemplateByCode($code);

        $wechatTitle = $templateInfo['wechat_title'];
        $wechatContent = $templateInfo['wechat_content'];
        $wechatUrl = $templateInfo['wechat_url'];
        $emailTitle = $templateInfo['email_title'];
        $emailContent = $templateInfo['email_content'];
        $emailUrl = $templateInfo['email_url'];

        $params['wechatHost'] = WECHAT_HOST ?? 'https://merp.staff.ifeng.com';
        $params['host'] = SYSTEM_HOST;
        foreach ($params as $name => $value) {
            $placeHolder = self::decoratePlaceholder($name);

            $wechatTitle = str_replace($placeHolder, $value, $wechatTitle);
            $wechatContent = str_replace($placeHolder, $value, $wechatContent);
            $wechatUrl = str_replace($placeHolder, $value, $wechatUrl);
            $emailTitle = str_replace($placeHolder, $value, $emailTitle);
            $emailContent = str_replace($placeHolder, $value, $emailContent);
            $emailUrl = str_replace($placeHolder, $value, $emailUrl);
        }

        return [$wechatTitle, $wechatContent, $wechatUrl, $emailTitle, $emailContent, $emailUrl];
    }

    /**
     * @author tanghan <tanghan@ifeng.com>
     * @time 2021/11/12 3:05 下午
     * @param string $placeholderName
     * @return string
     */
    private static function decoratePlaceholder(string $placeholderName)
    {
        return '{' . $placeholderName . '}';
    }
}