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
 * Date: 2015-09-14
 */


namespace app\admin\logic;
use app\admin\model\SpecGoodsPrice;
use app\common\logic\GoodsPromFactory;
use think\Db;
use think\Model;

class OrderLogic extends Model
{
    /**
     * @param array $condition  搜索条件
     * @param string $order   排序方式
     * @param int $start    limit开始行
     * @param int $page_size  获取数量
     */
    public function getOrderList($condition,$order='',$start=0,$page_size=20){
        $res = M('order')->where($condition)->limit("$start,$page_size")->order($order)->select();
        return $res;
    }
    /*
     * 获取订单商品详情
     */
    public function getOrderGoods($order_id){
        $select_year = getTabByOrderId($order_id);
        $sql = "SELECT g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total FROM __PREFIX__order_goods{$select_year} o ".
            "LEFT JOIN __PREFIX__goods g ON o.goods_id = g.goods_id WHERE o.order_id = $order_id";
        $res = Db::query($sql);
        return $res;
    }

    /*
     * 获取订单信息
     */
    public function getOrderInfo($order_id)
    {
        //  订单总金额查询语句		
        $select_year = getTabByOrderId($order_id);
        $order = M('order'.$select_year)->where("order_id = $order_id")->find();
        $order['address2'] = $this->getAddressName($order['province'],$order['city'],$order['district']);
        $order['address2'] = $order['address2'].$order['address'];		
        return $order;
    }

    /*
     * 根据商品型号获取商品
     */
    public function get_spec_goods($goods_id_arr){
    	if(!is_array($goods_id_arr)) return false;
    		foreach($goods_id_arr as $key => $val)
    		{
    			$arr = array();
    			$goods = M('goods')->where("goods_id = $key")->find();
    			$arr['goods_id'] = $key; // 商品id
    			$arr['goods_name'] = $goods['goods_name'];
    			$arr['goods_sn'] = $goods['goods_sn'];
    			$arr['market_price'] = $goods['market_price'];
    			$arr['goods_price'] = $goods['shop_price'];
    			$arr['cost_price'] = $goods['cost_price'];
    			$arr['member_goods_price'] = $goods['shop_price'];
                        $arr['store_id'] = $goods['store_id'];
    			foreach($val as $k => $v)
    			{
    				$arr['goods_num'] = $v['goods_num']; // 购买数量
    				// 如果这商品有规格
    				if($k != 'key')
    				{
    					$arr['spec_key'] = $k;
    					$spec_goods = M('spec_goods_price')->where("goods_id = $key and `key` = '{$k}'")->find();
    					$arr['spec_key_name'] = $spec_goods['key_name'];
    					$arr['member_goods_price'] = $arr['goods_price'] = $spec_goods['price'];
    					$arr['sku'] = $spec_goods['sku'];
    				}
    				$order_goods[] = $arr;
    			}
    		}
    		return $order_goods;	
    }

    /*
     * 订单操作记录
     */
    public function orderActionLog($order_id,$action,$note='',$action_user = 0,$user_type = 0){
        $order = M('order')->where(array('order_id'=>$order_id))->find();
        $data['order_id'] = $order_id;
        $data['action_user'] = $action_user; // 操作者 session('seller_id');
        $data['user_type'] = $user_type; // 0管理员 1商家 2前台用户
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;        
        return M('order_action')->add($data);//订单操作记录
    }

    /*
     * 获取订单商品总价格
     */
    public function getGoodsAmount($order_id){
        $sql = "SELECT SUM(goods_num * goods_price) AS goods_amount FROM __PREFIX__order_goods WHERE order_id = {$order_id}";
        $res = DB::query($sql);
        return $res[0]['goods_amount'];
    }

    /**
     * 得到发货单流水号
     */
    public function get_delivery_sn()
    {
		mt_srand((double) microtime() * 1000000);
        return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /*
     * 获取当前可操作的按钮
     */
    public function getOrderButton($order){
        // 三个月以前订单无任何操作按钮
        if(time() - $order['add_time'] > (86400 * 90))
            return array();        
        /*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         * 
         */
    	$os = $order['order_status'];//订单状态
    	$ss = $order['shipping_status'];//发货状态
    	$ps = $order['pay_status'];//支付状态
        $btn = array();
        if($order['pay_code'] == 'cod') {
        	if($os == 0 && $ss == 0){
        		$btn['confirm'] = '确认';
        	}elseif($os == 1 && $ss == 0 ){
        		$btn['delivery'] = '去发货';
        		$btn['cancel'] = '取消确认';
        	}elseif($ss == 1 && $os == 1 && $ps == 0){
        		$btn['pay'] = '付款';
        	}elseif($ps == 1 && $ss == 1 && $os == 1){
        		$btn['pay_cancel'] = '设为未付款';
        	}
        }else{
        	if($ps == 0 && $os == 0){
        		$btn['pay'] = '付款';
        	}elseif($os == 0 && $ps == 1){
        		$btn['pay_cancel'] = '设为未付款';
        		$btn['confirm'] = '确认';
        	}elseif($os == 1 && $ps == 1 && $ss==0){
        		$btn['cancel'] = '取消确认';
        		$btn['delivery'] = '去发货';
        	}
        } 
               
        if($ss == 1 && $os == 1 && $ps == 1){
        	$btn['delivery_confirm'] = '确认收货';
        	$btn['refund'] = '申请退货';
        }elseif($os == 2 || $os == 4){
        	$btn['refund'] = '申请退货';
        }elseif($os == 3 || $os == 5){
        	$btn['remove'] = '移除';
        }
        if($os != 5){
        	$btn['invalid'] = '无效';
        }
        return $btn;
    }

    
    public function orderProcessHandle($order_id,$act,$store_id = 0){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
                $order_sn = M('order')->where("order_id = $order_id")->getField("order_sn");
                update_pay_status($order_sn); // 调用确认收货按钮
    			return true;    			
    		case 'pay_cancel': //取消付款
    			$updata['pay_status'] = 0;
    			$this->order_pay_cancel($order_id);
    			return true;
    		case 'confirm': //确认订单
    			$updata['order_status'] = 1;
    			break;
    		case 'cancel': //取消确认
    			$updata['order_status'] = 0;
    			break;
    		case 'invalid': //作废订单
    			$updata['order_status'] = 5;
    			break;
    		case 'remove': //移除订单
    			$this->delOrder($order_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			confirm_order($order_id); // 调用确认收货按钮
    			return true;
    		default:
    			return true;
    	}                
    	return M('order')->where("order_id = $order_id and store_id = $store_id")->save($updata);//改变订单状态
    }
    
    
    //管理员取消付款
    function order_pay_cancel($order_id)
    {
    	//如果这笔订单已经取消付款过了
    	$count = M('order')->where("order_id = $order_id and pay_status = 1")->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
    	if($count == 0) return false;
    	// 找出对应的订单
    	$order = M('order')->where("order_id = $order_id")->find();
    	// 增加对应商品的库存
    	$orderGoodsArr = M('OrderGoods')->where("order_id = $order_id")->select();
    	foreach($orderGoodsArr as $key => $val)
    	{
    		if(!empty($val['spec_key']))// 有选择规格的商品
    		{   // 先到规格表里面增加数量 再重新刷新一个 这件商品的总数量
				$SpecGoodsPrice = new SpecGoodsPrice();
				$specGoodsPrice = $SpecGoodsPrice::get(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']]);
				$specGoodsPrice->setInc('store_count',$val['goods_num']);
    			refresh_stock($val['goods_id']);
    		}else{
				$specGoodsPrice = null;
    			M('Goods')->where("goods_id = {$val['goods_id']}")->setInc('store_count',$val['goods_num']); // 增加商品总数量
    		}
    		M('Goods')->where("goods_id = {$val['goods_id']}")->setDec('sales_sum',$val['goods_num']); // 减少商品销售量
    		//更新活动商品购买量
    		if($val['prom_type']==1 || $val['prom_type']==2){
				$GoodsPromFactory = new GoodsPromFactory();
				$goodsPromLogic = $GoodsPromFactory->makeModule($val, $specGoodsPrice);
    			$prom = $goodsPromLogic->getPromModel();
    			if($prom['status'] == 1 && $prom['is_end'] == 0){
    				$tb = $val['prom_type']==1 ? 'flash_sale' : 'group_buy';
    				M($tb)->where("id=".$val['prom_id'])->setDec('buy_num',$val['goods_num']);
    				M($tb)->where("id=".$val['prom_id'])->setDec('order_num');
    			}
    		}
    	}
    	// 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
    	M('order')->where("order_id=$order_id")->save(array('pay_status'=>0));
    	update_user_level($order['user_id']);
    	// 记录订单操作日志
    	logOrder($order['order_id'],'订单取消付款','付款取消',$order['user_id']);
    	//分销设置
    	M('rebate_log')->where("order_id = {$order['order_id']}")->save(array('status'=>0));
    }
    
    /**
     *	处理发货单
     * @param array $data  查询数量
     */
    public function deliveryHandle($data,$store_id){
		$order = $this->getOrderInfo($data['order_id']);
		$orderGoods = $this->getOrderGoods($data['order_id']);
		$selectgoods = $data['goods'];
		$data['order_sn'] = $order['order_sn'];
		$data['delivery_sn'] = $this->get_delivery_sn();
		$data['zipcode'] = $order['zipcode'];
		$data['user_id'] = $order['user_id'];
		$data['admin_id'] = session('seller_id');
		$data['consignee'] = $order['consignee'];
		$data['mobile'] = $order['mobile'];
		$data['country'] = $order['country'];
		$data['province'] = $order['province'];
		$data['city'] = $order['city'];
		$data['district'] = $order['district'];
		$data['address'] = $order['address'];
		$data['shipping_code'] = $order['shipping_code'];
		$data['shipping_name'] = $order['shipping_name'];
		$data['shipping_price'] = $order['shipping_price'];
		$data['create_time'] = time();
        $data['store_id'] = $store_id;
                
		$did = M('delivery_doc')->add($data);
		$is_delivery = 0;
		foreach ($orderGoods as $k=>$v){
			if($v['is_send'] == 1){
				$is_delivery++;
			}			
			if($v['is_send'] == 0 && in_array($v['rec_id'],$selectgoods)){
				$res['is_send'] = 1;
				$res['delivery_id'] = $did;
                $r = M('order_goods')->where("rec_id={$v['rec_id']}  and store_id = $store_id")->save($res);//改变订单商品发货状态
				$is_delivery++;
			}
		}
		$updata['shipping_time'] = time();
		if($is_delivery == count($orderGoods)){
			$updata['shipping_status'] = 1;
		}else{
			$updata['shipping_status'] = 2;
		}
                
        M('order')->where("order_id ={$data['order_id']} and store_id = $store_id")->save($updata);//改变订单状态
	    $seller_id = session('seller_id');
		$s = $this->orderActionLog($order['order_id'],'订单发货',$data['note'],$seller_id);//操作日志
		return $s && $r;
    }

    /**
     * 获取地区名字
     * @param int $p
     * @param int $c
     * @param int $d
     * @return string
     */
    public function getAddressName($p=0,$c=0,$d=0){
        $p = M('region')->where(array('id'=>$p))->field('name')->find();
        $c = M('region')->where(array('id'=>$c))->field('name')->find();
        $d = M('region')->where(array('id'=>$d))->field('name')->find();
        return $p['name'].','.$c['name'].','.$d['name'].',';
    }

    /**
     * 删除订单
     *
     */
    function delOrder($order_id,$store_id){
    	return M('order')->where(array('order_id'=>$order_id,'store_id'=>$store_id))->update(['deleted'=>1]);
    }

    public function getRefundGoodsMoney($return_goods){
    	$order_goods = M('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
    	if($return_goods['is_receive'] == 1){
    		if($order_goods['give_integral']>0){
    			$user = get_user_info($return_goods['user_id']);
    			if($order_goods['give_integral']>$user['pay_points']){
    				//积分被使用则从退款金额里扣
    				$return_goods['refund_money'] = $return_goods['refund_money'] - $order_goods['give_integral']/100;
    			}
    		}

    		$coupon_info = M('coupon_list')->where(array('uid'=>$return_goods['user_id'],'get_order_id'=>$return_goods['order_id']))->find();
    		if(!empty($coupon_info)){
    			if($coupon_info['status'] == 1) { //如果优惠券被使用,那么从退款里扣
    				$coupon = M('coupon')->where(array('id' => $coupon_info['cid']))->find();
    				if ($return_goods['refund_money'] > $coupon['money']) {
    					//退款金额大于优惠券金额，先从这里扣
    					$return_goods['refund_money'] = $return_goods['refund_money'] - $coupon['money'];
    				}
    			}
    		}
    	}
    	return $return_goods['refund_money'];
    }

    /**
     * 重新计算退款(最终退货涉及到要追回送的积分，优惠券那些)
     * @param $return_id
     * @return int
     */
    public function getFinalReturnInfo($return_id){
        $return_goods = M('return_goods')->where(array('id'=>$return_id))->find();
        empty($return_goods) && $this->error("参数有误");
        //追回积分
        $order_goods = M('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
        if($order_goods['give_integral']>0){
            $user = get_user_info($return_goods['user_id']);
            if($order_goods['give_integral'] > $user['pay_points']){
                //积分不够则从退款金额里扣
            	$point_to_money = $order_goods['give_integral']/tpCache('shopping.point_rate');  //按比例将积分转换成金额
                $return_goods['refund_money'] = $return_goods['refund_money'] - $point_to_money; //这时候的值可能是负数
            }else{
                //追回积分
                //accountLog($return_goods['user_id'],0,-$order_goods['give_integral'],'退货积分追回',0,$return_goods['order_id'],$return_goods['order_sn']);
            }
        }
        //追回优惠券
        $coupon_info = M('coupon_list')->where(array('uid'=>$return_goods['user_id'],'get_order_id'=>$return_goods['order_id']))->find();
        if(!empty($coupon_info)){
            if($coupon_info['status'] == 1) { //如果优惠券被使用,那么从退款里扣
                $coupon = M('coupon')->where(array('id' => $coupon_info['cid']))->find();
                $return_goods['refund_money'] = $return_goods['refund_money'] - $coupon['money'];
            }else{
                M('coupon_list')->where(array('id' => $coupon_info['id']))->delete();
                M('coupon')->where(array('id' => $coupon_info['cid']))->setDec('send_num');//优惠券追回
            }
        }
        if($return_goods['refund_money'] < 0){  
        	//说明金额不够扣，那么就得扣余额了,按照平台设置，这里不会出现支付余额不够扣的情况，非得超过你就扣他余额
            $return_goods['refund_deposit'] = $return_goods['refund_deposit'] + $return_goods['refund_money'];
            $return_goods['refund_money'] = 0; //退款金额为0
        }
        $data=[
            'refund_integral' =>$return_goods['refund_integral'],
            'refund_money' => $return_goods['refund_money'],
            'refund_deposit' => $return_goods['refund_deposit'],
        ];
        M('return_goods')->where(['id' => $return_goods['id']])->save($data);
        $return_goods['refund_integral'] = $return_goods['refund_integral'];  //最终要退的积分
        $return_goods['refund_money'] = $return_goods['refund_money'];  //最终要退的金额
        $return_goods['refund_deposit'] = $return_goods['refund_deposit']; //最终要退的余额
        return $return_goods;
    }

    /**
     * 支付原路退回
     * @param $return_goods
     */
    public function MoneyReturnToOriginal($return_goods){
        $order = M('order')->where(array('order_id'=>$return_goods['order_id']))->find();
        if($order['pay_code'] == 'weixin' || $order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile'){
            if($order['pay_code'] == 'weixin'){
                include_once  PLUGIN_PATH."payment/weixin/weixin.class.php";
                $payment_obj =  new \weixin();
                $data = array('transaction_id'=>$order['transaction_id'],'total_fee'=>$order['order_amount'],'refund_fee'=>$return_goods['refund_money']);
                $result = $payment_obj->payment_refund($data);
                if($result['return_code'] == 'SUCCESS'){
                    updateRefundGoods($return_goods['rec_id']);//订单商品售后退款
                    $this->success('退款成功');
                }else{
                    $this->error($result['return_msg']);
                }
            }else{
                include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
                $payment_obj = new \alipay();
                $detail_data = $order['transaction_id'].'^'.$return_goods['refund_money'].'^'.'用户申请订单退款';
                $data = array('batch_no'=>date('YmdHi').'r'.$return_goods['rec_id'],'batch_num'=>1,'detail_data'=>$detail_data);
                $payment_obj->payment_refund($data);
            }
        }else{
            $this->error('该订单支付方式不支持在线退回');
        }
    }
}