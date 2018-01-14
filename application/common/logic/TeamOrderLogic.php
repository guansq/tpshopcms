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

use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use think\Db;
use think\Model;

/**
 * 拼团订单逻辑类
 */
class TeamOrderLogic extends Model
{
    protected $team;// 拼团模型
    protected $order;//订单模型
    protected $goods;//商品模型
    protected $orderGoods;//订单商品模型.
    protected $specGoodsPrice;//商品规格模型
    protected $store;//商家模型
    protected $goodsBuyNum;//购买的商品数量
    protected $user_id = 0;//user_id
    protected $teamFound;//开团模型

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * 设置拼团模型
     * @param $team
     */
    public function setTeam($team)
    {
        $this->team = $team;
    }

    /**
     * 设置商品模型
     * @param $goods
     */
    public function setGoods($goods)
    {
        $this->goods = $goods;
    }

    /**
     * 设置商品规格模型
     * @param $specGoodsPrice
     */
    public function setSpecGoodsPrice($specGoodsPrice)
    {
        $this->specGoodsPrice = $specGoodsPrice;
    }

    /**
     * 设置订单模型
     * @param $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * 设置订单商品模型
     * @param $orderGoods
     */
    public function setOrderGoods($orderGoods)
    {
        $this->orderGoods = $orderGoods;
    }

    /**
     * 设置店铺模型
     * @param $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * 设置购买的商品数量
     * @param $goodsBuyNum
     */
    public function setGoodsBuyNum($goodsBuyNum)
    {
        $this->goodsBuyNum = $goodsBuyNum;
    }

    /**
     * 设置开团模型
     * @param $teamFound
     */
    public function setTeamFound($teamFound){
        $this->teamFound = $teamFound;
    }

    /**
     * 下单
     * @return array
     */
    public function add()
    {
        if (empty($this->team) || $this->team['status'] != 1) {
            return ['status' => 0, 'msg' => '该商品拼团活动不存在或者已下架', 'result' => ''];
        }
        if (empty($this->goods) || $this->goods['is_on_sale'] != 1) {
            return ['status' => 0, 'msg' => '该商品拼团活动不存在或者已下架', 'result' => ''];
        }
        if ($this->team['item_id'] > 0 && empty($this->specGoodsPrice)) {
            return ['status' => 0, 'msg' => '该商品拼团活动不存在或者已下架', 'result' => ''];
        }
        if ($this->goodsBuyNum <= 0) {
            return ['status' => 0, 'msg' => '至少购买一份', 'result' => ''];
        }
        if ($this->goodsBuyNum > $this->team['buy_limit']) {
            return ['status' => 0, 'msg' => '购买数已超过该活动单次购买限制数(' . $this->team['buy_limit'] . ')', 'result' => ''];
        }
        $OrderLogic = new OrderLogic();
        $orderData = [
            'user_id' => $this->user_id,
            'order_sn' => $OrderLogic->get_order_sn(),
            'goods_price' => $this->team['team_price'] * $this->goodsBuyNum,
            'order_prom_id' => $this->team['team_id'],
            'order_prom_type' => 6,
            'add_time' => time(),
            'store_id' => $this->team['store_id'],
            'order_amount' => $this->team['team_price'] * $this->goodsBuyNum,
            'total_amount' => $this->team['team_price'] * $this->goodsBuyNum,
        ];
        $order = new Order();
        $order->data($orderData);
        $orderSave = $order->save();
        if ($orderSave !== false) {
            $goods_commission = Db::name('goods_category')->where("id", $this->goods['cat_id3'])->cache(true, TPSHOP_CACHE_TIME)->getField('commission');//商品抽成比例
            $orderGoodsData = [
                'order_id' => $order['order_id'],
                'goods_id' => $this->goods['goods_id'],
                'goods_name' => $this->goods['goods_name'],
                'goods_sn' => $this->goods['goods_sn'],
                'goods_num' => $this->goodsBuyNum,
                'market_price' => $this->goods['market_price'],
                'member_goods_price' => $this->team['team_price'],
                'cost_price' => $this->goods['cost_price'],
                'give_integral' => $this->goods['give_integral'],
                'prom_type' => 6,//拼团
                'prom_id' => $this->team['team_id'],
                'store_id' => $this->team['store_id'],
                'distribut' => $this->team['goods']['distribut'],
                'commission' => $goods_commission,
            ];
            if ($this->specGoodsPrice) {
                $orderGoodsData['goods_price'] = $this->specGoodsPrice['price'];
                $orderGoodsData['spec_key'] = $this->specGoodsPrice['key'];
                $orderGoodsData['spec_key_name'] = $this->specGoodsPrice['key_name'];
                $orderGoodsData['sku'] = $this->specGoodsPrice['sku'];
            } else {
                $orderGoodsData['goods_price'] = $this->goods['shop_price'];
                $orderGoodsData['sku'] = $this->goods['sku'];
            }
            $orderGoods = new OrderGoods();
            $orderGoods->data($orderGoodsData);
            $orderGoodsSave = $orderGoods->save();
            if (session('?user')) {
                $user = session('user');
            }else{
                $user = Db::name('users')->field('nickname,head_pic')->where('user', $this->user_id)->find();
            }

            if($this->teamFound){
                /**团员拼团s**/
                $team_follow_data = [
                    'follow_user_id' => $user['user_id'],
                    'follow_user_nickname' => $user['nickname'],
                    'follow_time' => time(),
                    'order_id' => $order['order_id'],
                    'found_id' => $this->teamFound['found_id'],
                    'found_user_id' => $this->teamFound['user_id'],
                    'team_id' => $this->team['team_id'],
                ];
                Db::name('team_follow')->insert($team_follow_data);
                /***团员拼团e***/
            }else{
                /***团长开团s***/
                $team_found_data = [
                    'found_time'=>time(),
                    'user_id' => $this->user_id,
                    'team_id' => $this->team['team_id'],
                    'nickname' => $user['nickname'],
                    'head_pic' => $user['head_pic'],
                    'order_id' => $order['order_id'],
                    'need' => $this->team['needer'],
                    'price'=> $this->team['team_price'],
                    'goods_price' => $orderGoods['goods_price'],
                ];
                Db::name('team_found')->insert($team_found_data);
                /***团长开团e***/
            }
            if ($orderGoodsSave !== false) {
                return ['status' => 1, 'msg' => '提交拼团订单成功', 'result' => ['order_id' => $order['order_id']]];
            } else {
                return ['status' => 0, 'msg' => '拼团商品下单失败', 'result' => ''];
            }
        } else {
            return ['status' => 0, 'msg' => '拼团商品下单失败', 'result' => ''];
        }
    }

    /**
     * 更改订单商品购买商品数
     * @param $goodsNum
     */
    public function changeNum($goodsNum)
    {
        if ($goodsNum != $this->orderGoods['goods_num']) {
            $this->orderGoods->goods_num = $goodsNum;
            $this->order->goods_price = $this->orderGoods->member_goods_price * $goodsNum;
            $this->order->order_amount = $this->orderGoods->member_goods_price * $goodsNum;
            $this->order->total_amount = $this->orderGoods->member_goods_price * $goodsNum;
        }
    }

    /**
     * 使用优惠券
     * @param $couponId
     */
    public function useCouponById($couponId)
    {
        if ($couponId) {
            $couponLogic = new CouponLogic();
            $couponMoney = $couponLogic->getCouponMoney($this->user_id, $couponId, $this->order['store_id']);
            $this->order->coupon_price = $couponMoney;
            $this->order->order_amount = $this->order->order_amount - $couponMoney;
        }
    }

    /**
     * 选择物流，配送地址
     * @param $shipping_code
     * @param $UserAddress
     */
    public function useShipping($shipping_code, $UserAddress)
    {
        if ($shipping_code) {
            $shipping = Db::name('Plugin')->where("code", $shipping_code)->cache(true, TPSHOP_CACHE_TIME)->find();
            if ($shipping) {
                // 如果没有设置满额包邮 或者 额度达不到包邮 则计算物流费
                $goodsLogic = new GoodsLogic();
                if ($this->store['store_free_price'] == 0 || $this->order['goods_price'] < $this->store['store_free_price']) {
                    $shippingMoney = $goodsLogic->getFreight($shipping_code, $UserAddress['province'], $UserAddress['city'], $UserAddress['district'], $this->orderGoods['goods_num'] * $this->goods['weight'], $this->order['store_id']);
                    $this->order->shipping_code = $shipping['code'];
                    $this->order->shipping_name = $shipping['name'];
                    $this->order->shipping_price = $shippingMoney;
                    $this->order->order_amount = $this->order->order_amount + $shippingMoney;
                    $this->order->total_amount = $this->order->total_amount + $shippingMoney;
                }
            }
        }
    }

    /**
     * 使用余额
     * @param $user_money
     */
    public function useUserMoney($user_money)
    {
        if ($user_money) {
            $user_money = ($user_money > $this->order->order_amount) ? $this->order->order_amount : $user_money;
            $this->order->user_money = $user_money;
            $this->order->order_amount = $this->order->order_amount - $user_money;
        }
    }

    /**
     * 使用积分
     * @param $pay_points
     */
    public function usePayPoints($pay_points)
    {
        //使用积分
        if ($pay_points) {
            $point_rate = tpCache('shopping.point_rate'); //兑换比例
            // 积分支付 100 积分等于 1块钱
            $integral_money = $pay_points / $point_rate;
            // 假设应付 1块钱 而用户输入了 200 积分 2块钱, 那么就让 $pay_points = 1块钱 等同于强制让用户输入1块钱
            $integral_money = ($integral_money > $this->order->order_amount) ? $this->order->order_amount : $integral_money;
            $this->order->integral = $integral_money * $point_rate; //以防用户使用过多积分的情况
            $this->order->integral_money = $integral_money;
            $this->order->order_amount = $this->order->order_amount - $integral_money; //  积分抵消应付金额
        }
    }

    /**
     * 返回订单模型
     * @return mixed
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * 返回订单商品模型
     * @return mixed
     */
    public function getOrderGoods()
    {
        return $this->orderGoods;
    }

    /**
     * 拼团支付后操作
     * @param $order
     * @throws \think\Exception
     */
    public function doOrderPayAfter($order){
        $teamFound = TeamFound::get(['order_id' => $order['order_id']]);
        //团长的单
        if ($teamFound) {
            $teamFound->found_time = time();
            $teamFound->status = 1;
            $teamFound->save();
        }else{
            //团员的单
            $teamFollow = TeamFollow::get(['order_id' => $order['order_id']]);
            if($teamFollow){
                $teamFollow->status = 1;
                $teamFollow->save();
                //更新团长的单
                $teamFollow->team_found->join = $teamFollow['team_found']['join'] + 1;//参团人数+1
                //如果参团人数满足成团条件
                if($teamFollow->team_found->join >= $teamFollow->team_found->need){
                    $teamFollow->team_found->status = 2;
                }
                $teamFollow->team_found->save();
            }

        }


    }

}