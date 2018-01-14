<?php
namespace app\admin\validate;
use think\Validate;
class StoreClass extends Validate
{
    // 验证规则
    protected $rule = [
        ['sc_name', 'require|unique:store_class'],
        ['sc_bail','require|number'],
        ['sc_sort','require|number'],
    ];
    //错误信息
    protected $message  = [
        'sc_name.require'        => '名称必须',
        'sc_name.unique'         => '已存在店铺分类',
        'sc_bail.require'        => '保证金额度必须',
        'sc_bail.number'         => '保证金额度必须是数字',
        'sc_sort.require'        => '排序必须',
        'sc_sort.number'         => '排序必须为数字',
    ];
}