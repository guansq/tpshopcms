<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 */

namespace app\admin\validate;

use think\Validate;

class Role extends Validate
{
    //验证规则
    protected $rule = [
        'role_name'  => 'checkRoleName|notEmpty|unique:admin_role',
        'role_desc'  => 'require|notEmpty',
        'act_list'   => 'require|notEmpty'
    ];
    
    //错误消息
    protected $message = [
        'role_name'     => '角色名称不能为空',
        'role_name.checkRoleName' => '角色名称已经存在',
        'role_desc'     => '角色描述不能为空',
        'act_list'      => '权限分配必须选择',
    ];
    
    //验证场景
    protected $scene = [
        'save'  => ['role_name', 'role_desc', 'act_list'],
    ];
    
    protected function notEmpty($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value)) {
            return false;
        }
        return true;
    }

    /**
     * 添加角色时要验证一下名称是否重复，编辑不验证
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected  function  checkRoleName($value,$rule,$data){
        if(empty($data['role_id'])){
           $res = M('admin_role')->where(['role_name'=>$value])->count();
            if($res){
                return false;
            }
        }
        return true;
    }
}
