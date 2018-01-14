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


namespace app\seller\logic;
use think\model;
use think\Db;
class OrderLogic extends model
{

    /**
     * 获取订单商品详情
     * @param $order_id  订单ID
     * @param string $is_send  状态
     * @return mixed
     */
    public function getOrderGoods($order_id,$is_send =''){
        if($is_send){
            $where=" and o.is_send < $is_send";
        }
        $select_year = getTabByOrderId($order_id);
        $sql = "SELECT g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total FROM __PREFIX__order_goods{$select_year} o ".
            "LEFT JOIN __PREFIX__goods g ON o.goods_id = g.goods_id WHERE o.order_id = :order_id ".$where;
        $res = Db::query($sql,['order_id'=>$order_id]);
        return $res;
    }

    /*
     * 获取订单信息
     */
    public function getOrderInfo($order_id)
    {
        //  订单总金额查询语句		
        $select_year = getTabByOrderId($order_id);
        $order = M('order'.$select_year)->where(array('order_id'=>$order_id,'store_id'=>STORE_ID))->find();
        if(!$order) return false;
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
    			$goods = M('goods')->where("goods_id", $key)->find();
    			$arr['goods_id'] = $key; // 商品id
    			$arr['goods_name'] = $goods['goods_name'];
    			$arr['goods_sn'] = $goods['goods_sn'];
    			$arr['market_price'] = $goods['market_price'];
    			$arr['goods_price'] = $goods['shop_price'];
    			$arr['cost_price'] = $goods['cost_price'];
    			$arr['member_goods_price'] = $goods['shop_price'];
    			foreach($val as $k => $v)
    			{
    				$arr['goods_num'] = $v['goods_num']; // 购买数量
    				// 如果这商品有规格
    				if($k != 'key')
    				{
    					$arr['spec_key'] = $k;
    					$spec_goods = M('spec_goods_price')->where(['goods_id'=>$key,'key'=>$k])->find();
    					$arr['spec_key_name'] = $spec_goods['key_name'];
    					$arr['member_goods_price'] = $arr['goods_price'] = $spec_goods['price'];
    					$arr['sku'] = $spec_goods['sku'];
    				}
    				$order_goods[] = $arr;
    			}
    		}
    		return $order_goods;	
    }

    /**
     * 订单操作记录
     * @param $order
     * @param $action
     * @param string $note
     * @param int $action_user
     * @param int $user_type
     * @return mixed
     */
    public function orderActionLog($order,$action,$note='',$action_user = 0,$user_type = 0){
        $data['order_id'] = $order['order_id'];
        $data['action_user'] = $action_user;
        $data['store_id'] = STORE_ID; 
        $data['user_type'] = $user_type; // 0管理员 1商家 2前台用户
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;        
        return M('order_action')->add($data);//订单操作记录
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
        	}elseif($os == 1 && $ss == 2){
				$btn['delivery'] = '去发货';
			}
        }else{
        	if($ps == 0 && $os == 0){
                //$btn['pay'] = '付款';
        	}elseif($os == 0 && $ps == 1){
        		$btn['pay_cancel'] = '设为未付款';
        		$btn['confirm'] = '确认';
        	}elseif($os == 1 && $ps == 1 && $ss==0){
        		$btn['cancel'] = '取消确认';
        		$btn['delivery'] = '去发货';
        	}elseif(($os == 1 && $ps == 1 && $ss==2)){
				$btn['delivery'] = '去发货';
			}
        } 
               
        if($ss == 1 && $os == 1 && $ps == 1){
        	$btn['delivery_confirm'] = '确认收货';
        	$btn['refund'] = '申请退货';
        }elseif($os == 2 || $os == 4){
        	$btn['refund'] = '申请退货';
        }elseif($os == 3 || $os == 5){
//        	$btn['remove'] = '移除';
        }
        if($os != 5 && $ps != 1){
        	$btn['invalid'] = '无效';
        }
        
        if($order['order_status'] < 2)
        {
             $btn['edit'] = '修改订单'; // 修改订单   
             $select_year = getTabByOrderId($order['order_id']);
             $c = M('order_goods'.$select_year)->where('order_id',$order['order_id'])->sum('goods_num');
             if($c >= 2)
                 $btn['split'] = '拆分订单'; // 拆分订单 
             
        }
        return $btn;
    }

    
    public function orderProcessHandle($order_id,$act,$store_id = 0){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
                $order_sn = M('order')->where("order_id", $order_id)->getField("order_sn");
                update_pay_status($order_sn); // 调用确认收货按钮
    			return true;    			
    		case 'pay_cancel': //取消付款
    			$updata['pay_status'] = 0;
    			break;
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
    			$this->delOrder($order_id,$store_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			confirm_order($order_id); // 调用确认收货按钮
    			return true;
    		default:
    			return true;
    	}                
    	return M('order')->where(['order_id'=>$order_id,'store_id'=>$store_id])->save($updata);//改变订单状态
    }

    /**
     * 获取订单进度显示状态
     * @author lxl
     * @param $order
     * @return int
     */
    public function getShowStatus($order)
    {
        $show_status = 1;
        if ($order['pay_status'] == 0 && $order['order_status'] == 0 && $order['pay_code'] != 'cod') {
            $show_status = 1;
        } else if (($order['pay_status'] == 1 || $order['pay_code'] == "cod") && $order['shipping_status'] != 1 && ($order['order_status'] == 0 || $order['order_status'] == 1)) {
            $show_status = 2;
        } else if (($order['shipping_status'] == 1 AND $order['order_status'] == 1)) {
            $show_status = 3;
        }  else if ($order['is_comment'] == 1) {//评论
            $show_status = 5;
        } else if ($order['order_status'] == 2) {
            $show_status = 4;
        }
        return $show_status;
    }
    /**
     *	处理发货单
     * @param array $data  查询数量
     */
    public function deliveryHandle($data,$store_id){
		$order = $this->getOrderInfo($data['order_id']);
		if($order['order_prom_type'] == 5) return false;
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
                M('order_goods')->where(['rec_id'=>$v['rec_id'],'store_id'=>$store_id])->save($res);//改变订单商品发货状态
				$is_delivery++;
			}
		}
		$updata['shipping_time'] = time();
		if($is_delivery == count($orderGoods)){
			$updata['shipping_status'] = 1;
		}else{
			$updata['shipping_status'] = 2;
		}
		 
		//商家发货, 发送短信给客户
		$res = checkEnableSendSms("5");
		if($res && $res['status'] ==1){
		    $user_id = $order['user_id'];
		    $users = M('users')->where('user_id', $user_id)->getField('user_id , nickname , mobile', true);
		    
		    if($users){
		        $nickname = $users[$user_id]['nickname'];
		        $sender = $users[$user_id]['mobile'];
		        $params = array('user_name'=>$nickname , 'consignee'=>$order['consignee']);
		        sendSms("5", $sender, $params);
		    }
		}
		 		
        //array('商家发货','尊敬的${user_name}用户，您的订单${order_sn}已发货，收货人${consignee}，请您及时查收'),        
        M('order')->where(['order_id'=>$data['order_id'],'store_id'=>$store_id])->save($updata);//改变订单状态
	    $seller_id = session('seller_id');
        $order['shipping_status']=$updata['shipping_status'];
		return $this->orderActionLog($order,'订单发货',$data['note'],$seller_id,1);//操作日志
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
        $select_year = getTabByOrderId($order_id);
    	$a = M('order'.$select_year)->where(array('order_id'=>$order_id,'store_id'=>$store_id))->update(['deleted'=>1]);
    	//$b = M('order_goods')->where(array('order_id'=>$order_id,'store_id'=>$store_id))->delete();
    	return $a;
    }

}