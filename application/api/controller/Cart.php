<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace app\api\controller; 

use app\common\logic\CartLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\OrderLogic;
use think\Db;

class Cart extends Base {
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        // 给用户计算会员价 登录前后不一样
        if($this->user_id){
            $user = M('users')->where("user_id", $this->user_id)->find();
            M('Cart')->execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$unique_id}') and prom_type = 0");        
        }
    }

    /**
     * 将商品加入购物车
     */
    function addCart()
    {
        $goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $item_id = I("item_id/d"); // 商品规格id
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        
        if(empty($goods_id)) {
            $this->ajaxReturn(['status'=>0,'msg'=>'请选择要购买的商品','result'=>'']);
        }
        if(empty($goods_num)) {
           $this->ajaxReturn(['status'=>0,'msg'=>'购买商品数量不能为0','result'=>'']);
        }
        
        $cartLogic = new CartLogic();
        $cartLogic->setGoodsModel($goods_id);
        $cartLogic->setUniqueId($unique_id);
        $cartLogic->setUserId($this->user_id);
        if ($item_id) {
            $cartLogic->setSpecGoodsPriceModel($item_id);
        }
        $cartLogic->setGoodsBuyNum($goods_num);
        $result = $cartLogic->addGoodsToCart(); // 将商品加入购物车
        $this->ajaxReturn($result);
    }
    
    /**
     * 删除购物车的商品
     */
    public function delCart()
    {       
        $ids = I("ids"); // 商品 ids        
        $result = M("Cart")->where("id","in", $ids)->delete(); // 删除id为5的用户数据
        
        // 查找购物车数量
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $cartLogic = new CartLogic();
        $cartLogic->setUniqueId($unique_id);
        $cart_count =  $cartLogic->getUserCartGoodsNum();
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>$cart_count); // 返回结果状态       
        $this->ajaxReturn($return_arr);
    }
    
    
    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {                    
        $cart_form_data = $_POST["cart_form_data"]; // goods_num 购物车商品数量
        $cart_form_data = json_decode($cart_form_data,true); //app 端 json 形式传输过来                
        $unique_id = I("unique_id/s"); // 唯一id  类似于 pc 端的session id
        $unique_id = empty($unique_id) ? -1 : $unique_id;
        $where['session_id'] = $unique_id; // 默认按照 $unique_id 查询
        $store_where = "session_id = '{$unique_id}'";
        // 如果这个用户已经登录则按照用户id查询
        if ($this->user_id) {
            unset($where);
            $where['user_id'] = $this->user_id;
            $store_where  = "user_id = ".$this->user_id;
        } 
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected"); 
        
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartLogic->setUniqueId($unique_id);
        
        if ($cart_form_data) {
            $updateData = [];
            // 修改购物车数量 和勾选状态
            foreach ($cart_form_data as $key => $val) {
                if (!isset($cartList[$val['cartID']])) {
                    continue;
                }
                $updateData[$key]['goods_num'] = $val['goodsNum'];
                $updateData[$key]['selected'] = $val['selected'];
                $updateData[$key]['id'] = $val['cartID'];
                if ($cartList[$val['cartID']]['goods_num'] != $val['goodsNum']) {
                    $changeResult = $cartLogic->changeNum($val['cartID'], $val['goodsNum']);
                    if ($changeResult['status'] != 1) {
                        $this->ajaxReturn($changeResult);
                    }
                    break;
                }
            }
            if ($updateData) {
                $cartLogic->AsyncUpdateCart($updateData);
            }
        } 
        $cartList = $cartLogic->getCartList(1);// 选中的商品
        $result['total_price'] = $cartLogic->getCartPriceInfo($cartList);
        if($result['total_price']){
            $result['total_price']['cut_fee'] = $result['total_price']['goods_fee'];
            $result['total_price']['num'] = $result['total_price']['goods_num'];
            unset($result['total_price']['goods_fee']);
            unset($result['total_price']['goods_num']);
        }
        $cartList = $cartLogic->getCartList(0);// 所有的商品
        $cart_count = 0;
        foreach($cartList as $cartKey=>$cart){
            $cart['store_count'] = $cart['goods']['store_count'];
            $cart_count += $cart['goods_num'];//重新计算购物车商品数量
             unset($cart['goods']['goods_content']); 
        } 
        $storeList = M('store')->where("store_id in(select store_id from ".C('database.prefix')."cart where ( {$store_where})  )")->getField("store_id,store_name,store_logo,is_own_shop"); // 找出商家
        foreach($storeList as $k => $v)
        {
            $store = array("store_id"=>$k,'store_name'=>$v['store_name'],'store_logo'=>$v['store_logo'],'is_own_shop'=>$v['is_own_shop']);
            foreach($cartList as $k2 => $v2)
            {
                if($v2['store_id'] == $k){
                    $store['cartList'][] = $v2;
                }
            }
            $result['storeList'][] = $store;
        }
         
        $return['total_price']['num'] = $cart_count;
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }
    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {
        $address_id = input('address_id', 0);
        if ($this->user_id == 0) {
            $this->ajaxReturn(array('status'=>-1,'msg'=>'用户user_id不能为空','result'=>''));
        }
        
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        if($cartLogic->getUserCartOrderCount() == 0 ) {
            $this->ajaxReturn(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>''));
        }
        $usersInfo = get_user_info($this->user_id);  // 用户
        $cartList = $cartLogic->getCartList(1);
        $cartPriceInfo = $cartLogic->getCartPriceInfo($cartList);
        // 没有选中的不传递过去
        $cart_result['cartList'] = $cartList;     
        
        $store_id_arr = M('cart')->where(['user_id' =>$this->user_id, 'selected' =>1])->getField('store_id',true); // 获取所有店铺id
        $shippingList = M('shipping_area')->where(" store_id in (".implode(',', $store_id_arr).")")->group("store_id,shipping_code")->getField('shipping_area_id,shipping_code,store_id');// 物流公司
        $shippingList2 = M('plugin')->where("type = 'shipping'")->getField('code,name'); // 查找物流插件
        foreach($shippingList as $k => $v) {
            $shippingList[$k]['name']  = $shippingList2[$v['shipping_code']];        
        }
       
        //获取地址
        if ($address_id) {
            $userAddress = M('UserAddress')->where(['user_id' => $this->user_id, 'address_id' => $address_id])->find();
        } 
        if (!$address_id || !$userAddress) {
            $addresslist = M('UserAddress')->where("user_id = {$this->user_id}")->select();
            $userAddress = $addresslist[0];
            foreach ($addresslist as $address) {
                if ($address['is_default'] == 1) {
                    $userAddress = $address;
                    break;
                }
            }
        }
        if ($userAddress) {
            $userAddress['total_address'] = getTotalAddress($userAddress['province'], $userAddress['city'], $userAddress['district'], $userAddress['twon'], $userAddress['address']);
            $district = $userAddress['district'] ?: 0;
            $city     = $userAddress['city'] ?: 0;
            $province = $userAddress['province'] ?: 0; 
        }
        
        $storeList = M('store')->where("store_id in(select store_id from ".C('database.prefix')."cart where user_id = :user_id and selected =1)")->bind(['user_id'=>$this->user_id])->getField("store_id,store_name"); // 找出商家
        $goodsLogic = new GoodsLogic;
        // 循环店铺
        foreach($storeList as $store_id => $v) {
            $store = array('store_id' => $store_id, 'store_name'=>$v);
            //循环物流
            foreach($shippingList as $v3) {
                if($v3['store_id'] == $store_id) {
                    $v3['freight'] = 0;
                    $store['shippingList'][] = $v3;
                }
            }
            $store_goods_fee = 0;
            foreach ($cart_result['cartList'] as $v4) {
                if($v4['store_id'] == $store_id) {
                   $store_goods_fee += $v4['goods_fee'];
                   $store['cartList'][] = $v4;
                }
            }
            $store['cart_total_money'] = $store_goods_fee;
            
            // 找出这个订单的优惠券数量 没过期的  并且 订单金额达到 condition 优惠券指定标准的    
            $store['coupon_num'] = Db::name('coupon')->alias('c1')
                ->join('__COUPON_LIST__ c2', 'c2.cid = c1.id and c1.type in(0,1,2,3) AND c2.status=0')
                ->where([
                    'c2.uid' => $this->user_id,
                    'c1.use_end_time' => ['>', time()],
                    'c1.condition' => ['<=', $store_goods_fee],
                    'c2.store_id' => $store_id
                ])->count();
            
            /* 物流费 */
            foreach ($store['shippingList'] as &$ship_v) {
                $shipping_code = [$store_id => $ship_v['shipping_code']];
                $dispatchs = calculate_price($this->user_id, $store['cartList'], $shipping_code, $province, $city, $district);
                if ($dispatchs['status'] !== 1) {
                    $this->ajaxReturn($dispatchs);
                }
                $ship_v['freight'] = $dispatchs['result']['shipping_price'];
            }
            //店铺优惠信息
            $store['store_prom'] = $goodsLogic->getOrderPayProm($store_id);
            $storeListResult[] = $store;
        }
        
        $json_arr = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>array(
                'addressList' =>$userAddress, // 收货地址
                'totalPrice'  =>$cartPriceInfo, // 总计
                'userInfo'    =>$usersInfo, // 用户详情   
                'storeList'   =>$storeListResult
        ));   
        $this->ajaxReturn($json_arr) ;       
    }
       
    /**
     * 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
        $address_id = I("address_id/d"); //  收货地址id        
        $invoice_title = I('invoice_title'); // 发票        
        $pay_points =  I("pay_points/d",0); //  使用积分
        $user_money =  I("user_money/f",0); //  使用余额        
        $user_money = $user_money ? $user_money : 0;                                              
        
        $cart_form_data = $_POST["cart_form_data"]; // goods_num 购物车商品数量          
        $cart_form_data = json_decode($cart_form_data,true); //app 端 json 形式传输过来

        $shipping_code    = $cart_form_data['shipping_code']; // $shipping_code =  I("shipping_code"); //  物流编号  数组形式
        $user_note        = $cart_form_data['user_note'] ?: ''; // $user_note = I('user_note'); // 给卖家留言      数组形式
        $coupon_id        = $cart_form_data['coupon_id'] ?: 0; // $coupon_id =  I("coupon_id/d",0); //  优惠券id  数组形式
        $couponCode       = $cart_form_data['couponCode']; // $couponCode =  I("couponCode"); //  优惠券代码  数组形式        

        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        if($cartLogic->getUserCartOrderCount() == 0 ) exit(json_encode(array('status'=>-1,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        if(!$address_id) exit(json_encode(array('status'=>-1,'msg'=>'请完善收货人信息','result'=>null))); // 返回结果状态
        if(!$shipping_code) exit(json_encode(array('status'=>-1,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态
        
        $address = M('UserAddress')->where("address_id",$address_id)->find();
        $order_goods = M('cart')->where(["user_id"=> $this->user_id , "selected" => 1])->select();
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,$address['province'],$address['city'],$address['district'],$pay_points,$user_money,$coupon_id,$couponCode);

        if($result['status'] < 0) {
            $this->ajaxReturn($result);
        }
        
        $car_price = array(
            'postFee'      => $result['result']['shipping_price'], // 物流费
            'couponFee'    => $result['result']['coupon_price'], // 优惠券            
            'balance'      => $result['result']['user_money'], // 使用用户余额
            'pointsFee'    => $result['result']['integral_money'], // 积分支付            
            'payables'     => array_sum($result['result']['store_order_amount']), // 订单总额 减去 积分 减去余额
            'goodsFee'     => $result['result']['goods_price'],// 总商品价格
            'order_prom_amount' => array_sum($result['result']['store_order_prom_amount']), // 总订单优惠活动优惠了多少钱

            'store_order_prom_id'=> $result['result']['store_order_prom_id'], // 每个商家订单优惠活动的id号
            'store_order_prom_amount'=> $result['result']['store_order_prom_amount'], // 每个商家订单活动优惠了多少钱
            'store_order_amount' => $result['result']['store_order_amount'], // 每个商家订单优惠后多少钱, -- 应付金额
            'store_shipping_price'=>$result['result']['store_shipping_price'],  //每个商家的物流费
            'store_coupon_price'=>$result['result']['store_coupon_price'],  //每个商家的优惠券抵消金额
            'store_point_count' => $result['result']['store_point_count'], // 每个商家平摊使用了多少积分            
            'store_balance'=>$result['result']['store_balance'], // 每个商家平摊用了多少余额
            'store_goods_price'=>$result['result']['store_goods_price'], // 每个商家的商品总价
        );   

        // 提交订单        
        if ($_REQUEST['act'] == 'submit_order') {  
            if (empty($coupon_id) && !empty($couponCode)) {
                foreach($couponCode as $k => $v)
                $coupon_id[$k] = M('CouponList')->where("`code`='$v' and store_id = $k")->getField('id');
            }
            $orderLogic = new OrderLogic();
            $result = $orderLogic->addOrder($this->user_id,$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price,$user_note); // 添加订单
            exit(json_encode($result));            
        }
        
        $this->ajaxReturn(['status'=>1, 'msg'=>'计算成功', 'result'=>$car_price]);      
    }
 
    /**
     * 订单支付页面
     */
    public function cart4()
    {
        // 如果是主订单号过来的, 说明可能是合并付款的
        $master_order_sn = I('master_order_sn','');        
        if (!$master_order_sn) {                       
            $this->ajaxReturn(['status'=>-1, 'msg'=>'参数错误']);
        }
        $sum_order_amount = M('order')->where("master_order_sn", $master_order_sn)->sum('order_amount');      
        if (!is_numeric($sum_order_amount)) {
            $this->ajaxReturn(['status'=>-1, 'msg'=>'订单不存在']);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'获取成功','result' => $sum_order_amount]);
    }    
    
}
