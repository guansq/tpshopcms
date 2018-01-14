<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/4
 * Time: 11:27
 */

namespace app\seller\model;
use think\model;
use think\Db;

class Order  extends model{

    /**
     * 获取店铺今天的销售状况
     * @param $store_id
     * @return mixed
     */
    public function getTodayAmount($store_id){
        $now = strtotime(date('Y-m-d'));
        $today_order = Db::name('order')->where(['add_time'=>['gt',$now],'store_id'=>$store_id])->select();
        $today['today_order']=$today['cancel_order'] =0;
        $goods_price=$total_amount=$order_prom_amount=0;
        foreach($today_order as $key=>$order){
            $today['today_order'] +=1;  //今日总订单
            if($order['order_status']==3 ){
                $today['cancel_order'] +=1;  //今日取消订单
            }
            if(($order['order_status']==1 || $order['order_status'] == 2 || $order['order_status']==4) && ($order['pay_status']== 1 || $order['pay_code'] =='cod')){
                $goods_price +=$order['goods_price']; //今日订单商品总价
                $total_amount +=$order['total_amount']; //今日已收货订单总价
                $order_prom_amount +=$order['order_prom_amount']; //今日订单优惠
            }
        }
        $today['today_amount'] = $goods_price-$order_prom_amount; //今日销售总额（有效下单）
        return $today;
    }
}