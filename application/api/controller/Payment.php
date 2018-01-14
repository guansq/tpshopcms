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

class Payment extends Base 
{
    /**
     * app端发起支付宝,支付宝返回服务器端,  返回到这里
     * http://www.tp-shop.cn/index.php/Api/Payment/alipayNotify
     */
    public function alipayNotify()
    {
        $paymentPlugin = M('Plugin')->where("code='alipay' and  type = 'payment' ")->find(); // 找到支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化        

        require_once("plugins/payment/alipay/app_notify/alipay.config.php");
        require_once("plugins/payment/alipay/app_notify/lib/alipay_notify.class.php");

        $alipay_config['partner'] = $config_value['alipay_partner'];//合作身份者id，以2088开头的16位纯数字        

        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        //验证成功
        if ($verify_result) {                           
            $order_sn = $out_trade_no = trim($_POST['out_trade_no']); //商户订单号
            $trade_no = $_POST['trade_no'];//支付宝交易号
            $trade_status = $_POST['trade_status'];//交易状态
            
			
		//用户在线充值
		if (stripos($order_sn, 'recharge') !== false)
			$order_amount = M('recharge')->where(['order_sn' => $order_sn, 'pay_status' => 0])->value('account');
		else			
            $order_amount = M('order')->where(['master_order_sn'=>$order_sn])->whereOr(['order_sn'=>$order_sn])->sum('order_amount');
            if($order_amount!=$_POST['price'])
                exit("fail"); //验证失败

            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                update_pay_status($order_sn); // 修改订单支付状态                
            } elseif ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                update_pay_status($order_sn); // 修改订单支付状态                
            }               
            M('order')->where('order_sn', $order_sn)->whereOr('master_order_sn',$order_sn)->save(array('pay_code'=>'alipay','pay_name'=>'app支付宝'));
            echo "success"; //  告诉支付宝支付成功 请不要修改或删除               
        } else {
            echo "fail"; //验证失败         
        }
    }
 
    public function alipay_sign()
    {
        $orderSn = input('post.order_sn', '');
        $user = session('user');

        if (strpos($orderSn, 'recharge') === 0 || $orderSn === '') {
            //充值流程
            $orderAmount = input('account/f', 0);
            if ($orderAmount <= 0) {
                $this->ajaxReturn(['status'=>-1, 'msg'=>'充值金额不能为'.$orderAmount]);
            }
            if ($orderSn) {
                $order = M('recharge')->where("order_sn", $orderSn)->find();
                if (!$order) {
                    $this->ajaxReturn(['status'=>-1, 'msg'=>'该充值订单不存在']);
                }
                M('recharge')->where(['order_sn' => $orderSn, 'user_id' => $user['user_id']])->save(['account' => $orderAmount]);
            } else {
                $orderSn = 'recharge'.get_rand_str(10,0,1);
                $order['user_id'] = $user['user_id'];
                $order['nickname'] = $user['nickname'];
                $order['account'] = $orderAmount;
                $order['order_sn'] = $orderSn;
                $order['pay_name'] = 'app支付宝';
                $order['ctime'] = time();
                M('recharge')->add($order);
            }
        } else {
            //支付流程
            $order = M('order')->alias('o')->field('o.order_amount')
                    ->where('o.order_sn|o.master_order_sn', $orderSn)->select();
            if (!$order) {
                $this->ajaxReturn(['status' => -1, 'msg' => '订单不存在']);
            }
            // 所有商品单价相加
            $orderAmount = array_reduce($order, function ($sum, $val) {
                return $sum + $val['order_amount'];
            }, 0);
        }
        
        if (!function_exists('openssl_sign')) {
            $this->ajaxReturn(['status' => -1, 'msg' => '请先启用php的openssl扩展']);
        }
        
        $paymentPlugin = M('plugin')->where(['code' => 'alipay', 'type' => 'payment'])->find();
        $cfgVal = unserialize($paymentPlugin['config_value']); // 配置反序列化
        if (!$cfgVal || empty($cfgVal['alipay_partner']) || empty($cfgVal['alipay_private_key']) || empty($cfgVal['alipay_account'])) {
            $this->ajaxReturn(['status' => -1, 'msg' => '支付宝重要配置不能为空！']);
        }
        
        $storeName = M('config')->where('name', 'store_name')->getField('value');
        
        include_once(PLUGIN_PATH.'payment/alipay/app_notify/lib/alipay_sign.class.php');

        $sign = new \AlipaySign;
        $sign->partner = $cfgVal['alipay_partner'];
        $sign->rsaPrivateKey = $cfgVal['alipay_private_key'];
        $sign->seller_id = $cfgVal['alipay_account'];
        $sign->notifyUrl = SITE_URL.'/index.php/Api/Payment/alipayNotify';
        $result = $sign->execute($storeName, $storeName, $orderAmount, $orderSn);
        
		$this->ajaxReturn(['status' => 1, 'msg' => '签名成功', 'result' => $result]);
    }
    
}
