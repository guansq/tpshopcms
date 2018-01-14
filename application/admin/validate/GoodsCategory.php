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
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\admin\validate;
use think\Validate;
class GoodsCategory extends Validate {
    //验证规则
    protected $rule = [
        'name'  => 'require|checkName',
        'mobile_name'  => 'require|checkMoblieName',
        'sort_order'   => 'number'
    ];

    //错误消息
    protected $message = [
        'name.require'                  => '分类名称必须填写',
        'name.checkName'                => '分类名称已经存在',
        'mobile_name.require'           => '手机分类名称必须填写',
        'mobile_name.checkMoblieName'   => '手机分类名称已经存在',
        'sort_order.number'             => '排序必须为数字',
    ];

    /**
     * 验证分类名
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function checkName($value,$rule,$data){
        if(empty($data['id'])){
            if(M('goods_category')->where(['name'=>$value])->count()){
                return false;
            }
        }else{
            if(M('goods_category')->whereNotIn('id',$data['id'],'AND')->where(['name'=>$value])->count()){
                return false;
            }
        }
        return true;
    }
    /**
     * 验证手机分类名
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function checkMoblieName($value,$rule,$data){
        if(empty($data['id'])){
            if(M('goods_category')->where(['mobile_name'=>$value])->count()){
                return false;
            }
        }else{
            if(M('goods_category')->whereNotIn('id',$data['id'],'AND')->where(['mobile_name'=>$value])->count()){
                return false;
            }
        }
        return true;
    }
}
