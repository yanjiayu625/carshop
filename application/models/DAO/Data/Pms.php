<?php

namespace DAO\Data;

use \Redis\Common\CommonModel as RedisCommon;
use \ExtInterface\Erp\ApiModel as ExtErpApi;

/**
 * Amt配置数据
 */
class PmsModel extends \DAO\AbstractModel
{
    /**
     * 流程状态
     * @var array
     */
    public static $issueStatus = [
        '0'  => '流程撤回',
        '10' => '审批中',
        '11' => '审批退回',
        '3'  => '审批通过',
        '9'  => '流程封存',
        '91' => '流程废弃',
        '92' => '流程变更',
        '1' => '流程保存',
    ];

    /**
     * 事项审批, 查询列表的流程类型Array
     * @var array
     */
    public static $approveWorkflowType  = [
        ['type' => '考勤', 'value' => '2, 3, 4, 5, 9, 12, 63', 'total' => 0],
        ['type' => '绩效', 'value' => '55,56,57,58,59', 'total' => 0],
        ['type' => '报销', 'value' => '16, 17, 38', 'total' => 0],
        ['type' => '法务', 'value' => '48, 49', 'total' => 0],
        ['type' => '安全', 'value' => '51, 60', 'total' => 0],
        ['type' => '招聘', 'value' => '53,54', 'total' => 0],
//        ['type' => '用车', 'value' => [6], 'total' => 0],
//        ['type' => '出差', 'value' => [15], 'total' => 0],
//        ['type' => 'IT系统', 'value' => [8, 13, 31, 32], 'total' => 0],
//        ['type' => '办公用品', 'value' => [19], 'total' => 0],
//        ['type' => '资产', 'value' => [21, 22, 23, 24, 25, 26, 27, 28], 'total' => 0],
//        ['type' => '其他', 'value' => [14, 29, 30], 'total' => 0],
    ];

    /**
     * 重复提交校验
     * @var array
     */
    public static $repeatSubmitAction = [
        'createissue',
        'withdrawissue',
        'postreminder',
        'passissue',
        'batchpassissue',
        'rejectissue',
        'signissue',
        'addissue',
        'notifyissue',
        'passandmodify',
        'fallback'
    ];


    /**
     * 单例模式获取类实例
     */
    private static $_instance = null;

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

}
