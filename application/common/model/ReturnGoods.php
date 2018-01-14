<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\common\model;
use think\Model;

class ReturnGoods extends Model{

    //状态描述,(后续完成)
    public function getReturnStatusAttr($value, $data)
    {
        $status =  C('REFUND_STATUS');
        $return_type =  C('RETURN_STATUS');
        switch($data['stats']){
            case -2: $res = ''; break;
            case -1:; break;
            case 1 :; break;
            case 2 :; break;
            case 6 :; break;
        }
        if($return_type == 0){  //退款
            switch($data['stats']){
                case 5 :; break;
            }
        }
        if($return_type == 1){  //退货退款
            switch($data['stats']){
                case 2 :; break;
                case 3 :; break;
                case 5 :; break;
            }
        }
        if($return_type == 2){  //换货
            switch($data['stats']){
                case 2 :; break;
                case 3 :; break;
                case 4 :; break;
            }
        }
        if($return_type == 3){  //维修
            switch($data['stats']){
                case 2 :; break;
                case 3 :; break;
            }
        }
        return $res;
    }
}