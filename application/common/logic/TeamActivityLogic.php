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
 * Author: lhb
 * Date: 2017-05-15
 */

namespace app\common\logic;

use think\Model;

/**
 * 拼团活动逻辑类
 */
class TeamActivityLogic extends Model
{
    public function getCouponOrderList($order, $userCouponList)
    {
        $userCouponArray = collection($userCouponList)->toArray();
        $couponNewList = [];
        foreach ($userCouponArray as $couponKey => $couponItem) {
            //过滤掉购物车没有的店铺优惠券
            if ($userCouponArray[$couponKey]['store_id'] == $order['store_id']) {
                if ($order['goods_price'] >= $userCouponArray[$couponKey]['coupon']['condition']) {
                    $userCouponArray[$couponKey]['coupon']['able'] = 1;
                } else {
                    $userCouponArray[$couponKey]['coupon']['able'] = 0;
                }
                $couponNewList[] = $userCouponArray[$couponKey];
            }
        }
        return $couponNewList;
    }

    /**
     * 检查该单是否可以拼
     * @param $team_found|开团对象
     * @param $team|活动对象
     * @return array
     */
    public function TeamFoundIsCanFollow($team_found, $team)
    {
        if($team_found['team_id'] != $team['team_id']){
            return ['status' => 0, 'msg' => '该拼单数据不存在或已失效', 'result' => ''];
        }
        if($team_found['join'] >= $team_found['need']){
           return ['status' => 0, 'msg' => '该单已成功结束', 'result' => ''];
        }
        if(time() - $team_found['need'] > $team['time_limit']){
            return ['status' => 0, 'msg' => '该拼单已过期', 'result' => ''];
        }
        return ['status' => 1, 'msg' => '能拼', 'result' => ''];
    }


}