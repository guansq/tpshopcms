<?php
namespace app\seller\validate;
use think\Validate;
class Coupon extends Validate
{
    // 验证规则
    protected $rule = [
        ['name', 'require|unique:coupon,name^type'],
        ['money', 'require'],
        ['condition', 'require|checkCondition'],
        ['createnum', 'require'],
        ['type', 'require'],
        ['send_start_time', 'checkSendTime'],
//        ['send_end_time', 'require'],
        ['use_start_time', 'checkUserTime'],
        ['use_type', 'checkUseType'],
    ];
    //错误信息
    protected $message  = [
        'name.require'                  => '优惠券名称必须',
        'name.unique'                   => '已有相同类型的优惠券名称',
        'money.require'                 => '请填写优惠券面额',
        'condition.require'             => '请填写消费金额',
        'condition.checkCondition'      => '消费金额不能小于或等于优惠券金额',
        'createnum.require'             => '请填写发放数量',
        'type.require'                  => '请选择发放类型',
//        'send_start_time.require'       => '请选择发放开始日期',
        'send_start_time.checkSendTime' => '发放结束日期不得小于发放开始日期',
//        'send_end_time.require'         => '请选择发放结束日期',
        'use_start_time.checkUserTime'  => '使用结束日期不得小于使用开始日期',
        'use_type.checkUseType'         => '请选择指定可使用的商品或分类',
    ];
    /**
     * 检查发放日期
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected function checkSendTime($value, $rule ,$data)
    {
        if($data['type'] > 0){
            return (empty($data['send_end_time']) ||  empty($value)) ? '请选择发起止日期' : true;
        }
        return true;
    }

    /**
     * 检查用户使用时间
     * @param $value
     * @param $rile
     * @param $data
     * @return bool
     */
    protected function checkUserTime($value,$rile,$data){
        return ($value >= $data['use_end_time']) ? false : true;
    }
    /**
     * 检查消费金额
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected function checkCondition($value, $rule ,$data)
    {
        return ($value < $data['money']) ? false : true;
    }
    /**
     * 检查指定商品ID或分类
     * @param $value|验证数据
     * @param $rule|验证规则
     * @param $data|全部数据
     * @return bool|string
     */
    protected  function checkUseType($value, $rule ,$data){
        if($value == 1 && empty($data['goods_id'])){
            return '请选择指定可使用的商品';
        }
        if($value == 2 && empty($data['cat_id3'])){
            return '请选择指定可使用的商品分类';
        }
        return true;
    }
}