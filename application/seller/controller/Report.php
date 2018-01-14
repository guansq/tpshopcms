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
 * Date: 2016-06-21
 */

namespace app\seller\controller;

use think\Db;
use think\Page;
use app\seller\model\Order;

class Report extends Base{
	public $begin;
	public $end;
	public $store_id;
        public $select_year; // 选择哪张表查询
        public function _initialize(){
        parent::_initialize();
		if(I('start_time')){
                        $begin = I('start_time');
                        $end = I('end_time');
		}else{                    
                        $begin = date('Y-m-d', strtotime("-3 month"));//30天前
                        $end = date('Y-m-d', strtotime('+1 days'));                    			 
		}
                
                $this->select_year = getTabByTime($begin); // 表后缀
		$this->assign('start_time',$begin);
		$this->assign('end_time',$end);
		$this->begin = strtotime($begin);
		$this->end = strtotime($end)+86399;
		$this->store_id = STORE_ID;
		$this->assign('timegap',date('Y-m-d',$this->begin).' - '.date('Y-m-d',$this->end));
	}
	
	public function index(){
        $OrderMobile = new Order();
        $today=$OrderMobile->getTodayAmount($this->store_id);
		if($today['today_order'] == 0){
			$today['sign'] = round(0, 2);
		}else{
			$today['sign'] = round($today['today_amount']/$today['today_order'],2);
		}
		$this->assign('today',$today);
		$sql = "SELECT COUNT(*) as tnum,sum(goods_price-order_prom_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from  __PREFIX__order{$this->select_year} ";
		$sql .= " where add_time>$this->begin and add_time<$this->end and store_id=$this->store_id AND (pay_status=1 or pay_code='cod') and order_status in(1,2,4) group by gap order by gap desc";
		$res = Db::query($sql);//订单数,交易额
		
		foreach ($res as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$tnum += $val['tnum'];
			$tamount += $val['amount'];
		}

		for($i=$this->end;$i>$this->begin;$i=$i-24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);						
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;			
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		
		$this->assign('list',$list);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		return $this->fetch();
	}

	public function saleTop(){
		$sql = "select goods_name,goods_sn,sum(goods_num) as sale_num,sum(goods_num*goods_price) as sale_amount from __PREFIX__order_goods{$this->select_year} ";
		$sql .=" where is_send = 1 and store_id=$this->store_id group by goods_id order by sale_amount DESC limit 100";
		$res = Db::cache(true,3600)->query($sql);
		$this->assign('list',$res);
		return $this->fetch();
	}
	

	public function saleList(){
	
		$sql2 = "select count(*) as tnum from __PREFIX__order_goods{$this->select_year} as a left join __PREFIX__order{$this->select_year} as b on a.order_id=b.order_id ";
		$sql2 .= " where b.add_time>$this->begin and b.add_time<$this->end and a.store_id=$this->store_id and (b.pay_status=1 or b.pay_code='cod') and b.order_status in(1,2,4)";
		$total = Db::query($sql2);
		$count =  $total[0]['tnum'];
		$Page = new Page($count,20);
		$show = $Page->show();
                
		$sql = "select a.*,b.order_sn,b.shipping_name,b.pay_name,b.add_time from __PREFIX__order_goods{$this->select_year} as a left join __PREFIX__order{$this->select_year} as b on a.order_id=b.order_id ";
		$sql .= " where b.add_time>$this->begin and b.add_time<$this->end and a.store_id=$this->store_id and (b.pay_status=1 or b.pay_code='cod') and b.order_status in(1,2,4) order by add_time desc limit {$Page->firstRow},{$Page->listRows}";
		$res = Db::query($sql);
		$this->assign('list',$res);                
                
		$this->assign('page',$show);
		return $this->fetch();
	}
	
	//财务统计
	public function finance(){
		$sql = "SELECT sum(b.goods_num*b.member_goods_price) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		$sql .= "sum(a.coupon_price) as coupon_amount,FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__order{$this->select_year} a left join __PREFIX__order_goods{$this->select_year} b on a.order_id=b.order_id ";
		$sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.store_id=$this->store_id and a.pay_status=1 and a.shipping_status=1 and b.is_send=1 group by gap order by a.add_time";
		$res = Db::cache(true)->query($sql);//物流费,交易额,成本价

		foreach ($res as $val){
			$arr[$val['gap']] = $val['goods_amount'];
			$brr[$val['gap']] = $val['cost_price'];
			$crr[$val['gap']] = $val['shipping_amount'];
			$drr[$val['gap']] = $val['coupon_amount'];
		}
			
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$date = $day[] = date('Y-m-d',$i);
			$tmp_goods_amount = empty($arr[$date]) ? 0 : $arr[$date];
			$tmp_cost_amount = empty($brr[$date]) ? 0 : $brr[$date];
			$tmp_shipping_amount = empty($crr[$date]) ? 0 : $crr[$date];
			$tmp_coupon_amount = empty($drr[$date]) ? 0 : $drr[$date];
			
			$goods_arr[] = $tmp_goods_amount;
			$cost_arr[] = $tmp_cost_amount;
			$shipping_arr[] = $tmp_shipping_amount;
			$coupon_arr[] = $tmp_coupon_amount;
			$list[] = array('day'=>$date,'goods_amount'=>$tmp_goods_amount,'cost_amount'=>$tmp_cost_amount,
					'shipping_amount'=>$tmp_shipping_amount,'coupon_amount'=>$tmp_coupon_amount,'end'=>date('Y-m-d',$i+24*60*60));
		}
		$this->assign('list',$list);
		$result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		return $this->fetch();
	}
	
}