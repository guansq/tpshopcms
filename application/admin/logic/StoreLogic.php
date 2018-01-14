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
 * Date: 2016-06-09
 */


namespace app\admin\logic;
use think\Model;
use think\Db;

class StoreLogic extends Model
{    
    
    /**
     * 获取指定店铺信息
     * @param int $store_id 用户UID
     * @param bool $relation 是否关联查询
     * @return mixed 找到返回数组
     */
    public function detail($store_id, $relation = true)
    {
        $user = D('Store')->where(array('store_id' => $store_id))->relation($relation)->find();
        return $user;
    }
    
    /**
     * 修改店铺信息
     * @param int $uid
     * @param array $data
     * @return array

    public function update($store_id = 0, $data = array())
    {
        $db_res = D('User')->where(array("user_id" => $store_id))->data($data)->save();
        if ($db_res) {
            return array(1, "用户信息修改成功");
        } else {
            return array(0, "用户信息修改失败");
        }
    }
     */
    
    /**
     * 添加店铺
     * @param array $store
     * @return array
     */
    public function addStore($store)
    {
    	Db::startTrans();
		//添加店铺信息
		$store_id = Db::name('store')->add($store);
        $store_extend_count = Db::name('store_extend')->where(['store_id'=>$store_id])->count();
        if($store_extend_count == 0){
            Db::name('store_extend')->add(array('store_id'=>$store_id));
        }
		if($store['is_own_shop'] == 1){
			//添加驻外店铺
			$apply = array('seller_name'=>$store['seller_name'],'user_id'=>$store['user_id'],
					'store_name'=>$store['store_name'],'company_province'=>0,'sc_bail'=>0,'apply_state'=>1,
			);
			M('store_apply')->add($apply);
		}
		//添加店铺管理员
		$seller = array('seller_name'=>$store['seller_name'],'store_id'=>$store_id,'user_id'=>$store['user_id'],'is_admin'=>1);
		$seller_id = Db::name('seller')->add($seller);
		if( $store_id && $seller_id){
			Db::commit();
            $this->store_init_shipping($store_id);//初始化店铺物流
			adminLog('新增店铺：'.$store['store_name']);
			return true;
		}else{
			Db::rollback();
			return false;
		}	
    }
    
    /**
     * 改变用户密码
     * @param $store_id
     * @param $oldPassword
     * @param $newPassword
     * @return string
     */
    public function changePassword($store_id, $oldPassword, $newPassword)
    {
    
        $user = $this->detail($store_id);
        if ($user['user_pass'] != encrypt($oldPassword)) {
            return array(0, "原用户密码不正确");
        }
        $data['user_id'] = $store_id;
        $data['user_pass'] = encrypt($newPassword);
    
        if (D('User')->where(array("user_id" => $store_id))->data($data)->save()) {
            return array(1, "密码修改成功", U("Admin/login/logout"));
        } else {
            return array(0, "密码修改失败");
        }
    
    }
    
    
    /**
     * 生成新的Hash
     * @param $authInfo
     * @return string
     */
    public function genHash(&$authInfo)
    {
        $User = D('User', 'Logic');    
        $condition['user_id'] = $authInfo['user_id'];
        $session_code = encrypt($authInfo['user_id'] . $authInfo['user_pass'] . time());
        $User->where($condition)->setField('user_session', $session_code);
    
        return $session_code;
    }
    
    public function getAuth($role_id)
    {
    	return $role_id;
    }

    /**
     * 自动给商家结算
     * @param $store_id
     * @return bool
     */
    public function auto_transfer($store_id){
        // 确认收货多少天后 自动结算给 商家
        $today_time = time();
        $auto_transfer_date = tpCache('shopping.auto_transfer_date');
        $auto_transfer_date = $auto_transfer_date * (60 * 60 * 24); // 1天的时间戳        
        $time = time() - $auto_transfer_date; // N天以前的时间戳
        $sql = "select order_id from __PREFIX__order where store_id = $store_id and order_status in(2,4) and confirm_time <  $time and order_statis_id = 0";
        
        $list = Db::query($sql);
        
        if(empty($list)) // 没有数据直接跳出
            return false;
        
        $data = array(
            'start_date' => $today_time - $auto_transfer_date, // 结算开始时间
            'end_date'   => time(), // 结算截止时间
            'create_date'=>  time(), // 记录创建时间            
            'store_id'   => $store_id, // 店铺id
        );      
        foreach($list as $key => $val)
        {               
            $order_settlement = order_settlement($val['order_id']); // 调用全局结算方法
            $data['order_totals'] += $order_settlement['goods_amount'];// 订单商品金额    
            $data['shipping_totals'] += $order_settlement['shipping_price'];// 运费    
            $data['return_integral'] +=  $order_settlement['return_integral'];// 退还积分    
            $data['commis_totals'] +=  $order_settlement['settlement'];// 平台抽成
            $data['give_integral'] +=  $order_settlement['give_integral'];// 送出积分金额
            $data['result_totals'] +=  $order_settlement['store_settlement'];// 本期应结
            $data['order_prom_amount'] +=  $order_settlement['order_prom_amount'];// 优惠价
            $data['coupon_price'] +=  $order_settlement['coupon_price'];// 优惠券抵扣
            $data['distribut'] +=  $order_settlement['distribut'];// 分销金额  
            $data['integral'] +=  $order_settlement['integral'];//订单使用积分
            $data['return_totals'] += $order_settlement['return_totals'];//若订单商品退款，退还金额
            $data['refund_integral'] += $order_settlement['refund_integral'];//若订单商品退款，退还积分
            $order_id_arr[] = $val['order_id'];
        }              
        $order_statis_id = M('order_statis')->add($data); // 添加一笔结算统计                
        M('order')->where("order_id in (".  implode(',', $order_id_arr).")")->save(array('order_statis_id' =>$order_statis_id)); // 标识为已经结算
        // 给商家加钱 记录日志
        storeAccountLog($store_id,$data['result_totals'],$data['result_totals'] * -1,'平台订单结算');        
    }

    /**
     * 添加店铺时，默认安装一个物流插件
     * @param $store_id
     */
    public function store_init_shipping($store_id){
        $default_shipping_code = 'shunfeng';
        $store_shipping_is_on = M('shipping_area')->where(array('store_id'=>$store_id,'is_close'=>1))->find();
        if(empty($store_shipping_is_on)){
            //平台规定店铺初始，物流默认顺丰物流
            $store_shipping_is_shunfen = M('shipping_area')->where(array('store_id'=>$store_id,'shipping_code'=>$default_shipping_code))->find();
            if(empty($store_shipping_is_shunfen)){
                M('shipping_area')->where(array('store_id'=>$store_id))->save(array('is_default'=>0));
                $config['first_weight'] = '1000'; // 首重
                $config['second_weight'] = '2000'; // 续重
                $config['money'] = '12';
                $config['add_money'] = '2';
                $add['shipping_area_name'] = '全国其他地区';
                $add['shipping_code'] = $default_shipping_code;
                $add['config'] = serialize($config);
                $add['is_default'] = 1;
                $add['is_close'] = 1;
                $add['store_id'] = $store_id;
                M('shipping_area')->add($add);
            }else{
                M('shipping_area')->where(array('shipping_area_id'=>$store_id))->data(array('is_close' => 1,'is_default'=>1))->save();
            }
        }
    }
}