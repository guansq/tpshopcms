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
namespace app\mobile\controller;

use app\common\logic\CouponLogic;
use app\common\logic\OrderLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\TeamActivityLogic;
use app\common\logic\TeamOrderLogic;
use app\common\model\Goods;
use app\common\model\Order;
use app\common\model\OrderGoods;
use app\common\model\ShippingArea;
use app\common\model\TeamActivity;
use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use app\common\model\UserAddress;
use think\Db;
use think\Page;


class Team extends MobileBase
{
    public $user_id = 0;
    public $user = array();
    /**
     * 构造函数
     */
    public function  __construct()
    {
        parent::__construct();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
    }

    /**
     * 拼团首页
     * @return mixed
     */
    public function index()
    {
        $TeamActivity = new TeamActivity();
        $team_where = ['status' => 1];
        $count = $TeamActivity->where($team_where)->count();
        $goods_category = Db::name('goods_category')->where(['level' => 1, 'is_show' => 1])->select();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $TeamActivity->append(['team_type_desc', 'time_limit_hours', 'status_desc'])->where($team_where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('list', $list);
        $this->assign('goods_category', $goods_category);
        return $this->fetch();
    }

    /**
     * 拼团首页列表
     */
    public function AjaxTeamList(){
        $p = Input('p',1);
        $team_where = ['status' => 1];
        $TeamActivity = new TeamActivity();
        $list = $TeamActivity->with('specGoodsPrice,goods')->where($team_where)->page($p, 10)->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功','result'=>$list]);
    }

    /**
     * 拼团活动详情
     * @return mixed
     */
    public function info()
    {
        $team_id = input('id');
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->with('specGoodsPrice,goods,store')->where('team_id',$team_id)->find();
        if(empty($team_id)){
            $this->error('参数错误', U('Mobile/Team/index'));
        }
        if (empty($team)) {
            $this->error('该商品拼团活动不存在或者已被删除', U('Mobile/Team/index'));
        }
        if(empty($team['goods'])){
            $this->error('此商品不存在或者已下架', U('Mobile/Team/index'));
        }
        $user_id = cookie('user_id');
        $goodsLogic = new GoodsLogic();
        $filter_spec = $goodsLogic->get_spec($team['goods_id']);
        if($user_id){
            $collect = Db::name('goods_collect')->where(array("goods_id"=>$team['goods_id'] ,"user_id"=>$user_id))->count();
            $this->assign('collect',$collect);
        }
        $goods_images_list = Db::name('goods_images')->where("goods_id" , $team['goods_id'])->select(); // 商品图册
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $commentStatistics = $goodsLogic->commentStatistics($team['goods_id']);// 获取某个商品的评论统计
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('filter_spec', $filter_spec);//规格参数
        $this->assign('team', $team->toArray());//商品拼团活动主体
        return $this->fetch();
    }

    /**
     * 下单
     */
    public function addOrder()
    {
        C('TOKEN_ON', false);
        $team_id = input('team_id/d');
        $goods_num = input('goods_num/d');
        $found_id = input('found_id/d');//拼团id，有此ID表示是团员参团,没有表示团长开团
        if ($this->user_id == 0) {
            $this->ajaxReturn(['status' => -101, 'msg' => '购买拼团商品必须先登录', 'result' => '']);
        }
        if (empty($team_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status' => 0, 'msg' => '至少购买一份', 'result' => '']);
        }
        $team = TeamActivity::get($team_id);
        if($found_id){
            $teamFound = TeamFound::get(['found_id' => $found_id, 'status' => 1]);
            if(empty($teamFound)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '该拼单数据不存在或已失效', 'result' => '']);
            }
            $teamActivityLogic = new TeamActivityLogic();
            $IsCanFollow = $teamActivityLogic->TeamFoundIsCanFollow($teamFound, $team);
            if($IsCanFollow['status'] != 1){
                $this->ajaxReturn(['status' => 0, 'msg' => $IsCanFollow['msg'], 'result' => '']);
            }
        }
        $teamOrderLogic = new TeamOrderLogic();
        if (!empty($teamFound)) {
            $teamOrderLogic->setTeamFound($teamFound);
        }
        $teamOrderLogic->setTeam($team);
        $teamOrderLogic->setGoods($team->goods);
        $teamOrderLogic->setSpecGoodsPrice($team->specGoodsPrice);
        $teamOrderLogic->setUserId($this->user_id);
        $teamOrderLogic->setGoodsBuyNum($goods_num);
        $result = $teamOrderLogic->add();
        $this->ajaxReturn($result);
    }

    /**
     * 结算页
     * @return mixed
     */
    public function order()
    {
        $order_id = input('order_id/d',0);
        $address_id = input('address_id/d');
        if(empty($this->user_id)){
            $this->redirect("User/login");
            exit;
        }
        if ($address_id) {
            $address_where = ['address_id' => $address_id];
        } else {
            $address_where = ["user_id" => $this->user_id, "is_default" => 1];
        }
        $address = Db::name('user_address')->where($address_where)->find();
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'team')));
            exit;
        }else{
            $this->assign('address',$address);
        }
        $Order = new Order();
        $OrderGoods = new OrderGoods();
        $order = $Order->with('store')->where(['order_id'=>$order_id,'user_id'=>$this->user_id])->find();
        if(empty($order)){
            $this->error('订单不存在或者已取消',U("Mobile/Order/order_list"));
        }
        $order_goods = $OrderGoods->with('goods')->where(['order_id' => $order_id])->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/Order/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
        }
        if($order['order_status'] == 3 ){   //订单已经取消
            $this->error('订单已取消',U("Mobile/Order/order_list"));
        }
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $plugin_where = ['type'=>'payment','status'=>1,'code'=>'weixin'];
        }else{
            $plugin_where = ['type'=>'payment','status'=>1,'scene'=>1];
        }
        $pluginList = Db::name('plugin')->where($plugin_where)->select();
        $paymentList = convert_arr_key($pluginList, 'code');
        //不支持货到付款
        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            //判断当前浏览器显示支付方式
            if (($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())) {
                unset($paymentList[$key]);
            }
        }
        $ShippingArea = new ShippingArea();
        $shipping_area= $ShippingArea->with('plugin')->where(['store_id'=>$order['store_id'],'is_default' => 1, 'is_close' => 1])->group("shipping_code")->cache(true, TPSHOP_CACHE_TIME)->select();
        $couponLogic = new CouponLogic();
        $TeamActivity = new TeamActivityLogic();
        $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, [$order_goods['goods_id']], [$order_goods['goods']['cat_id3']]);//用户可用的优惠券列表
        $userCartCouponList = $TeamActivity->getCouponOrderList($order, $userCouponList);
        $this->assign('userCartCouponList', $userCartCouponList);
        $this->assign('paymentList', $paymentList);
        $this->assign('order', $order);
        $this->assign('order_goods', $order_goods);
        $this->assign('shipping_area',$shipping_area);
        return $this->fetch();
    }

    /**
     * 立即支付,更改订单
     */
    public function updateOrder(){
        $order_id = input('order_id/d');
        $address_id = input('address_id/d');
        if(empty($this->user_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'登录超时','result'=>['url'=>U("User/login")]]);
        }
        if(empty($address_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'请选择地址','result'=>[]]);
        }
        $order = Order::get(['user_id' => $this->user_id, 'order_id' => $order_id,['']]);
        if(empty($order)){
            $this->ajaxReturn(['status'=>0,'msg'=>'该订单已关闭或者不存在','result'=>['url'=>U("Mobile/Order/order_list")]]);
        }
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/Order/order_detail",array('id'=>$order_id));
            $this->ajaxReturn(['status'=>0,'msg'=>'该订单已支付成功','result'=>['url'=>$order_detail_url]]);
        }
        $address = Db::name('user_address')->where(['user_id' => $this->user_id, 'address_id' => $address_id])->find();
        if (empty($address)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '非法操作', 'result' => []]);
        }
        $orderData = [
            'consignee'        =>$address['consignee'], // 收货人
            'province'         =>$address['province'],//'省份id',
            'city'             =>$address['city'],//'城市id',
            'district'         =>$address['district'],//'县',
            'twon'             =>$address['twon'],// '街道',
            'address'          =>$address['address'],//'详细地址',
            'mobile'           =>$address['mobile'],//'手机',
            'zipcode'          =>$address['zipcode'],//'邮编',
            'email'            =>$address['email'],//'邮箱',
        ];
        $order->data($orderData,true);
        $orderSave = $order->save();
        if($orderSave !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'添加地址成功','result'=>[]]);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'添加地址失败','result'=>[]]);
        }
    }

    /**
     * 获取订单详细
     */
    public function getOrderInfo(){
        $order_id = input('order_id/d');
        $shipping_code = input('shipping_code/s');//配送方式
        $goods_num = input('goods_num/d');
        $coupon_id = input('coupon_id/d');
        $address_id = input('address_id/d');
        $user_money = input('user_money/f');
        $pay_points = input('pay_points/d');
        $act = input('post.act','');
        if(empty($this->user_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'登录超时','result'=>['url'=>U("User/login")]]);
        }
        if(empty($order_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误','result'=>[]]);
        }
        if(empty($address_id)){
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择地址', 'result' => ['url' => U('Mobile/User/add_address', array('source' => 'team', 'order_id' => $order_id))]]);
        }
        //获取订单,检查订单
        $Order = new Order();
        $order = $Order->with('store')->where(['order_id' => $order_id, 'order_prom_type' => 6, 'user_id' => $this->user_id])->find();
        if(empty($order)){
            $this->ajaxReturn(['status'=>0,'msg'=>'该订单已关闭或者不存在','result'=>['url'=>U("Mobile/Order/order_list")]]);
        }
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/Order/order_detail",array('id'=>$order_id));
            $this->ajaxReturn(['status'=>0,'msg'=>'该订单已支付成功','result'=>['url'=>$order_detail_url]]);
        }

        //获取订单商品,检查订单商品
        $OrderGoods = new OrderGoods();
        $orderGoods = $OrderGoods->with('goods')->where(['order_id' => $order_id, 'prom_type' => 6])->find();
        if (empty($orderGoods)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '该订单失效或不存在', 'result' => []]);
        }

        //获取拼团活动,检查活动
        $TeamActivity = new TeamActivity();
        $team = $TeamActivity->where(['team_id'=>$orderGoods['prom_id']])->find();
        if(empty($team)){
            $this->ajaxReturn(['status' => 0, 'msg' => '订单失效或不存在', 'result' => []]);
        }

        //获取用户地址，检查用户地址
        $UserAddress = new UserAddress();
        $userAddress = $UserAddress->where(['address_id'=>$address_id,'user_id'=>$this->user_id])->find();
        if(empty($userAddress)){
            $this->ajaxReturn(['status' => -1, 'msg' => '请选择地址', 'result' => []]);
        }

        //检查购买数
        if($goods_num > $team['buy_limit']){
            $this->ajaxReturn(['status' => 0, 'msg' => '购买数已超过该活动单次购买限制数('.$team['buy_limit'].'个)', 'result' => []]);
        }

        //使用余额,检查使用余额条件
        if($user_money && $user_money > $this->user['user_money']){
            $this->ajaxReturn(['status' => 0, 'msg' => '你的账户可用余额为:'.$this->user['user_money'].'元', 'result' => []]);
        }

        //使用积分检查,检查使用积分条件
        if($pay_points){
            $use_percent_point = tpCache('shopping.point_use_percent');     //最大使用限制: 最大使用积分比例, 例如: 为50时, 未50% , 那么积分支付抵扣金额不能超过应付金额的50%
            if($use_percent_point == 0){
                $this->ajaxReturn(['status' => 0, 'msg' => '该笔订单不能使用积分', 'result' => []]);
            }
            if ($pay_points > $this->user['pay_points']){
                $this->ajaxReturn(['status' => 0, 'msg' => '你的账户可用积分为:'.$this->user['pay_points'], 'result' => []]);
            }
            $min_use_limit_point = tpCache('shopping.point_min_limit'); //最低使用额度: 如果拥有的积分小于该值, 不可使用
            if ($min_use_limit_point > 0 && $pay_points < $min_use_limit_point) {
                $this->ajaxReturn(['status' => 0, 'msg' => '您使用的积分必须大于'.$min_use_limit_point.'才可以使用', 'result' => []]);
            }
        }
        //获取拼单信息，并检查拼单,是否能拼
        $TeamActivityLogic = new TeamActivityLogic();
        $teamFollow = TeamFollow::get(['order_id' => $order_id, 'follow_user_id' => $this->user_id]);
        if ($teamFollow) {
            $teamFound = $teamFollow->teamFound;
            if (empty($teamFound)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '团长的单不翼而飞了', 'result' => []]);
            } else {
                $IsCanFollow = $TeamActivityLogic->TeamFoundIsCanFollow($teamFound, $team);
                if($IsCanFollow['status'] != 1){
                    $this->ajaxReturn(['status' => 0, 'msg' => $IsCanFollow['msg'], 'result' => '']);
                }
            }
        }
        $couponLogic = new CouponLogic();
        $TeamOrderLogic = new TeamOrderLogic();
        $TeamOrderLogic->setUserId($this->user_id);
        $TeamOrderLogic->setOrder($order);
        $TeamOrderLogic->setOrderGoods($orderGoods);
        $TeamOrderLogic->setGoods($orderGoods->goods);
        $TeamOrderLogic->setStore($order->store);
        $TeamOrderLogic->changeNum($goods_num); //购买数量
        $TeamOrderLogic->useCouponById($coupon_id); //使用优惠券
        $TeamOrderLogic->useShipping($shipping_code, $userAddress); //选择物流
        $TeamOrderLogic->useUserMoney($user_money);//使用余额
        $TeamOrderLogic->usePayPoints($pay_points);//使用积分
        $finalOrder = $TeamOrderLogic->getOrder();
        $finalOrderGoods = $TeamOrderLogic->getOrderGoods();
        // 确认订单
        if ($act == 'submit_order') {
            $finalOrder->save();
            $finalOrderGoods->save();
            $this->ajaxReturn(['status' => 1, 'msg' => '确认订单成功', 'result' => []]);
        }else{
            $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, [$orderGoods['goods_id']], [$orderGoods['goods']['cat_id3']]);//用户可用的优惠券列表
            $userCartCouponList = $TeamActivityLogic->getCouponOrderList($finalOrder, $userCouponList);
            $result = [
                'order'=>$finalOrder,
                'order_goods'=>$finalOrderGoods,
                'couponList'=>$userCartCouponList,
            ];
            $this->ajaxReturn(['status' => 1, 'msg' => '计算成功', 'result' => $result]);
        }

    }

    /**
     * 拼团分享页
     * @return mixed
     */
    public function found()
    {
        $found_id = input('id');
        if (empty($found_id)) {
            $this->error('参数错误', U('Mobile/Team/index'));
        }
        $teamFound = TeamFound::get($found_id);
        $teamFollow = $teamFound->teamFollow()->where('status', 1)->select();
        $teamActivity = $teamFound->teamActivity;
        $this->assign('teamFollow', $teamFollow);
        $this->assign('teamActivity', $teamActivity);
        $this->assign('teamFollow', $teamFollow);
        return $this->fetch();
    }

}