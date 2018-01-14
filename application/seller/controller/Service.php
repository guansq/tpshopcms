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
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2017-03-20
 */

namespace app\seller\controller;

use think\AjaxPage;
use think\Db;
use think\Page;
use app\seller\logic\OrderLogic;

class Service extends Base
{

    public function ask_list()
    {
        checkIsBack();
        return $this->fetch();
    }

    public function ajax_ask_list()
    {
        $model = M('goods_consult');
        $username = I('nickname', '', 'trim');
        $content = I('content', '', 'trim');
        $where = array('parent_id' => 0, 'store_id' => STORE_ID);
        if ($username) {
            $where['username'] = $username;
        }
        if ($content) {
            $where['content'] = ['like', '%' . $content . '%'];
        }
        $count = $model->where($where)->count();
        $Page = new AjaxPage($count, 16);
        //是否从缓存中读取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            //重置获取条件
            delIsBack();
        }

        $comment_list = $model->where($where)->order('add_time DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if (!empty($comment_list)) {
            $goods_id_arr = get_arr_column($comment_list, 'goods_id');
            $goods_list = M('Goods')->where("goods_id", "in", implode(',', $goods_id_arr))->getField("goods_id,goods_name");
        }
        $consult_type = array(0 => '默认咨询', 1 => '商品咨询', 2 => '支付咨询', 3 => '配送', 4 => '售后');
        cachePage($Page);
        $show = $Page->show();
        $this->assign('consult_type', $consult_type);
        $this->assign('goods_list', $goods_list);
        $this->assign('comment_list', $comment_list);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    public function ask_handle()
    {
        $type = I('post.type');
        $selected_id = I('post.selected/a');
        if (!in_array($type, array('del', 'show', 'hide')) || !$selected_id) {
            $this->error('异常操作');
        }
        $row = false;
        $selected_id = implode(',', $selected_id);
        if ($type == 'del') {
            //删除咨询
            $where = array(
                'id|parent_id' => ['IN', $selected_id],
                'store_id' => STORE_ID
            );
            $row = M('goods_consult')->where($where)->delete();
        }
        if ($type == 'show') {
            $row = M('goods_consult')->where(['id' => ['IN', $selected_id], 'store_id' => STORE_ID])->save(array('is_show' => 1));
        }
        if ($type == 'hide') {
            $row = M('goods_consult')->where(['id' => ['IN', $selected_id], 'store_id' => STORE_ID])->save(array('is_show' => 0));
        }
        if ($row !== false) {
            $this->success('操作完成');
        } else {
            $this->error('操作失败');
        }

    }

    /**
     * 换货维修申请列表
     */
    public function return_list(){
    	//搜索条件
    	$where['store_id'] = STORE_ID;
    	$where['type'] = array('gt',1);
    	$status = I('status');
    	if($status || $status == '0'){
    		$where['status'] = $status;
    	}
    	$order_sn = I('order_sn');
    	if($order_sn) $where['order_sn'] = $order_sn;
    	$begin = strtotime(I('add_time_begin'));
    	$end = strtotime(I('add_time_end'));
    	if($begin && $end){
    		$where['addtime'] = array('between',"$begin,$end");
    	}
    	$count = M('return_goods')->where($where)->count();
    	$Page  = new Page($count,20);
    	$show = $Page->show();
    	$list = M('return_goods')->where($where)->order("id desc")->limit("{$Page->firstRow},{$Page->listRows}")->select();
    	$goods_id_arr = get_arr_column($list, 'goods_id');
    	if(!empty($goods_id_arr))
    		$goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
    	$this->assign('goods_list',$goods_list);
    	$state = C('REFUND_STATUS');
    	$this->assign('list', $list);
    	$this->assign('state',$state);
    	$this->assign('page',$show);// 赋值分页输出
    	return $this->fetch();
    }
    
    
    /**
     * 退款申请列表
     */
    public function refund_list(){
    	$where['store_id'] = STORE_ID;
    	$where['type'] = array('lt',2);
    	$status = I('status');
    	if($status || $status=='0'){
    		$where['status'] = $status;
    	}
    	$order_sn = I('order_sn');
    	if($order_sn) $where['order_sn'] = $order_sn;
    	$begin = strtotime(I('add_time_begin'));
    	$end = strtotime(I('add_time_end'));
    	if($begin && $end){
    		$where['addtime'] = array('between',"$begin,$end");
    	}
    	$count = M('return_goods')->where($where)->count();
    	$Page  = new Page($count,20);
    	$show = $Page->show();
    	$list = M('return_goods')->where($where)->order("id desc")->limit("{$Page->firstRow},{$Page->listRows}")->select();
    	if(!empty($list)){
    		$goods_id_arr = get_arr_column($list, 'goods_id');
    		$user_id_arr =  get_arr_column($list, 'user_id');
    		$goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
    		$user_list = M('users')->where("user_id in (".implode(',', $user_id_arr).")")->getField('user_id,nickname');
    		$this->assign('goods_list',$goods_list);
    		$this->assign('user_list',$user_list);
    	}
    	$state = C('REFUND_STATUS');
    	$this->assign('list', $list);
    	$this->assign('state',$state);
    	$this->assign('page',$show);
    	return $this->fetch();
    }
    
    /**
     * 删除某个退换货申请
     */
    public function return_del(){
    	$id = I('get.id/d');
    	M('return_goods')->where("id = $id and store_id = ".STORE_ID)->delete();
    	$this->success('成功删除!');
    }
    
    /**
     * 换货操作
     */
    public function return_info()
    {
    	$id = I('id/d');
        $return_goods = M('return_goods')->where(array('id'=>$id,'store_id'=>STORE_ID))->find();
        $select_year = getTabByOrderId($return_goods['order_id']);
        empty($return_goods) && $this->error("参数有误");
    	if(IS_POST){
            $data = I('post.');
    		if($data['status'] == 1 || $data['status'] == -1){
    			$data['checktime'] = time();//审核换货申请
    			if($return_goods['is_receive'] == 0 && $data['status'] != -1) $data['status'] = 3;//未发货商品无需确认收货
    		}else{
    			$data['status'] = 4;//处理发货
    			$data['seller_delivery']['express_time'] = date('Y-m-d H:i:s');
    			$data['seller_delivery'] = serialize($data['seller_delivery']);
    			M('order_goods'.$select_year)->where(array('order_id'=>$return_goods['order_id'],'goods_id'=>$return_goods['goods_id']))->save(array('is_send'=>2));
    		}
    		M('return_goods')->where(array('id'=>$data['id'],'store_id'=>STORE_ID))->save($data);
    		$this->success('操作成功!',U('Service/return_list'));exit;
    	}
    	if($return_goods['imgs']) $return_goods['imgs'] = explode(',', $return_goods['imgs']);
    	if($return_goods['delivery']) $return_goods['delivery'] = unserialize($return_goods['delivery']);
    	if($return_goods['seller_delivery']) $return_goods['seller_delivery'] = unserialize($return_goods['seller_delivery']);
    	$user = get_user_info($return_goods['user_id']);
    	$order_goods = M('order_goods'.$select_year)->where("order_id ={$return_goods['order_id']} and goods_id = {$return_goods['goods_id']} and spec_key = '{$return_goods['spec_key']}'")->find();
    	$this->assign('user',$user);
    	$order = M('order'.$select_year)->where(array('order_id'=>$return_goods['order_id']))->find();
    	$this->assign('order',$order);//退货订单信息
    	$this->assign('order_goods',$order_goods);//退货订单商品
    	$this->assign('return_goods',$return_goods);// 退换货申请信息
    	$this->assign('state',C('REFUND_STATUS'));
    	return $this->fetch();
    }

    /**
     * 退货操作
     */
    public function refund_info(){
        $id = I('id');
        $return_goods = M('return_goods')->where(array('id'=>$id,'store_id'=>STORE_ID))->find();
        $select_year = getTabByOrderId($return_goods['order_id']);
        $order = M('order'.$select_year)->where(['order_id'=>$return_goods['order_id']])->find();
        empty($return_goods) && $this->error("参数有误");
    	if(IS_POST){
    		$data = I('post.');
    		$data['checktime'] = time();
    		if($data['status'] == 1){
                $orderLogic = new OrderLogic();
    			if($return_goods['is_receive'] == 0) $data['status'] = 3;//未发货商品无需确认收货
    		}
    		
    		if($data['refund_money'] != $return_goods['refund_money']){
    			$data['gap'] = $return_goods['refund_money'] - $data['refund_money'];//退款差额
    		}
    		M('return_goods')->where(array('id'=>$data['id'],'store_id'=>STORE_ID))->save($data);
    		$this->success('操作成功!',U('Service/refund_list'));
    		exit;
    	}
    	if($return_goods['imgs']) $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        if($return_goods['delivery']) {
    		$return_goods['delivery'] = unserialize($return_goods['delivery']);
    	}
    	$user = get_user_info($return_goods['user_id']);
    	$order_goods = M('order_goods'.$select_year)
            ->where("order_id ={$return_goods['order_id']} and goods_id = {$return_goods['goods_id']} and spec_key = '{$return_goods['spec_key']}'")
            ->find();
    	$this->assign('user',$user);
    	$this->assign('order',$order);//退货订单信息
    	$this->assign('order_goods',$order_goods);//退货订单商品
    	$this->assign('return_goods',$return_goods);// 退换货申请信息
    	$this->assign('state',C('REFUND_STATUS'));
    	return $this->fetch();
    }
    
    public function confirm_receive(){
    	$id = I('id');
    	$return_goods = M('return_goods')->where(array('id'=>$id,'store_id'=>STORE_ID))->find();
    	if($return_goods){
    		M('return_goods')->where(array('id'=>$id,'store_id'=>STORE_ID))->save(array('status'=>3,'receivetime'=>time()));
    		$refer = $return_goods['type']>1 ? U('Service/return_list') : U('Service/refund_list');
    		$this->success('操作成功!',$refer);exit;
    	}else{
    		$this->error("参数有误");
    	}
    }

    public function complain_list()
    {
		$complain_state = I('complain_state');
		$map = array();
		$map['store_id'] = STORE_ID;
		if($complain_state){
			$map['complain_state'] = $complain_state;
		}
		$begin = strtotime(I('add_time_begin'));
		$end = strtotime(I('add_time_end'));
		if($begin && $end){
			$map['complain_time'] = array('between',"$begin,$end");
		}
        $type = I('type');
        $key = trim(I('key'));
        if(!empty($key)){
            $map["$type"]=$key;
        }
		$count = M('complain')->where($map)->count();
		$page = new Page($count);
		$lists  = M('complain')->where($map)->order('complain_time desc')->limit($page->firstRow.','.$page->listRows)->select();
		if(!empty($lists)){
			$goods_id_arr = get_arr_column($lists, 'order_goods_id');
			$goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
			$this->assign('goodsList',$goodsList);
		}
		$this->assign('page',$page->show());
		$this->assign('pager',$page);
		$this->assign('lists',$lists);
		$complain_state = array(1=>'待处理',2=>'对话中',3=>'待仲裁',4=>'已完成');
		$this->assign('state',$complain_state);
        return $this->fetch();
    }
    
    public function complain_info(){
    	$complain_id = I('complain_id/d');
    	$complain = M('complain')->where(array('complain_id'=>$complain_id,'store_id'=>STORE_ID))->find();
        $select_year = getTabByOrderId($complain['order_id']);
    	$order = M('order'.$select_year)->where(array('order_id'=>$complain['order_id']))->find();
    	$order_goods = M('order_goods'.$select_year)->where(array('order_id'=>$complain['order_id'],'goods_id'=>$complain['order_goods_id']))->find();
		$ids[] = $order['province'];
		$ids[] = $order['city'];
		$ids[] = $order['district'];
		if(!empty($ids)){
			$regionLits = M('region')->where("id", "in" , implode(',', $ids))->getField("id,name");
			$this->assign('regionLits',$regionLits);
		}
    	if(!empty($complain['complain_pic'])){
    		$complain['complain_pic'] = unserialize($complain['complain_pic']);
    	}
    	if(!empty($complain['appeal_pic'])){
    		$complain['appeal_pic'] = unserialize($complain['appeal_pic']);
    	}
    	$this->assign('complain',$complain);
    	$this->assign('order',$order);
    	$this->assign('order_goods',$order_goods);
    	return $this->fetch();
    }
    
    public function complain_appeal(){
    	if(IS_POST){
    		$data = I('post.');
    		$complain = M('complain')->where(array('complain_id'=>$data['complain_id'],'store_id'=>STORE_ID))->find();
    		if(!$complain) $this->ajaxReturn(['status'=>0,'msg'=>'非法操作或参数有误']);
    		if(is_array($data['appeal_pic'])){
    			$data['appeal_pic'] = serialize($data['appeal_pic']);
    		}
    		$data['appeal_time'] = time();
    		$data['complain_state'] = 2;
    		M('complain')->where(array('complain_id'=>$data['complain_id'],'store_id'=>STORE_ID))->save($data);
            $this->ajaxReturn(['status'=>1,'msg'=>'申诉成功','url'=>U('Service/complain_list')]);
    	}
    }
    
    public function get_complain_talk(){
    	$complain_id = I('complain_id/d');
    	$complain_info = M('complain')->where(array('complain_id'=>$complain_id,'store_id'=>STORE_ID))->find();
    	$complain_info['member_status'] = 'accused';
    	$complain_talk_list = M('complain_talk')->where(array('complain_id'=>$complain_id))->order('talk_id desc')->select();
    	$talk_list = array();
    	if(!empty($complain_talk_list)){
    		foreach($complain_talk_list as $i=>$talk) {
    			$talk_list[$i]['css'] = $talk['talk_member_type'];
    			$talk_list[$i]['talk'] = date("Y-m-d H:i:s",$talk['talk_time']);
    			switch($talk['talk_member_type']){
    				case 'accuser':
    					$talk_list[$i]['talk'] .= '投诉人';
    					break;
    				case 'accused':
    					$talk_list[$i]['talk'] .= '被投诉店铺';
    					break;
    				case 'admin':
    					$talk_list[$i]['talk'] .= '管理员';
    					break;
    				default:
    					$talk_list[$i]['talk'] .= '未知';
    			}
    			if(intval($talk['talk_state']) === 2) {
    				$talk['talk_content'] = '<该对话被管理员屏蔽>';
    			}
    			$talk_list[$i]['talk'].= '('.$talk['talk_member_name'].')说:'.$talk['talk_content'];
    		}
    	}
    	echo json_encode($talk_list);
    }
    
    public function publish_complain_talk(){
    	$complain_id = I('complain_id/d');
    	$complain_talk = trim(I('complain_talk'));
    	$talk_len = strlen($complain_talk);
    	if($talk_len>0 && $talk_len<255){
    		$complain_info = M('complain')->where(array('complain_id'=>$complain_id,'store_id'=>STORE_ID))->find();
    		$complain_state = intval($complain_info['complain_state']);
    		$param = array();
    		$param['complain_id'] = $complain_id;
    		$param['talk_member_id'] = $complain_info['user_id'];
    		$param['talk_member_name'] = $complain_info['user_name'];
    		$param['talk_member_type'] = 'accused';
    		$param['talk_content'] = $complain_talk;
    		$param['talk_state'] = 1;
    		$param['talk_admin'] = 0;
    		$param['talk_time'] = time();
    		if(M('complain_talk')->add($param)){
    			echo json_encode('success');
    		}else{
    			echo json_encode('error2');
    		}
    	}else{
    		echo json_encode('error1');
    	}
    }
}