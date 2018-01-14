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
 * Author: 当燃
 * Date: 2015-09-09
 */

namespace app\admin\logic;
use think\Model;
use think\Db;

class ServiceLogic extends Model
{
    /**
     * 获取筛选框搜索条件对应的ID
     * 如store_name就去store表获取store_id
     * @param $type
     * @param $qv
     * @return mixed
     */
    public function getConditionId($type,$qv){
            $where["$type"] = array('like','%'.$qv.'%');
            $model = explode('_',$type);
            $column = $model[0].'_id';
            if($type !='order_sn'){
                $id_arr=Db::name("$model[0]")->where($where)->getField("$column",true);
                $data["$column"]=['in',$id_arr];
            }else{
                $data["$type"] = array('like','%'.$qv.'%');
            }
        return $data;
    }

}