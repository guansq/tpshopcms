<?php
namespace app\seller\validate;
use think\Validate;
use think\Db;
class PromOrder extends Validate
{
    // 验证规则
    protected $rule = [
        ['title', 'require|max:50'],
        ['type', 'require'],
        ['expression','require|checkExpression'],
        ['money','require'],
        ['start_time','require'],
        ['end_time','require|checkEndTime'],
//        ['prom_img','require'],
    ];
    //错误信息
    protected $message  = [
        'title.require'         => '促销标题必须',
        'title.max'             => '促销标题小于50字符',
        'type.require'          => '活动类型必须',
        'goods_id.require'      => '请选择参与促销的商品',
        'expression.require'    => '请填写优惠体现',
        'money.require'         => '请填写最小使用金额',
        'money.require'         => '立减金额不能大于等于需要满足的金额',
        'start_time.require'    => '请选择开始时间',
        'end_time.require'      => '请选择结束时间',
        'end_time.checkEndTime' => '结束时间不能早于开始时间',
//        'prom_img.require'      => '图片必须',
    ];
    /**
     * 检查结束时间
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected function checkEndTime($value, $rule ,$data)
    {
        return ($value < $data['start_time']) ? false : true;
    }
    /**
     * 检查活动最小满足金额
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected function checkExpression($value, $rule ,$data){
        if($data['type'] == 1){
            return ($value >= $data['money'] || empty($value)) ? false : true;
        }
        return true;
    }
}