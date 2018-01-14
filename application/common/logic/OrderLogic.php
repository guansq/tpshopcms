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
 * Date: 2016-03-19
 */

namespace app\common\logic;

use think\Model;
/**
 * Class orderLogic
 * @package Common\Logic
 */
class OrderLogic extends Model
{

	//取消订单
	public function cancel_order($user_id,$order_id){
		$order = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
		//检查是否未支付订单 已支付联系客服处理退款
		if(empty($order))
			return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
		//检查是否未支付的订单
		if($order['pay_status'] > 0 || $order['order_status'] > 0)
			return array('status'=>-1,'msg'=>'支付状态或订单状态不允许','result'=>'');
		//获取记录表信息
		//$log = M('account_log')->where(array('order_id'=>$order_id))->find();
		//有余额支付的情况
		if($order['user_money'] > 0 || $order['integral'] > 0){
			accountLog($user_id, $order['user_money'], $order['integral'], "订单取消，退回{$order['user_money']}元,{$order['integral']}积分", 0, $order['order_id'], $order['sn']);
		}

		if($order['coupon_price'] >0){
			$res = array('use_time'=>0,'status'=>0,'order_id'=>0);
			M('coupon_list')->where(array('order_id'=>$order_id,'uid'=>$user_id))->save($res);
		}
		$row = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->save(array('order_status'=>3,'user_note'=>'用户取消订单'));
		$data['order_id'] = $order_id;
		$data['action_user'] = $user_id;
		$data['action_note'] = '您取消了订单';
		$data['order_status'] = 3;
		$data['pay_status'] = $order['pay_status'];
		$data['shipping_status'] = $order['shipping_status'];
		$data['log_time'] = time();
		$data['status_desc'] = '用户取消订单';
		M('order_action')->add($data);//订单操作记录

		if(!$row) return array('status'=>-1,'msg'=>'操作失败','result'=>'');
		return array('status'=>1,'msg'=>'操作成功','result'=>'');
	}

	public function check_dispute_order($order_id,$complain_id,$user_id){
		$res = array('flag'=>1,'data'=>'');
		$complain_log = M('complain')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
		if($complain_log){
			$res = array('flag'=>2,'msg'=>"该订单已经投诉过，请在用户中心投诉管理查看处理进度",'data'=>'');
		}else{
			$order = M('order')->where(array('order_id'=>$order_id))->find();
			if($order['pay_status'] == 0){
				$res = array('flag'=>2,'msg'=>"该订单并未付款，无法进行投诉交易服务。",'data'=>'');
			}elseif($complain_id == 1 && $order['shipping_status'] == 1){
				//配送投诉，如果卖家已经发货，所以不能提交
				$res = array('flag'=>2,'data'=>'','msg'=>"该纠纷类型暂无法提交，可能是您的订单已完成，或您已申请过同类型的纠纷单，建议您优先联系卖家客服处理。前往帮助中心了解<a href=''>纠纷发起规则</a>。");
			}elseif(in_array($complain_id,array(2,3,7,8,9,10))){
				//查看是否有申请退货退款，换货维修售后服务
				$return_goods = M('return_goods')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->select();
				$headhtml = '<div class="choosetyp6"><span style="width:20%">是否选择</span><span style="width:20%">售后服务单</span><span style="width:40%">对应商品</span><span style="width:20%">售后服务单状态</span></div>';
				$mismatch = $headhtml.'<div class="applyrestore"><p class="tit">如果没有满足条件的售后服务单</p><p class="mali">如果你遇到售后类型问题，可以先去申请返修退换货；倘若在售后过程中仍有问题，可再来申请交易纠纷</p><a href="'.U('Order/return_goods_index').'">申请返修退换货</a></div>';
				if(empty($return_goods)){
					$res = array('flag'=>2,'data'=> $mismatch,'msg'=>"该纠纷类型暂无法提交，可能是该订单下没有审核不通过的退货服务单，建议您选择其他纠纷类型，或联系卖家客服处理。前往帮助中心了解<a href=''>纠纷发起规则</a>。");
				}else{
					$state = C('REFUND_STATUS');
					$html = $headhtml;
					foreach ($return_goods as $k=>$val){
						$html .= '<div class="choosetyp6">';
						$goods_url = U('Goods/goodsInfo',array('id'=>$val['goods_id']));
						$return_url = U('Order/return_goods_info',array('id'=>$val['id']));
						$goods_name = M('order_goods')->where(array('order_id'=>$order_id,'goods_id'=>$val['goods_id']))->getField('goods_name');
						if($k == 0){
							$html .= '<span style="width:20%"><input type="radio" checked name="order_goods_id" value="'.$val['goods_id'].'">&nbsp;&nbsp;'.$val['id'].'</span>';
						}else{
							$html .= '<span style="width:20%"><input type="radio" name="order_goods_id" value="'.$val['goods_id'].'">&nbsp;&nbsp;'.$val['id'].'</span>';
						}
						$html .= '<span style="width:20%"><a href="'.$return_url.'" target="_blank"><img src="'.goods_thum_images($val['goods_id'],60,60).'" height="60" title=""></a></span>';
						$html .= '<span style="width:40%"><a class="shop_name_ir" href="'.$goods_url.'" target="_blank">'.$goods_name.'</a></span>';
						$html .= '<span style="width:20%">'.$state[$val['status']].'</span></div>';
					}
					
					$res = array('flag'=>1,'data'=>$html);//如果售后服务单有多个，那就让用户选择投诉
					if(count($return_goods) == 1){
						$res = array('flag'=>1,'data' => $html);
						$return_goods = $return_goods[0];
						if($return_goods['status'] == -2){
							$res = array('flag'=>2,'msg'=>"该服务单会员自己选择了取消，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。",'data'=>'');
						}
						if($return_goods['status'] == -1){
							$res = array('flag'=>1,'data'=> $html);
						}
						if($return_goods['status'] == 0){
							if(($return_goods['addtime']+48*3600)>time()){
								$res = array('flag'=>2,'msg'=>'该纠纷类型暂无法提交，您的该类型服务单还在等待卖家审核中');
							}
						}
						if($return_goods['status']>=1){
							if($complain_id == 10){
								if(empty($return_goods['delivery'])){
									$res = array('flag'=>2,'data'=>'','msg'=>"该纠纷类型暂无法提交，可能是您还未在服务单中上传物流信息，或服务单已处理完成，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。");
								}elseif(($return_goods['receivetime']+48*3600)>time()){
									$res = array('flag'=>2,'data'=>'','msg'=>"该服务单还在等待卖家处理，并未超过48小时，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。");
								}
							}
							if($complain_id == 9 && $return_goods['status']<4){
								$res = array('flag'=>2,'data'=>'','msg'=>"该服务单还在等待卖家处理，并未完成，建议您优先联系卖家客服解决。前往帮助中心了解纠纷发起规则。");
							}
						}
						//找不到退货退款服务单
						if($complain_id<4 && $return_goods['type']==1){
							$res = array('flag'=>2,'data'=>$mismatch,'msg'=>"该纠纷类型暂无法提交，可能是该订单下没有审核不通过的此类服务单，建议您选择其他类型，或联系卖家客服解决。前往帮助中心了解纠纷发起规则");
						}
						//找不到换货维修服务单
						if($complain_id>6 && $return_goods['type']==0){
							$res = array('flag'=>2,'data'=>$mismatch,'msg'=>"该纠纷类型暂无法提交，可能是该订单下没有审核不通过的此类服务单，建议您选择其他类型，或联系卖家客服解决。前往帮助中心了解纠纷发起规则");
						}
					}
				}
			}
		}
		return $res;
	}

	/**
	 * 添加一个订单
	 * @param $user_id|用户id
	 * @param $address_id|地址id
	 * @param $shipping_code|物流编号
	 * @param $invoice_title|发票
	 * @param array $coupon_id|优惠券id
	 * @param $car_price|各种价格
	 * @param $user_note|给卖家留言
	 * @return array
	 */
	public function addOrder($user_id,$address_id,$shipping_code,$invoice_title,$coupon_id = array(),$car_price,$user_note)
	{
		// 仿制灌水 1天只能下 50 单  // select * from `tp_order` where user_id = 1  and order_sn like '20151217%'
		//$order_count = M('Order')->where("user_id= $user_id and order_sn like '".date('Ymd')."%'")->count(); // 查找购物车商品总数量
		//if($order_count >= 50)
		//	return array('status'=>-9,'msg'=>'一天只能下50个订单','result'=>'');

		// 插入订单 order
		$address = M('UserAddress')->where("address_id",$address_id)->find();

		// 循环添加订单 多少个商家添加多少个订单
		foreach($car_price['store_order_amount'] as $k => $v)
		{
			$shipping = M('Plugin')->where("code",$shipping_code[$k])->cache(true,TPSHOP_CACHE_TIME)->find();
			$order_sn = $this->get_order_sn(); // 获取生成订单号
			!isset($master_order_sn) && ($master_order_sn = $this->get_order_sn()); // 主订单号
			// 用户使用余额
			$car_price['store_balance'][$k] = $car_price['store_balance'][$k] ? $car_price['store_balance'][$k] : 0;
			// 用户使用积分
			$car_price['store_point_count'][$k] = $car_price['store_point_count'][$k] ? $car_price['store_point_count'][$k] : 0;
			$data = array(
					'order_sn'         =>$order_sn, // 订单编号
					'master_order_sn'  =>$master_order_sn, // 主订单号
					'user_id'          =>$user_id, // 用户id
					'consignee'        =>$address['consignee'], // 收货人
					'province'         =>$address['province'],//'省份id',
					'city'             =>$address['city'],//'城市id',
					'district'         =>$address['district'],//'县',
					'twon'             =>$address['twon'],// '街道',
					'address'          =>$address['address'],//'详细地址',
					'mobile'           =>$address['mobile'],//'手机',
					'zipcode'          =>$address['zipcode'],//'邮编',
					'email'            =>$address['email'],//'邮箱',
					'shipping_code'    =>$shipping['code'],//'物流编号',
					'shipping_name'    =>$shipping['name'], //'物流名称',
					'invoice_title'    =>$invoice_title, //'发票抬头',
					'user_note'        =>$user_note[$k], //'给卖家留言',
					'goods_price'      =>$car_price['store_goods_price'][$k],//每个店铺的商品价格',
					'shipping_price'   =>$car_price['store_shipping_price'][$k],//'物流价格',
					'user_money'       =>$car_price['store_balance'][$k], // 当前订单使用的余额数量
					'coupon_price'     =>$car_price['store_coupon_price'][$k],//'使用优惠券',
					'integral'         =>$car_price['store_point_count'][$k], // 使用的积分数量
					'integral_money'   =>($car_price['store_point_count'][$k] / tpCache('shopping.point_rate')),//'使用积分抵多少钱',
					'total_amount'     =>($car_price['store_goods_price'][$k] + $car_price['store_shipping_price'][$k]),// 订单总额 = 商品总价 + 物流费
					'order_amount'     =>$car_price['store_order_amount'][$k],//'应付款金额',
					'add_time'         =>time(), // 下单时间
					'order_prom_id'    =>$car_price['store_order_prom_id'][$k],//'订单优惠活动id',
					'order_prom_amount'=>$car_price['store_order_prom_amount'][$k],//'订单优惠活动优惠了多少钱',
					'store_id'         =>$k,  // 店铺id
			);

			$order_id = M("Order")->insertGetId($data);

			// 记录订单操作日志
			$action_info = array(
					'order_id'        =>$order_id,
					'action_user'     =>$user_id,
					'user_type'       =>2,
					'action_note'     => '您提交了订单，请等待系统确认',
					'status_desc'     =>'提交订单', //''
					'log_time'        =>time(),
			);
			M('order_action')->add($action_info);

			// 1插入order_goods 表
			$cartList = M('Cart')->where("store_id = $k and user_id = $user_id and selected = 1")->select();
			foreach($cartList as $key => $val)
			{
				$goods = M('goods')->where("goods_id = {$val['goods_id']} ")->cache(true,TPSHOP_CACHE_TIME)->find();
				$data2['order_id']           = $order_id; // 订单id
				$data2['goods_id']           = $val['goods_id']; // 商品id
				$data2['goods_name']         = $val['goods_name']; // 商品名称
				$data2['goods_sn']           = $val['goods_sn']; // 商品货号
				$data2['goods_num']          = $val['goods_num']; // 购买数量
				$data2['market_price']       = $val['market_price']; // 市场价
				$data2['goods_price']        = $val['goods_price']; // 商品价
				$data2['spec_key']           = $val['spec_key']; // 商品规格
				$data2['spec_key_name']      = $val['spec_key_name']; // 商品规格名称
				$data2['sku']                = $val['sku']; // 商品条码
				$data2['member_goods_price'] = $val['member_goods_price']; // 会员折扣价
				$data2['cost_price']         = $goods['cost_price']; // 成本价
				$data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分
				$data2['prom_type']          = $val['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
				$data2['prom_id']            = $val['prom_id']; // 活动id
				$data2['store_id']           = $val['store_id']; // 店铺id
				$data2['distribut']          = $goods['distribut']; // 三级分销金额
				$data2['commission']         = M('goods_category')->where("id = {$goods['cat_id3']}")->cache(true,TPSHOP_CACHE_TIME)->getField('commission'); // 商品抽成比例
				$order_goods_id              = M("OrderGoods")->insert($data2);
				// 扣除商品库存  扣除库存移到 付完款后扣除
				//M('Goods')->where("goods_id = ".$val['goods_id'])->setDec('store_count',$val['goods_num']); // 商品减少库存
			}

			// 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
			if($data['order_amount'] == 0)
			{
				update_pay_status($order_sn, 1); // 这里刚刚下的订单必须从主库里面去查
			}

			if(!empty($coupon_id[$k])){
				// 2修改优惠券状态
				$data3['uid'] = $user_id;
				$data3['order_id'] = $order_id;
				$data3['use_time'] = time();
				$data3['status'] = 1;
				M('CouponList')->where("id",$coupon_id[$k])->save($data3);
				$cid = M('CouponList')->where("id",$coupon_id[$k])->getField('cid');
				M('Coupon')->where("id",$cid)->setInc('use_num'); // 优惠券的使用数量加一
			}

			// 3 扣除积分 扣除余额
			if($car_price['store_point_count'][$k] != 0)
				M('Users')->where("user_id = $user_id")->setDec('pay_points',$car_price['store_point_count'][$k]); // 用户的积分减
			if($car_price['store_balance'][$k] != 0)
				M('Users')->where("user_id = $user_id")->setDec('user_money',$car_price['store_balance'][$k]); // 用户的余额减

			// 4 清空购物车
			M('Cart')->where("store_id = $k and user_id = $user_id and selected = 1")->delete();

			// 5 记录log 日志
			$data4['user_id'] = $user_id;
			$data4['user_money'] = -$car_price['store_balance'][$k];
			$data4['pay_points'] = -$car_price['store_point_count'][$k];
			$data4['change_time'] = time();
			$data4['desc'] = '下单消费';
			$data4['order_sn'] = $order_sn;
			$data4['order_id'] = $order_id;
			// 如果使用了积分或者余额才记录
			($data4['user_money'] || $data4['pay_points']) && M("AccountLog")->add($data4);

			//分销开关全局
			$order = M("Order")->where("order_id = $order_id")->find();
			if(file_exists(APP_PATH.'common/logic/DistributLogic.php'))
			{
				$distributLogic = new \app\common\logic\DistributLogic();
				$distributLogic->rebateLog($order); // 生成分成记录
				if($data['order_amount'] == 0)
				update_pay_status($order_sn, 1); // 这里刚刚下的订单必须从主库里面去查
			}
			// 如果有微信公众号 则推送一条消息到微信
			$user = M('users')->where("user_id = $user_id")->find();
			if($user['oauth']== 'weixin')
			{
				$wx_user = M('wx_user')->find();
				$jssdk = new JssdkLogic($wx_user['appid'],$wx_user['appsecret']);
				$wx_content = "你刚刚下了一笔订单:{$order['order_sn']} 尽快支付,过期失效!";
				$jssdk->push_msg($user['openid'],$wx_content);
			}

			//用户下单, 发送短信给商家
			$res = checkEnableSendSms("3");

			if($res && $res['status'] ==1){
				$store = M('store')->where("store_id = ".$k)->find();
				$sender = (!empty($store) && !empty($store['service_phone'])) ? $store['service_phone'] : false;
				$params = array('consignee'=>$order['consignee'] , 'mobile' => $order['mobile']);
				$resp = sendSms("3", $sender, $params);
			}
		}

		return array('status'=>1,'msg'=>'提交订单成功','result'=>$master_order_sn); // 返回新增的订单id
	}

	/**
	 * 获取订单 order_sn
	 * @return string
	 */
	public function get_order_sn()
	{
		$order_sn = null;
		// 保证不会有重复订单号存在
		while(true){
			$order_sn = date('YmdHis').rand(1000,9999); // 订单编号			
			$order_sn_count = M('order')->where("order_sn = '$order_sn' or master_order_sn = '$order_sn'")->count();
			if($order_sn_count == 0)
				break;
		}
		return $order_sn;
	}

    /**
     * 获取退货列表
     * @param type $keywords
     * @param type $addtime
     * @param type $status
     * @return type
     */
    public function getReturnGoodsList($keywords, $addtime, $status,$user_id)
	{
		if($keywords){
            $where['r.order_sn|o.goods_name'] = array('like',"%$keywords%");
    	}
    	if($status === '0' || !empty($status)){
            $where['r.status'] = $status;
    	}
    	if($addtime == 1){
            $where['r.addtime'] = array('gt',(time()-90*24*3600));
    	}
    	if($addtime == 2){
            $where['r.addtime'] = array('lt',(time()-90*24*3600));
    	}
    	$where['r.user_id'] = $user_id;
    	$query = M('return_goods')->alias('r')->field('r.*,o.goods_name')
                ->join('__ORDER_GOODS__ o', 'r.rec_id = o.rec_id AND o.deleted = 0')->where($where);
        $query2 = clone $query;
        $count = $query->count();
    	$page = new \think\Page($count,10);
    	$list = $query2->order("id desc")->limit($page->firstRow, $page->listRows)->select();
    	$goods_id_arr = get_arr_column($list, 'goods_id');
    	if(!empty($goods_id_arr)) {
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        }
        
        return [
            'goodsList' => $goodsList,
            'return_list' => $list,
            'page' => $page->show()
        ];
	}

    /**
     * 获取可申请退换货订单商品
     * @param $sale_t
     * @param $keywords
     * @param $user_id
     * @return array
     */
    public function getReturnGoodsIndex($sale_t, $keywords, $user_id)
    {
        if($keywords){
            $condition['order_sn'] = $keywords;
        }
        if($sale_t == 1){
            //三个月内
            $condition['add_time'] = array('gt' , 'DATE_SUB(CURDATE(), INTERVAL 3 MONTH)');
        }else if($sale_t == 2){
            //三个月前
            $condition['add_time'] = array('lt' , 'DATE_SUB(CURDATE(), INTERVAL 3 MONTH)');
        }
    	$condition['user_id'] = $user_id;
    	$condition['pay_status'] = 1;
    	$condition['order_status'] = array('neq',3);
    	$condition['confirm_time'] = array('gt',0);
    	$condition['shipping_status'] = 1;
    	$condition['deleted'] = 0;

    	$count = M('order')->where($condition)->count();
    	$Page  = new \think\Page($count,10);
    	$show = $Page->show();
    	$order_list = M('order')->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	foreach ($order_list as $k=>$v) {
            $data = M('order_goods')->where(['order_id'=>$v['order_id'],'is_send'=>['lt',2]])->select();  //订单没完成售后的商品
            if(!empty($data)){
                foreach ($data as $gk => $gv) {
                    $return_goods = M('return_goods')->where(['rec_id' => $gv['rec_id']])->count();  //商品有售后的
                    if ($return_goods > 0) {
                        unset($gv); //去除这个商品
                    } else {
                        $order_list[$k]['goods_list'][] = $gv;
                    }
                }
            }
            if(empty($data) || empty($order_list[$k]['goods_list'])){
                unset($order_list[$k]);  //除去没有可申请的订单
            }
    	}
        $store_id_list = get_arr_column($order_list, 'store_id');
        if(!empty($store_id_list))
            $store_list = M('store')->where("store_id in (".  implode(',', $store_id_list).")")->getField('store_id,store_name,store_qq');
        return [
            'order_list' => $order_list,
            'store_list'=>$store_list,
            'page' => $show
        ];
    }

    /**
     * 上传退换货图片，兼容小程序
     * @return array
     */
    public function uploadReturnGoodsImg()
    {
        $return_imgs = '';
        if ($_FILES['return_imgs']['tmp_name']) {
			$files = request()->file("return_imgs");
            if (is_object($files)) {
                $files = [$files]; //可能是一张图片，小程序情况
            }
			$validate = ['size'=>1024 * 1024 * 3,'ext'=>'jpg,png,gif,jpeg'];
			$dir = 'public/upload/return_goods/';
			if (!($_exists = file_exists($dir))){
				$isMk = mkdir($dir);
			}
			$parentDir = date('Ymd');
			foreach($files as $key => $file){
				$info = $file->rule($parentDir)->validate($validate)->move($dir, true);
				if($info){
					$filename = $info->getFilename();
					$new_name = '/'.$dir.$parentDir.'/'.$filename;
					$return_imgs[]= $new_name;
				}else{
                    return ['status' => -1, 'msg' => $file->getError()];//上传错误提示错误信息
				}
			}
			if (!empty($return_imgs)) {
				$return_imgs = implode(',', $return_imgs);// 上传的图片文件
			}
		}
        
        return ['status' => 1, 'msg' => '操作成功', 'result' => $return_imgs];
    }
    
    /**
     * 申请售后
     * @param $rec_id
     * @param $order
     */
    public function addReturnGoods($rec_id,$order)
    {
        $data = I('post.');
        $confirm_time_config = tpCache('shopping.auto_service_date');
        $confirm_time = $confirm_time_config * 24 * 60 * 60;
        if ((time() - $order['confirm_time']) > $confirm_time && !empty($order['confirm_time'])) {
            return ['result'=>-1,'msg'=>'已经超过' . $confirm_time_config . "天内退货时间"];
        }
        
        $img = $this->uploadReturnGoodsImg();
        if ($img['status'] !== 1) {
            return $img;
        }
        $data['imgs'] = $img['result'] ?: ($data['imgs'] ?: ''); //兼容小程序，多传imgs

        $data['addtime'] = time();
        $data['user_id'] = $order['user_id'];
        $data['store_id'] = $order['store_id'];
        $order_goods = M('order_goods')->where(array('rec_id'=>$rec_id))->find();
        if($data['type'] < 2){
        	//退款申请，若该商品有赠送积分或优惠券，在平台操作退款时需要追回
            $rate = round($order_goods['member_goods_price']*$data['goods_num']/$order['goods_price'],2);
            if($order['order_amount']>0){
                $data['refund_money'] = $rate*$order['order_amount'];//退款金额
                $data['refund_deposit'] = $rate*$order['user_money'];//该退余额支付部分
                $data['refund_integral'] = floor($rate*$order['integral']);//该退积分支付
            }else{
                $data['refund_deposit'] = $rate*$order['user_money'];//该退余额支付部分
                $data['refund_integral'] = floor($rate*$order['integral']);//该退积分支付
            }
        }
        if(!empty($data['id'])){
        	$result = M('return_goods')->where(array('id'=>$data['id']))->save($data);
        }else{
        	$result = M('return_goods')->add($data);
        }
        
        if($result){
            return ['status'=>1,'msg'=>'申请成功'];
        }
        return ['status'=>-1,'msg'=>'申请失败'];
    }
    
    /**
     * 删除订单
     * @param type $order_id
     * @return type
     */
    public function delOrder($order_id)
    {
        $validate = validate('order');
        if (!$validate->scene('del')->check(['order_id' => $order_id])) {
            return ['status' => 0, 'msg' => $validate->getError()];
        }
     
        $row = M('order')->where('order_id' ,$order_id)->update(['deleted'=>1]);
        if (!$row) {
            return ['status'=>-1,'msg'=>'删除失败'];
        }
        
        return ['status'=>1,'msg'=>'删除成功'];
    }

    /**
     * 记录取消订单
     */
    public function recordRefundOrder($user_id, $order_id, $user_note, $consignee, $mobile)
    {
        $order = M('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在'];
        }
        if($order['shipping_status'] == 1){
        	return ['status' => -1, 'msg' => '该订单已经发货，请申请售后'];
        }
        $order_return_num = M('return_goods')->where(['order_id' => $order_id, 'user_id' => $user_id,'status'=>['neq',5]])->count();
        if($order_return_num > 0){
            return ['status' => -1, 'msg' => '该订单中有商品正在申请售后'];
        }
        $order_info = [
            'user_note' => $user_note,
            'consignee' => $consignee,
            'mobile'    => $mobile,
            'order_status'=> 3,
        ];

        $result = M('order')->where(['order_id' => $order_id])->update($order_info);
        if (!$result) {
            return ['status' => 0, 'msg' => '操作失败'];
        }
        if($order['order_prom_type']==5){  //虚拟订单要处理一下兑换码
            M('vr_order_code')->where(['order_id' => $order_id])->update(['refund_lock'=>1]);
        }
        $data['order_id'] = $order_id;
		$data['action_user'] = $user_id;
		$data['action_note'] = $user_note;
		$data['order_status'] = 3;
		$data['pay_status'] = $order['pay_status'];
		$data['shipping_status'] = $order['shipping_status'];
		$data['log_time'] = time();
		$data['status_desc'] = '用户取消已付款订单';
		M('order_action')->add($data);//订单操作记录
        return ['status' => 1, 'msg' => '提交成功'];
    }

	/**
	 * 	生成兑换码
	 * 长度 =3位 + 4位 + 2位 + 3位  + 1位 + 5位随机  = 18位
	 * @param $order
	 * @return mixed
	 */
	function make_virtual_code($order){
		$order_goods = M('order_goods')->where(array('order_id'=>$order['order_id']))->find();
		$goods = M('goods')->where(array('goods_id'=>$order_goods['goods_id']))->find();
		M('order')->where(array('order_id'=>$order['order_id']))->save(array('order_status'=>1,'shipping_time'=>time()));
		$perfix = mt_rand(100,999);
		$perfix .= sprintf('%04d', (int) $goods['store_id'] * $order['user_id'] % 10000)
				. sprintf('%02d', (int) $order['user_id'] % 100).sprintf('%03d', (float) microtime() * 1000);

		for ($i = 0; $i < $order_goods['goods_num']; $i++) {
			$order_code[$i]['order_id'] = $order['order_id'];
			$order_code[$i]['store_id'] = $goods['store_id'];
			$order_code[$i]['user_id'] = $order['user_id'];
			$order_code[$i]['vr_code'] = $perfix. sprintf('%02d', (int) $i % 100) . rand(5,1);
			$order_code[$i]['pay_price'] = $goods['shop_price'];
			$order_code[$i]['vr_indate'] = $goods['virtual_indate'];
			$order_code[$i]['vr_invalid_refund'] = $goods['virtual_refund'];
		}
		return M('vr_order_code')->insertAll($order_code);
	}


    /**
     * 自动取消订单
     * @param array|$order   订单
     * @param $set_time  自动取消时间/天 默认1天
     */
    public function  abolishOrder($order,$set_time=1){
        $abolishtime = time()-($set_time*60*60*24);
        if($order['add_time']<$abolishtime) {
            $result = $this->cancel_order($order['user_id'],$order['order_id']);
            return $result;
        }
    }

	/**
	 * 转换订单的优惠券数据
	 * @param $storeCartList|购物车商品
	 * @param $userCouponList|用户优惠券列表
	 * @return mixedable
	 */
	public function getCouponOrderList($order, $userCouponList)
	{
		$userCouponArray = collection($userCouponList)->toArray();
		$couponNewList = [];
		foreach ($userCouponArray as $couponKey => $couponItem) {
				//过滤掉购物车没有的店铺优惠券
				if ($userCouponArray[$couponKey]['store_id'] == $order['store_id']) {
					if ($userCouponArray[$couponKey]['coupon']['use_type'] == 0) {
						//是否满足在该店铺购买的价格
						if ($order['order_amount'] >= $userCouponArray[$couponKey]['coupon']['condition']) {
							$userCouponArray[$couponKey]['coupon']['able'] = 1;
						} else {
							$userCouponArray[$couponKey]['coupon']['able'] = 0;
						}
					} elseif ($userCouponArray[$couponKey]['coupon']['use_type'] == 1) {
						//是否满足购买指定商品的价格
						if ($order['order_amount']  >= $userCouponArray[$couponKey]['coupon']['condition']) {
							$userCouponArray[$couponKey]['coupon']['able'] = 1;
						} else {
							$userCouponArray[$couponKey]['coupon']['able'] = 0;
						}
					} elseif ($userCouponArray[$couponKey]['coupon']['use_type'] == 2) {
						//是否满足购买指定商品分类的价格
						$pointGoodsCatPrice = 0;//指定商品分类的购买总价
						$CouponGoodsCatId = get_arr_column($userCouponArray[$couponKey]['coupon']['goods_coupon'], 'goods_category_id');
						foreach ($storeCartList[$storeCartKey]['cartList'] as $cartKey => $cartItem) {
							if (in_array($storeCartList[$storeCartKey]['cartList'][$cartKey]['goods']['cat_id3'], $CouponGoodsCatId)) {
								$pointGoodsCatPrice += $storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_price'] * $storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_num'];
							}
						}
						if ($pointGoodsCatPrice >= $userCouponArray[$couponKey]['coupon']['condition']) {
							$userCouponArray[$couponKey]['coupon']['able'] = 1;
						} else {
							$userCouponArray[$couponKey]['coupon']['able'] = 0;
						}
					} else {
						$userCouponList[$couponKey]['coupon']['able'] = 1;
					}
					$couponNewList[] = $userCouponArray[$couponKey];
				}
		}
		return $couponNewList;
	}
}