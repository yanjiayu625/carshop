<?php

namespace DAO\Conf;

/**
 * 系统所有配置信息
 */
class ConfModel extends \Bll\AbstractModel
{

    /**
     * 考核活动配置列表 状态
     * @var array
     */
    public static $eventStatusName = ['1'=>'未发布','2'=>'进行中','3'=>'已完成'];

    /**
     * 考核流程列表
     * @var array
     */
    public static $processName = ['1'=>'绩效指标填写', '2'=>'总监确认考核关系','3'=>'绩效指标回顾与调整', '4'=>'员工填写自述',
                                    '5'=>'绩效评分','6'=>'绩效面谈'];
    /**
     * 周期类型
     * @var array
     */
    public static $cycleTypeName = ['annual'=>'年度','semi'=>'半年度','quarterly'=>'季度', 'monthly'=>'月度'];

    /**
     * 公司职级
     * @var array
     */
    public static $levelName1 = ['E1-1'=>'主管','E1-2'=>'高级主管','E2-1'=>'经理', 'E2-2'=>'经理', 'E2-3'=>'高级经理',
        'E3-1'=>'副总监','E3-2'=>'总监','E3-3'=>'高级总监','E3-4'=>'总经理',
        'E4'=>'VP', 'E4-1'=>'副总裁', 'E4-2'=>'高级副总裁', 'E5'=>'CXO', 'E6'=>'总裁', 'E7'=>'首席执行官',
        'I1'=>'I1 初级', 'I1-1'=>'I1-1 初级', 'I1-2'=>'I1-2 初级',
        'I2'=>'I2 熟练', 'I2-1'=>'I2-1 熟练', 'I2-2'=>'I2-2 熟练', 'I2-3'=>'I2-3 熟练',
        'I3'=>'I3 高级', 'I3-1'=>'I3-1 高级', 'I3-2'=>'I3-2 高级', 'I3-3'=>'I3-3 高级',
        'I4'=>'I4 资深', 'I4-1'=>'I4-1 资深', 'I4-2'=>'I4-2 资深', 'I4-3'=>'I4-3 资深',
        'I5'=>'I5 专家', 'I5-1'=>'I5-1 专家', 'I5-2'=>'I5-2 专家',
        'I6'=>'I6 高级专家', 'I6-1'=>'I6-1 高级专家', 'I6-2'=>'I6-2 高级专家', 'I7'=>'I7 资深专家',
        'O1'=>'O1 操作助理', 'O2'=>'O2 操作专员',
    ];


    /**
     * 公司职级
     * @var array
     */
    public static $levelName = [
        '操作助理'=>'O1',
        '操作专员'=>'O2',
        'I1'=>'I1,I1-1,I1-2',
        'I2'=>'I2,I2-1,I2-2,I2-3',
        'I3'=>'I3,I3-1,I3-2,I3-3',
        '主管'=>'E1-1',
        '高级主管'=>'E1-2',
        'I4'=>'I4,I4-1,I4-2,I4-3',
        'I5'=>'I5,I5-1,I5-2,I5-3',
        'I6'=>'I6,I6-1,I6-2',
        'I7'=>'I7',
        'I8'=>'I8',
        '经理'=>'E2-1',
        '高级经理'=>'E2-2',
        '副总监'=>'E3-1',
        '总监'=>'E3-2',
        '高级总监'=>'E3-3',
        '总经理'=>'E3-4',
        '管理层'=>'E4,E4-1,E4-2,E4-3'
    ];

    /**
     * 配置条件
     * @var array
     */
    public static $piConfig = ['dept'=>'部门','rank'=>'职级', 'user'=>'人员'];

    /**
     * 员工类型
     * @var array
     */
    public static $employType = ['正式员工','实习生','外包人员','外包(实习)','外包(无系统使用权)',
        '派遣员工', '兼职/顾问/劳务', '外援', '退休'];

    /**
     * 员工状态
     * @var array
     */
    public static $employStatus = ['在职','离职-辞职','离职-劝退','离职-辞退'];

    /**
     * 休假类型
     * @var array
     */
    public static $vacationType = ['年假','事假','病假','产假','护理假','婚假','工伤假','丧假','哺乳假','产检假','探亲假'];

    /**
     * 公司所有VP
     * @var array
     */
    public static $vpList = ['liuchun'=>'刘春','houcy'=>'侯春艳','chenming'=>'陈明',
        'chixy'=>'池小燕','zouming'=>'邹明','jinmy'=>'金明岩','lvjing'=>'吕靖'];

    /**
     * 我的绩效进度
     * @var array
     */
    public static $proList = ['1'=>['code'=>'indicator','name'=>'指标制定'],'2'=>['code'=>'adjust','name'=>'指标回顾调整'],
        '3'=>['code'=>'readme','name'=>'填写自述'], '4'=>['code'=>'score','name'=>'评分审核'],
        '5'=>['code'=>'interview','name'=>'绩效面谈'],'6'=>['code'=>'next','name'=>'下期指标制定']];

    /**
     * 我的绩效进度
     * @var array
     */
    public static $overviewList = ['1'=>['name'=>'员工指标确认','num'=>0, 'click'=>false],
        '2'=>['name'=>'员工自评','num'=>0, 'click'=>false],
        '3'=>['name'=>'本环节打分','num'=>0, 'click'=>false],
        '4'=>['name'=>'绩效面谈','num'=>0, 'click'=>false],
        '5'=>['name'=>'下期指标制定','num'=>0, 'click'=>false]];

    /**
     * 打分值数组
     * @var array
     */
    public static $scoreList = [5,4.75,4.5,4.25,4,3.75,3.5,3.25,3,2.75,2.5,2.25,2,1.75,1.5,1.25,1,0.75,0.5,0.25,0];

    /**
     * 系统角色配置
     * @var array
     */
    public static $roleList = ['DH'=>'部门负责人','VP'=>'VP', 'BP'=>'BP', 'OD'=>'OD'];

}
