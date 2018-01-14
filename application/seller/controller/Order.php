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
namespace app\seller\controller;

use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use app\seller\logic\OrderLogic;
use app\seller\logic\GoodsLogic;
use think\AjaxPage;
use think\Db;
use think\Page;

class Order extends Base
{
    public $order_status;
    public $shipping_status;
    public $pay_status;

    /*
     * 初始化操作
     */
    public function _initialize()
    {
        parent::_initialize();
        C('TOKEN_ON', false); // 关闭表单令牌验证
        // 订单 支付 发货状态
        $this->order_status = C('ORDER_STATUS');
        $this->pay_status = C('PAY_STATUS');
        $this->shipping_status = C('SHIPPING_STATUS');
        $this->assign('order_status', $this->order_status);
        $this->assign('pay_status', $this->pay_status);
        $this->assign('shipping_status', $this->shipping_status);
    }

    /*
     *订单首页
     */
    public function index()
    {
        $begin = date('Y-m-d', strtotime("-3 month"));//30天前
        $end = date('Y-m-d', strtotime('+1 days'));
        $this->assign('timegap', $begin . '-' . $end);
        $this->assign('begin', date('Y-m-d', strtotime("-3 month")+86400));
        $this->assign('end', date('Y-m-d', strtotime('+1 days')));
        return $this->fetch();
    }

    /*
     *Ajax首页
     */
    public function ajaxindex()
    {
        $select_year = getTabByTime(I('add_time_begin')); // 表后缀
        $begin = strtotime(I('add_time_begin'));
        $end   = strtotime(I('add_time_end'));
        
        // 搜索条件 STORE_ID
        $condition = array('store_id' => STORE_ID); // 商家id
        I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
        if ($begin && $end) {
            $condition['add_time'] = array('between', "$begin,$end");
        }
        I('order_sn') ? $condition['order_sn'] = trim(I('order_sn')) : false;
        I('order_status') != '' ? $condition['order_status'] = I('order_status/d') : false;
        I('pay_status') != '' ? $condition['pay_status'] = I('pay_status/d') : false;
        I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status/d') : false;
        I('order_statis_id/d') != '' ? $condition['order_statis_id'] = I('order_statis_id/d') : false; // 结算统计的订单

        $sort_order = I('order_by', 'DESC') . ' ' . I('sort');
        $condition['order_prom_type'] = array('lt',5);
        $count = M('order'.$select_year)->where($condition)->count();
        $Page = new AjaxPage($count, 20);
        $show = $Page->show();
        //获取订单列表
        //$orderList = $orderLogic->getOrderList($condition, $sort_order, $Page->firstRow, $Page->listRows);
        $orderList = M('order'.$select_year)->where($condition)->limit("{$Page->firstRow},{$Page->listRows}")->order($sort_order)->select();
        //获取每个订单的商品列表
        $order_id_arr = get_arr_column($orderList, 'order_id');
        if (!empty($order_id_arr)) ;
        if ($order_id_arr) {
            $goods_list = M('order_goods'.$select_year)->where("order_id", "in", implode(',', $order_id_arr))->select();
            $goods_arr = array();
            foreach ($goods_list as $v) {
                $goods_arr[$v['order_id']][] = $v;
            }
            $this->assign('goodsArr', $goods_arr);
        }
        $this->assign('orderList', $orderList);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('pager', $Page);
        return $this->fetch();
    }

    
    public function virtual_list(){
     header("Content-type: text/html; charset=utf-8");
	 exit('请联系TPshop官网客服购买高级版支持此功能'); 
    }
    
    public function virtual_info(){
    	$order_id = I('order_id/d');
        // 获取操作表
        $select_year = getTabByOrderId($order_id);        
    	$order = M('order'.$select_year)->where(array('order_id'=>$order_id,'store_id'=>STORE_ID))->find();
    	if($order['pay_status'] == 1){
    		$vrorder = M('vr_order_code')->where(array('order_id'=>$order_id))->find();
            if($vrorder['vr_indate'] < time() && $vrorder['vr_state']==0){  //看看有没有过期
                M('vr_order_code')->where(array('order_id'=>$order_id))->update(['vr_state'=>2]);
                $value['vr_state'] = 2;
            }
    		$this->assign('vrorder',$vrorder);
    	}
    	$order_goods = M('order_goods'.$select_year)->where(array('order_id'=>$order_id))->find();
    	$order_goods['virtual_indate'] = M('goods')->where(array('goods_id'=>$order_goods['goods_id']))->getField('virtual_indate');
    	$this->assign('order',$order);
    	$this->assign('order_goods',$order_goods);
    	return $this->fetch();
    }
    
    public function virtual_cancel(){
    	$order_id = I('order_id/d');
    	if(IS_POST){
    		$admin_note = I('admin_note');
    		$order = M('order')->where(array('order_id'=>$order_id,'store_id'=>STORE_ID))->find();
    		if($order){
    			$r = M('order')->where(array('order_id'=>$order_id,'store_id'=>STORE_ID))->save(array('order_status'=>3,'admin_note'=>$admin_note));
    			if($r){
    				$orderLogic = new OrderLogic();
                    $seller_id = session('seller_id');
                    $orderLogic->orderActionLog($order, '取消订单', $admin_note, $seller_id, 1);
    				$this->ajaxReturn(array('status'=>1,'msg'=>'操作成功'));
    			}else{
    				$this->ajaxReturn(array('status'=>-1,'msg'=>'操作失败'));
    			}
    		}else{
    			$this->ajaxReturn(array('status'=>-1,'msg'=>'订单不存在'));
    		}
    	}
    	$order = M('order')->where(array('order_id'=>$order_id,'store_id'=>STORE_ID))->find();
    	$this->assign('order',$order);
    	return $this->fetch();
    }
    
    public function verify_code(){
    	if(IS_POST){
    		$vr_code = I('vr_code');
    		if (!preg_match('/^[a-zA-Z0-9]{15,18}$/',$vr_code)) {
    			$this->ajaxReturn(array('error' => '兑换码格式错误，请重新输入'));
    		}
    		$vr_code_info = M('vr_order_code')->where(array('vr_code'=>$vr_code))->find();
    		if(empty($vr_code_info) || $vr_code_info['store_id'] != STORE_ID){
    			$this->ajaxReturn(array('error' => '该兑换码不存在'));
    		}
    		if ($vr_code_info['vr_state'] == '1') {
    			$this->ajaxReturn(array('error' => '该兑换码已被使用'));
    		}
    		if ($vr_code_info['vr_indate'] < time()) {
    			$this->ajaxReturn(array('error'=>'该兑换码已过期，使用截止日期为： '.date('Y-m-d H:i:s',$vr_code_info['vr_indate'])));
    		}
    		if ($vr_code_info['refund_lock'] > 0) {//退款锁定状态:0为正常,1为锁定(待审核),2为同意
    			$this->ajaxReturn(array('error' => '该兑换码已申请退款，不能使用'));
    		}
    		$update['vr_state'] = 1;
    		$update['vr_usetime'] = time();
    		M('vr_order_code')->where(array('vr_code'=>$vr_code))->save($update);
    		//检查订单是否完成
    		$condition = array();
    		$condition['vr_state'] = 0;
    		$condition['refund_lock'] = array('in',array(0,1));
    		$condition['order_id'] = $vr_code_info['order_id'];
    		$condition['vr_indate'] = array('gt',time());
    		$vr_order = M('vr_order_code')->where($condition)->select();
    		if(empty($vr_order)){
    			$data['order_status'] = 2;  //此处不能直接为4，不然前台不能评论
    			$data['confirm_time'] = time();
    			$order = M('order')->where(array('order_id'=>$vr_code_info['order_id']))->field('order_sn,user_note')->find();
    			M('order')->where(array('order_id'=>$vr_code_info['order_id']))->save($data);
    		}
    		$order_goods = M('order_goods')->where(array('order_id'=>$vr_code_info['order_id']))->find();
    		if($order_goods){
    			$content = '<tr><td class="bdl"></td><td>'.$vr_code.'</td><td class="w70"><div class="ncsc-goods-thumb">';
    			$content .=	'<a target="_blank" href="'.U('Home/Goods/goodsInfo',array('id'=>$order_goods['goods_id'])).'">';
    			$content .=	'<img src="'.goods_thum_images($order_goods['goods_id'],240,240).'"></a></div></td>';
    			$content .=	'<td class="tl"><a href="'.U('Home/Goods/goodsInfo',array('id'=>$order_goods['goods_id'])).'" target="_blank">'.$order_goods['goods_name'].'</a></td>';
    			$content .=	'<td><a target="_blank" href="'.U('Order/virtual_info',array('order_id'=>$vr_code_info['order_id'])).'"></a>'.$order['order_sn'].'</td>';
    			$content .=	'<td class="bdr">'.$order['user_note'].'</td></tr>';
    			$this->ajaxReturn(array('status' =>1,'content'=>$content));
    		}else{
    			$this->ajaxReturn(array('error' => '虚拟订单商品不存在'));
    		}
    	}
    	 return $this->fetch();
    }

    /*
     * ajax 发货订单列表
    */
    public function ajaxdelivery()
    {
        $begin = strtotime(I('add_time_begin'));
        $end = strtotime(I('add_time_end'));
        $select_year = getTabByTime(I('add_time_begin')); // 表后缀
        $condition = array('store_id' => STORE_ID ,'deleted' =>0);
        if ($begin && $end) {
            $condition['add_time'] = array('between', "$begin,$end");
        }
        I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
        I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
        $shipping_status = I('shipping_status');
        $condition['shipping_status'] = empty($shipping_status) ? 0 : $shipping_status;
        $condition['order_status'] = array('in', '1,2,4');
        $condition['order_prom_type'] = array('lt',5);
        $count = M('order'.$select_year)->where($condition)->count();
        $Page = new AjaxPage($count, 10);
        $show = $Page->show();
        $orderList = M('order'.$select_year)->where($condition)->limit($Page->firstRow . ',' . $Page->listRows)->order('add_time DESC')->select();
        //@new 新UI 需要 {
        //获取每个订单的商品列表
        $order_id_arr = get_arr_column($orderList, 'order_id');
        //查询所有订单的所有商品
        if (count($order_id_arr) > 0) {
            $goods_list = M('order_goods'.$select_year)->where("is_send<2 and order_id", "in", implode(',', $order_id_arr))->select();
            $goods_arr = array();
            foreach ($goods_list as $v) {
                $goods_arr[$v['order_id']][] = $v;
            }
            $this->assign('goodsArr', $goods_arr);
        }
        //查询所有订单的用户昵称
        $user_id_arr = get_arr_column($orderList, 'user_id');
        if (count($user_id_arr) > 0) {
            $users = M('users')->where("user_id", "in", implode(',', $user_id_arr))->getField("user_id,nickname");
            $this->assign('users', $users);
        }
        //}
        $this->assign('orderList', $orderList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail($order_id)
    {
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if(!$order){
        	$this->error('该订单不存在或没有权利查看', U('Seller/Order/index'));
        }
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $button = $orderLogic->getOrderButton($order);
        // 获取操作记录
        $select_year = getTabByOrderId($order_id);
        $action_log = M('order_action'.$select_year)->alias('oa')
            ->field('oa.*,s.seller_name')
            ->join('seller s','s.seller_id=oa.action_user')
            ->where(['oa.order_id' => $order_id])
            ->order('oa.log_time desc')
            ->select();
        $this->assign('order', $order);
        $this->assign('action_log', $action_log);
        $this->assign('orderGoods', $orderGoods);

        /*
         定义一个变量, 用于前端UI显示订单5个状态进度. 1: 提交订单;2:订单支付; 3: 商家发货; 4: 确认收货; 5: 订单完成
         此判断依据根据来源于 Common的config.phpz中的"订单用户端显示状态" @{

       '1'=>' AND pay_status = 0 AND order_status = 0 AND pay_code !="cod" ', //订单查询状态 待支付
        '2'=>' AND (pay_status=1 OR pay_code="cod") AND shipping_status !=1 AND order_status in(0,1) ', //订单查询状态 待发货
        '3'=>' AND shipping_status=1 AND order_status = 1 ', //订单查询状态 待收货
        '4'=> ' AND order_status=2 ', // 待评价 已收货     //'FINISHED'=>'  AND order_status=1 ', //订单查询状态 已完成
        '5'=> ' AND order_status = 4 ', // 已完成 */

        $show_status = $orderLogic->getShowStatus($order);
        if($order['is_comment'] == 1){
            $comment_time = M('comment')->where('order_id' , $order['order_id'])->order('comment_id desc')->value('add_time');
            $this->assign('comment_time', $comment_time); //查询评论时间
        }
        $this->assign('show_status', $show_status);
        $this->assign('button', $button);
        return $this->fetch();
    }

    /**
     * 订单编辑
     * @param int $id 订单id
     */
    public function edit_order()
    {
        $order_id = I('order_id/d');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if ($order['store_id'] != STORE_ID) {
            $this->error('该订单不存在', U('Seller/Order/index'));
        }
        if ($order['shipping_status'] != 0) {
            $this->error('已发货订单不允许编辑');
            exit;
        }

        $orderGoods = $orderLogic->getOrderGoods($order_id);

        if (IS_POST) {
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机           
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注
            $order['admin_note'] = I('admin_note'); //                  
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status' => 1, 'type' => 'shipping', 'code' => I('shipping')))->getField('name');
            $order['pay_code'] = I('payment');// 支付方式            
            $order['pay_name'] = M('plugin')->where(array('status' => 1, 'type' => 'payment', 'code' => I('payment')))->getField('name');
            $goods_id_arr = I("goods_id/a");
            $new_goods = $old_goods_arr = array();
            //################################订单添加商品
            if ($goods_id_arr) {
                $new_goods = $orderLogic->get_spec_goods($goods_id_arr);
                foreach ($new_goods as $key => $val) {
                    $val['order_id'] = $order_id;
                    $val['store_id'] = STORE_ID;
                    $rec_id = M('order_goods')->add($val);//订单添加商品
                    if (!$rec_id)
                        $this->error('添加失败');
                }
            }

            //################################订单修改删除商品
            $old_goods = I('old_goods/a');
            foreach ($orderGoods as $val) {
                if (empty($old_goods[$val['rec_id']])) {
                    M('order_goods')->where("rec_id", $val['rec_id'])->delete();//删除商品
                } else {
                    //修改商品数量
                    if ($old_goods[$val['rec_id']] != $val['goods_num']) {
                        $val['goods_num'] = $old_goods[$val['rec_id']];
                        M('order_goods')->where("rec_id", $val['rec_id'])->save(array('goods_num' => $val['goods_num']));
                    }
                    $old_goods_arr[] = $val;
                }
            }

            $goodsArr = array_merge($old_goods_arr, $new_goods);
            $result = calculate_price($order['user_id'], $goodsArr, [STORE_ID => $order['shipping_code']], $order['province'], $order['city'], $order['district'], 0, 0);
            if ($result['status'] < 0) {
                $this->error($result['msg']);
            }

            //################################修改订单费用
            $order['goods_price'] = $result['result']['goods_price']; // 商品总价
            $order['shipping_price'] = $result['result']['shipping_price'];//物流费
            $order['order_amount'] = $result['result']['order_amount']; // 应付金额
            $order['total_amount'] = $result['result']['total_amount']; // 订单总价
            $o = M('order')->where(['order_id' => $order_id, 'store_id' => STORE_ID])->save($order);

            $seller_id = session('seller_id');
            $l = $orderLogic->orderActionLog($order, '编辑订单', '修改订单', $seller_id, 1);//操作日志
            if ($o && $l) {
                $this->success('修改成功', U('Order/editprice', array('order_id' => $order_id)));
            } else {
                $this->success('修改失败', U('Order/detail', array('order_id' => $order_id)));
            }
            exit;
        }
        // 获取省份
        $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //获取订单城市
        $city = M('region')->where(array('parent_id' => $order['province'], 'level' => 2))->select();
        //获取订单地区
        $area = M('region')->where(array('parent_id' => $order['city'], 'level' => 3))->select();
        //获取支付方式
        $payment_list = M('plugin')->where(array('status' => 1, 'type' => 'payment'))->select();
        //获取配送方式
        $shipping_list = M('plugin')->where(array('status' => 1, 'type' => 'shipping'))->select();

        $this->assign('order', $order);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('orderGoods', $orderGoods);
        $this->assign('shipping_list', $shipping_list);
        $this->assign('payment_list', $payment_list);
        return $this->fetch();
    }

    /*
     * 拆分订单
     */
    public function split_order()
    {
        $order_id = I('order_id/d');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if ($order['store_id'] != STORE_ID) {
            $this->error('该订单不存在', U('Seller/Order/index'));
        }
        if ($order['shipping_status'] != 0) {
            $this->error('已发货订单不允许编辑');
            exit;
        }
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        if (IS_POST) {
            //################################先处理原单剩余商品和原订单信息
            $old_goods = I('old_goods/a');
            $oldGoodsPrice  = 0;
            foreach ($orderGoods as $val) {
                if (empty($old_goods[$val['rec_id']])) {
                    M('order_goods')->where("rec_id", $val['rec_id'])->delete();//删除商品
                } else {
                    //修改商品数量
                    if ($old_goods[$val['rec_id']] != $val['goods_num']) {
                        $val['goods_num'] = $old_goods[$val['rec_id']];
                        M('order_goods')->where("rec_id", $val['rec_id'])->save(array('goods_num' => $val['goods_num']));
                    }
                    $oldArr[] = $val;//剩余商品
                    $oldGoodsPrice += $val['goods_price'];
                }
                $all_goods[$val['rec_id']] = $val;//所有商品信息
            }
            $result = calculate_price($order['user_id'], $oldArr, array(STORE_ID => $order['shipping_code']), $order['province'], $order['city'], $order['district'], 0, 0);
            if ($result['status'] < 0) {
                $this->error($result['msg']);
            }
            //修改订单费用
            $res['goods_price'] = $result['result']['goods_price']; // 商品总价
            $res['order_amount'] = $result['result']['order_amount']; // 应付金额
            $res['total_amount'] = $result['result']['total_amount']; // 订单总价
            if($order['user_money'] > 0 || $order['integral']>0){
            	$res['user_money'] = round($oldGoodsPrice/$order['goods_price']*$order['user_money'],2);
            	$res['integral'] = floor($oldGoodsPrice/$order['goods_price']*$order['integral']);
            }
            M('order')->where(['order_id' => $order_id, 'store_id' => STORE_ID])->save($res);
            //################################原单处理结束

            //################################新单处理
            for ($i = 1; $i < 20; $i++) {
                if (!empty($_POST[$i . '_old_goods'])) {
                    $split_goods[] = $_POST[$i . '_old_goods'];//新订单商品
                }
            }
            foreach ($split_goods as $key => $vrr) {
                foreach ($vrr as $k => $v) {
                    $all_goods[$k]['goods_num'] = $v;
                    $brr[$key][] = $all_goods[$k];
                }
            }

            foreach ($brr as $goods) {
                $result = calculate_price($order['user_id'], $goods, array(STORE_ID => $order['shipping_code']), $order['province'], $order['city'], $order['district'], 0, 0);
                if ($result['status'] < 0) {
                    $this->error($result['msg']);
                }
                $new_order = $order;
                $new_order['order_sn'] = date('YmdHis') . mt_rand(1000, 9999);
                $new_order['parent_sn'] = $order['order_sn'];
                $new_order['user_money'] = 0;
                $new_order['integral'] = 0;
                if($order['user_money'] > 0 || $order['integral']>0){
                	$new_order['user_money'] = round($result['result']['goods_price']/$order['goods_price']*$order['user_money'],2);
                	$new_order['integral'] = floor($result['result']['goods_price']/$order['goods_price']*$order['integral']);
                	$new_order['integral_money'] = $new_order['integral']*100;
                }
                //修改订单费用
                $new_order['goods_price'] = $result['result']['goods_price']; // 商品总价
                $new_order['order_amount'] = $result['result']['goods_price'] - $new_order['user_money'] - $new_order['integral_money']; // 应付金额
                $new_order['total_amount'] = $result['result']['goods_price']; // 订单总价
                $new_order['add_time'] = time();

                $new_order['shipping_price'] = 0;//商家主动拆单物流费用忽略
                $new_order['pay_status'] = $order['pay_status'];
                $new_order['shipping_status'] = $order['shipping_status'];
                $new_order['order_status'] = $order['order_status'];
                unset($new_order['order_id']);
                $new_order_id = M('order')->add($new_order);//插入订单表
                foreach ($goods as $vv) {
                    $vv['order_id'] = $new_order_id;//新订单order_id
                    unset($vv['rec_id']);
                    $vv['store_id'] = STORE_ID;
                    $nid = M('order_goods')->add($vv);//插入订单商品表
                }
            }
            $orderLogic->orderActionLog($order, 'split','拆分订单',session('seller_id'));
            //################################新单处理结束
            $this->success('操作成功', U('Order/detail', array('order_id' => $order_id)));
            exit;
        }

        foreach ($orderGoods as $val) {
            $brr[$val['rec_id']] = array('goods_num' => $val['goods_num'], 'goods_name' => getSubstr($val['goods_name'], 0, 35) . $val['spec_key_name']);
        }
        $this->assign('order', $order);
        $this->assign('goods_num_arr', json_encode($brr));
        $this->assign('orderGoods', $orderGoods);
        return $this->fetch();
    }

    /*
     * 价钱修改
     */
    public function editprice($order_id)
    {
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if ($order['store_id'] != STORE_ID) {
            $this->error('该订单不存在', U('Seller/Order/index'));
        }
        $this->editable($order);
        if (IS_POST) {
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
            $update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $row = M('order')->where(array('order_id' => $order_id, 'store_id' => STORE_ID))->save($update);
            if (!$row) {
                $this->success('没有更新数据', U('Order/editprice', array('order_id' => $order_id)));
            } else {
                $this->success('操作成功', U('Order/detail', array('order_id' => $order_id)));
            }
            exit;
        }
        $this->assign('order', $order);
        return $this->fetch();
    }

    /**
     * 订单删除
     * @param int $id 订单id
     *  store_id
     */
    public function delete_order($order_id)
    {
        $orderLogic = new OrderLogic();
        $del = $orderLogic->delOrder($order_id, STORE_ID);
        if ($del) {
            $this->success('删除订单成功');
        } else {
            $this->error('订单删除失败');
        }
    }

    /**
     * 订单取消付款
     */
    public function pay_cancel($order_id)
    {
        if (I('remark')) {
            $data = I('post.');
            $orderLogic = new OrderLogic();
            $order = $orderLogic->getOrderInfo($data['order_id']);
            if ($order['store_id'] != STORE_ID) {
                $this->error('该订单不存在', U('Seller/Order/index'));
            }
            $note = array('退款到用户余额', '已通过其他方式退款', '不处理，误操作项');
            if ($data['refundType'] == 0 && $data['amount'] > 0) {
                accountLog($data['user_id'], $data['amount'], 0, '退款到用户余额');
            }
            $orderLogic->orderProcessHandle($data['order_id'], 'pay_cancel', STORE_ID);
            $seller_id = session('seller_id');
            $d = $orderLogic->orderActionLog($order, '取消付款', $data['remark'] . ':' . $note[$data['refundType']], $seller_id, 1);
            if ($d) {
                exit("<script>window.parent.pay_callback(1);</script>");
            } else {
                exit("<script>window.parent.pay_callback(0);</script>");
            }
        } else {
            $order = M('order')->where("order_id", $order_id)->find();
            $this->assign('order', $order);
            return $this->fetch();
        }
    }

    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print()
    {
        $order_id = I('order_id/d');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if ($order['store_id'] != STORE_ID) {
            $this->error('该订单不存在', U('Seller/Order/index'));
        }
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'] . ' ' . $order['city'] . ' ' . $order['district'] . ' ' . $order['address'];
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        //@new 兼容新UI 计算商品总是 { 
        $order_num_arr = get_arr_column($orderGoods, 'goods_num');
        if ($order_num_arr) {
            $goodsCount = array_sum($order_num_arr);
            $this->assign('goods_count', $goodsCount);
        }
        // }
        $shop = tpCache('shop_info');
        $this->assign('order', $order);
        $this->assign('shop', $shop);
        $this->assign('orderGoods', $orderGoods);
        $template = I('template', 'print');
        return $this->fetch($template);
    }

    /**
     * 快递单打印
     */
    public function shipping_print()
    {
        $order_id = I('get.order_id/d');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if ($order['store_id'] != STORE_ID) {
            $this->error('该订单不存在', U('Seller/Order/index'));
        }
        //查询是否存在订单及物流
        $shipping = M('plugin')->where(array('code' => $order['shipping_code'], 'type' => 'shipping'))->find();
        if (!$shipping) {
            $this->error('物流插件不存在');
        }
        if (empty($shipping['config_value'])) {
            $this->error('请联系平台管理员设置' . $shipping['name'] . '打印模板');
        }
        $shop = M('store')->where(array('store_id' => STORE_ID))->find();
        $shop['province'] = empty($shop['province_id']) ? '' : getRegionName($shop['province_id']);
        $shop['city'] = empty($shop['city_id']) ? '' : getRegionName($shop['city_id']);
        $shop['district'] = empty($shop['district']) ? '' : getRegionName($shop['district']);

        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        if (empty($shipping['config'])) {
            $config = array('width' => 840, 'height' => 480, 'offset_x' => 0, 'offset_y' => 0);
            $this->assign('config', $config);
        } else {
            $this->assign('config', unserialize($shipping['config']));
        }
        $template_var = array("发货点-名称", "发货点-联系人", "发货点-电话", "发货点-省份", "发货点-城市",
            "发货点-区县", "发货点-手机", "发货点-详细地址", "收件人-姓名", "收件人-手机", "收件人-电话",
            "收件人-省份", "收件人-城市", "收件人-区县", "收件人-邮编", "收件人-详细地址", "时间-年", "时间-月",
            "时间-日", "时间-当前日期", "订单-订单号", "订单-备注", "订单-配送费用");
        $content_var = array($shop['store_name'], $shop['seller_name'], $shop['store_phone'], $shop['province'], $shop['city'],
            $shop['district'], $shop['store_phone'], $shop['store_address'], $order['consignee'], $order['mobile'], $order['phone'],
            $order['province'], $order['city'], $order['district'], $order['zipcode'], $order['address'], date('Y'), date('M'),
            date('d'), date('Y-m-d'), $order['order_sn'], $order['admin_note'], $order['shipping_price'],
        );
        $shipping['config_value'] = str_replace($template_var, $content_var, $shipping['config_value']);
        $this->assign('shipping', $shipping);
        return $this->fetch("Plugin/print_express");
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle()
    {
        $orderLogic = new OrderLogic();
        $data = I('post.');
        $res = $orderLogic->deliveryHandle($data, STORE_ID);
        if ($res) {
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功', 'url'=>U('Seller/Order/index')]);
        } else {
            $this->ajaxReturn(['status'=>1,'msg'=>'操作失败', 'url'=>U('Order/delivery_info', array('order_id' => $data['order_id']))]);
        }
    }


    public function delivery_info()
    {
        //商家发货
        $order_id = I('order_id/d');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if ($order['store_id'] != STORE_ID) {
            $this->error('该订单不存在', U('Seller/Order/index'));
        }
        $return_goods = M('return_goods')->where(array('order_id' => $order_id, 'status' => array('<>', -2)))->getField('rec_id,goods_num');
        if ($order['shipping_status'] == 1) {
            $this->error('该订单已发货', U('Seller/Order/index'));
        }
        $orderGoods = $orderLogic->getOrderGoods($order_id,2);
        $delivery_record = Db::name('delivery_doc')->alias('d')->join('__SELLER__ s', 's.seller_id = d.admin_id')->where(['d.order_id' => $order_id])->select();
        if ($delivery_record) {
            $order['invoice_no'] = $delivery_record[count($delivery_record) - 1]['invoice_no'];
        }
        foreach ($orderGoods as $k=>$v){
        	if(!empty($return_goods[$v['rec_id']])){
        		$orderGoods[$k]['unsend'] = 1;
        	}else{
        		$orderGoods[$k]['unsend'] = 0;
        	}
        }
        $this->assign('order', $order);
        $this->assign('orderGoods', $orderGoods);
        $this->assign('delivery_record', $delivery_record);//发货记录
        return $this->fetch();
    }

    /**
     * 发货单列表
     */
    public function delivery_list()
    {
        $this->assign('begin', date('Y-m-d', strtotime("-3 month")+86400));
        $this->assign('end', date('Y-m-d', strtotime('+1 days')));        
        return $this->fetch();
    }
    
    /**
     * 订单操作
     * @param $id
     */
    public function order_action()
    {
        $orderLogic = new OrderLogic();
        $type = I('get.type');
        $order_id = I('get.order_id/d');
        if ($type && $order_id) {
        	$order = $orderLogic->getOrderInfo($order_id);
            $button = $orderLogic->getOrderButton($order);
        	if($order){
        		$a = $orderLogic->orderProcessHandle($order_id, $type, STORE_ID);
        		$seller_id = session('seller_id');
                $action = '';
                if(in_array($type,array_keys($button))){
                    $action = $button[$type];
                }
        		$res = $orderLogic->orderActionLog($order, $action, I('note'), $seller_id, 1);
        		if ($res && $a) {
        			exit(json_encode(array('status' => 1, 'msg' => '操作成功')));
        		} else {
        			exit(json_encode(array('status' => 0, 'msg' => '操作失败')));
        		}
        	}else{
        		exit(json_encode(array('status' => 0, 'msg' => '非法操作')));
        	}
        } else {
            $this->error('参数错误', U('Seller/Order/detail', array('order_id' => $order_id)));
        }
    }

    public function order_log()
    {
        $condition = array();
        $log = M('order_action');
        $admin_id = I('admin_id/d');
        if ($admin_id > 0) {
            $condition['action_user'] = $admin_id;
        }
        $condition['store_id'] = STORE_ID;
        $count = $log->where($condition)->count();
        $Page = new Page($count, 20);
        foreach ($condition as $key => $val) {
            $Page->parameter[$key] = urlencode($val);
        }
        $show = $Page->show();
        $list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $seller = M('seller')->getField('seller_id,seller_name');
        $this->assign('admin', $seller);
        return $this->fetch();
    }

    /**
     * 检测订单是否可以编辑
     * @param $order
     */
    private function editable($order)
    {
        if ($order['shipping_status'] != 0) {
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }

    public function export_order()
    {
        //搜索条件
        $where['store_id'] = STORE_ID;
        $consignee = I('consignee');
        if ($consignee) {
            $where['consignee'] = ['like', '%'.$consignee.'%'];
        }
        $order_sn = I('order_sn');
        if ($order_sn) {
            $where['order_sn'] = $order_sn;
        }
        $order_status = I('order_status');
        if ($order_status) {
            $where['order_status'] = $order_status;
        }

        $timegap = I('timegap');
        if ($timegap) {
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
            $where['add_time'] = ['between',[$begin,$end]];
        }
        $region = Db::name('region')->cache(true)->getField('id,name');
        $orderList = Db::name('order')->field("*,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time")->where($where)->order('order_id')->select();
        $strTable = '<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
        $strTable .= '</tr>';

        foreach ($orderList as $k => $val) {
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;' . $val['order_sn'] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['create_time'] . ' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . "{$region[$val['province']]},{$region[$val['city']]},{$region[$val['district']]},{$region[$val['twon']]}{$val['consignee']}" . ' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['address'] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['mobile'] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['goods_price'] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['order_amount'] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['pay_name'] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $this->pay_status[$val['pay_status']] . '</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $this->shipping_status[$val['shipping_status']] . '</td>';
            $orderGoods = D('order_goods')->where('order_id', $val['order_id'])->select();
            $strGoods = "";
            foreach ($orderGoods as $goods) {
                $strGoods .= "商品编号：" . $goods['goods_sn'] . " 商品名称：" . $goods['goods_name'];
                if ($goods['spec_key_name'] != '') $strGoods .= " 规格：" . $goods['spec_key_name'];
                $strGoods .= "<br />";
            }
            unset($orderGoods);
            $strTable .= '<td style="text-align:left;font-size:12px;">' . $strGoods . ' </td>';
            $strTable .= '</tr>';
        }
        $strTable .= '</table>';
        unset($orderList);
        downloadExcel($strTable, 'order');
        exit();
    }

    /**
     * 用于测试使用，旧模板删除，新模板没有套
     * 添加一笔订单
     */
    public function add_order()
    {
        $order = array('store_id' => STORE_ID);
        //  获取省份
        $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //  获取订单城市
        $city = M('region')->where(array('parent_id' => $order['province'], 'level' => 2))->select();
        //  获取订单地区
        $area = M('region')->where(array('parent_id' => $order['city'], 'level' => 3))->select();
        //  获取配送方式
        $shipping_list = M('plugin')->where(array('status' => 1, 'type' => 'shipping'))->select();
        //  获取支付方式
        $payment_list = M('plugin')->where(array('status' => 1, 'type' => 'payment'))->select();
        if (IS_POST) {
            $order['user_id'] = I('user_id/d');// 用户id 可以为空
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机           
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注            
            $order['order_sn'] = date('YmdHis') . mt_rand(1000, 9999); // 订单编号;
            $order['admin_note'] = I('admin_note'); // 
            $order['add_time'] = time(); //                    
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status' => 1, 'type' => 'shipping', 'code' => I('shipping')))->getField('name');
            $order['pay_code'] = I('payment');// 支付方式            
            $order['pay_name'] = M('plugin')->where(array('status' => 1, 'type' => 'payment', 'code' => I('payment')))->getField('name');

            $goods_id_arr = I("goods_id/d");
            $orderLogic = new OrderLogic();
            $order_goods = $orderLogic->get_spec_goods($goods_id_arr);
            $result = calculate_price($order['user_id'], $order_goods, array(STORE_ID => $order['shipping_code']), $order[province], $order[city], $order[district], 0, 0, 0, 0);
            if ($result['status'] < 0) {
                $this->error($result['msg']);
            }

            $order['goods_price'] = $result['result']['goods_price']; // 商品总价
            $order['shipping_price'] = $result['result']['store_shipping_price'][STORE_ID]; //物流费
            $order['order_amount'] = $result['result']['order_amount']; // 应付金额
            $order['total_amount'] = $result['result']['total_amount']; // 订单总价

            // 添加订单
            $order_id = M('order')->add($order);
            if ($order_id) {
                foreach ($order_goods as $key => $val) {
                    $val['order_id'] = $order_id;
                    $val['store_id'] = STORE_ID;
                    $rec_id = M('order_goods')->add($val);
                    if (!$rec_id)
                        $this->error('添加失败');
                }
                $this->success('添加商品成功', U("Order/detail", array('order_id' => $order_id)));
                exit();
            } else {
                $this->error('添加失败');
            }
        }
        $this->assign('shipping_list', $shipping_list);
        $this->assign('payment_list', $payment_list);
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        return $this->fetch();
    }

    /**
     * 选择搜索商品
     */
    public function search_goods()
    {
        $GoodsLogic = new GoodsLogic;
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $where = array('store_id' => STORE_ID, 'is_on_sale' => 1);
        I('intro') && $where[I('intro')] = 1;
        $brand_id = I('brand_id/d');
        $cat_id = I('cat_id/d');
        $keywords = I('keywords');
        $where['is_virtual'] = I('is_virtual/d',0); //默认不查虚拟商品
        if ($cat_id) {
            $goods_category = M('goods_category')->where("id", $cat_id)->find();
            $where['cat_id' . $goods_category['level']] = $cat_id; // 初始化搜索条件
            $this->assign('cat_id', $cat_id);
        }
        if ($brand_id) {
            $this->assign('brand_id', $brand_id);
            $where['brand_id'] = $brand_id;
        }
        if ($keywords) {
            $this->assign('keywords', $keywords);
            $where['goods_name|keywords'] = array('like', '%' . $keywords . '%');
        }
        $count = M('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($goodsList as $key => $val) {
            $spec_goods = M('spec_goods_price')->where("goods_id", $val['goods_id'])->select();
            $goodsList[$key]['spec_goods'] = $spec_goods;
        }
        $store_bind_class = M('store_bind_class')->where("store_id", STORE_ID)->select();
        $cat_id1 = get_arr_column($store_bind_class, 'class_1');
        $cat_id2 = get_arr_column($store_bind_class, 'class_2');
        $cat_id3 = get_arr_column($store_bind_class, 'class_3');
        $cat_id0 = array_merge($cat_id1, $cat_id2, $cat_id3);

        $this->assign('categoryList', $categoryList);
        $this->assign('brandList', $brandList);
        $this->assign('page', $Page->show());//赋值分页输出
        $this->assign('cat_id0', $cat_id0);
        $this->assign('goodsList', $goodsList);
        return $this->fetch();
    }

    public function ajaxOrderNotice()
    {
        $order_amount = M('order')->where(array('order_status' => 0))->count();
        echo $order_amount;
    }

    //虚拟订单临时支付方法，以后要删除
    public function generateVirtualCode(){
        $order_id = I('order_id/d');
        // 获取操作表
        $select_year = getTabByOrderId($order_id);
        $order = M('order'.$select_year)->where(array('order_id'=>$order_id,'store_id'=>STORE_ID))->find();
        $res = update_pay_status($order['order_sn'], 1);
        if($res){
            $this->success('成功生成兑换码', U('Order/virtual_info',['order_id'=>$order_id]), 1);
        }
        $this->error('失败', U('Order/virtual_info',['order_id'=>$order_id]), 1);
    }
}
