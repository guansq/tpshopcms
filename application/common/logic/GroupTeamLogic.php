<?php

/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2016-10-09
 */

namespace app\common\logic;

use think\Model;
/**
 * 拼团活动
 * Class TeamActivity
 */
class GroupTeamLogic extends Model
{
	//编辑拼团活动
	public function saveTeamActivity($data){
		if(!empty($data['ladder_amount'])){
			foreach ($data['ladder_amount'] as $k=>$val){
				$data['ladder'][$val] = $data['ladder_price'][$k];
			}
			$data['ladder'] = serialize($data['ladder']);
		}
		if(empty($data['id'])){
			$r = M('team_activity')->add($data);
			M('goods')->where(array('goods_id'=>$data['goods_id']))->save(array('prom_type'=>4,'prom_id'=>$r));
		}else{
			$r = M('team_activity')->where(array('id'=>$data['id']))->save($data);
		}
		return $r;
	}
	
	//团长开团
	public function buildTeamOrder($order_id,$act_info){
		$user = session('user');
		$res = $this->check_team_found($act_info,$user['user_id']);
		if($res['status'] == 1){			
			$act_info['start_time'] = time();
			$act_info['end_time'] = $act_info['start_time']+$act_info['time_limit']*3600;
			$act_info['order_id'] = $order_id;
			$act_info['user_id']  = $user['user_id'];
			$act_info['nickname']  = $user['nickname'];
			$act_info['head_pic']  = $user['head_pic'];
			$act_info['join']  = 1;
			foreach ($act_info['ladder'] as $k=>$v){
				$act_info['need'] = $k;
				$act_info['price'] = $v;
			}
			if(M('team_found')->add($act_info)){
				return array('status'=>1,'msg'=>'开团成功');
			}else{
				return array('status'=>0,'msg'=>'开团失败');
			}
		}else{
			return $res;
		}
	}
	
	//是否满足开团条件检测
	public function checkTeamFound($act_info,$user_id){
		//需要购买才能开团
		if($act_info['need_buy'] == 1){
			$is_buy = M('order')->alias('ro')->field('ro.user_id,g.goods_id')
    		->join('left join __PREFIX__order_goods as g ON ro.order_id = g.order_id')
			->where(array('ro.pay_status'=>1,'g.goods_id'=>$act_info['goods_id']))->select();
			if(!$is_buy) return array('status'=>-1,'msg'=>'需要购买该商品之后才能开团');
		}
		if($act_info['buy_limit']>0){
			$buy_num = M('order')->alias('ro')->field('ro.user_id,g.goods_id')
			->join('left join __PREFIX__order_goods as g ON ro.order_id = g.order_id')
			->where(array('ro.pay_status'=>1,'g.goods_id'=>$act_info['goods_id']))->sum('g.goods_num');
			if($buy_num>$act_info['buy_limit']){
				return array('status'=>-1,'msg'=>'该拼团商品您已购买达到了'.$act_info['buy_limit'].'件');
			}
		}
		return array('status'=>1);
	}
	
	//用户参与拼团
	public function followJoinTeam($order_id,$found_info){
		$team = M('team_found')->where(array('team_id'=>$found_info['team_id']))->find();
		if($team['end_time']<time()){
			return false;
		}else{
			if($team['join']<$team['need']){
				$user = session('user');
				$follew['follow_time'] = time();
				$follew['order_id'] = $order_id;
				$follew['follow_user_id'] = $user['user_id'];
				$follew['follow_user_nickname'] = $user['nickname'];
				$follew['follow_user_head'] = $user['head_pic'];
				$follew['leader_id'] = $found_info['user_id'];
				$follew['team_id'] = $found_info['team_id'];
				$follew['act_id'] = $found_info['act_id'];
				M('team_follow')->add($follew);
				if($team['join']+1 == $team['need']){
					$data['status'] = 1;
				}
				$data['join'] = $team['join']+1;
				return M('team_found')->where(array('team_id'=>$found_info['team_id']))->save($data);
			}else{
				return false;	
			}
		}
	}
	
	//获取最新拼团订单
	public function getLastFollow(){
		return M('team_follow')->order('id desc')->limit(1)->find();
	}
	
	//自动更新拼团状态
	public function autoUpdateTeam(){
		//已达到拼团最后期限的拼团列表
		$where = "where need>join and end_time<".time();
		$fail_team = M('team_found')->where($where)->order('team_id')->getFiled('team_id,order_id');
		if($fail_team){
			M('team_found')->where($where)->save(array('status'=>2));
			foreach ($fail_team as $k=>$val){
				$order_id_arr = array($val['order_id']);
				$user_id_arr = array($val['user_id']);
				$follow = M('team_follow')->where(array('team_id'=>$val['team_id']))->getField('follow_user_id,order_id');
				if(!empty($follow)){
					$follow_user = array_keys($follow);
					$follow_order = array_values($follow);
					$user_id_arr = array_push($follow_user, $val['user_id']);
					$order_id_arr = array_push($follow_order, $val['order_id']);
				}
				//拼团失败，修改拼团订单状态，当做取消订单处理
				M('order')->where("order_id in (".implode(',', $order_id_arr).")")->save(array('order_status'=>3,'pay_sataus'=>0));
				//拼团失败，退款到用户余额
				M('user')->where("user_id in (".implode(',', $user_id_arr).")")->setInc('user_money',$val['price']);
			}
		}
	}
	
	//获取跟团用户列表
	public function getFollowList(){
		$team_id = I('team_id');
		if($team_id){
			return M('team_follow')->where(array('team_id'=>$team_id))->select();
		}else{
			return false;
		}
	}
	
	//抽奖团抽奖
	public function drawLottery($act_id){
		$activity = M('team_activity')->where(array('act_id'=>$act_id))->find();
		$lottery_team = M('team_found')->where(array('group_type'=>3,'status'=>1))->getField('user_id,team_id');
		$lottery_team_id = array_values($lottery_team);
		$team_leader = array_keys($lottery_team);//团长集合
		$follow_user = M('follow')->where("team_id in (".implode(',', $lottery_team_id).")")->getField('user_id');//团员集合
		$lottery_arr = array_merge($team_leader,$follow_user);//所有参与抽奖会员集合
		$lottery_user = array_rand($follow_user,$activity['stock_limit']);
		//团长免抽，直接中奖
		M('team_follow')->where("follow_user_id in (".implode(',', $lottery_user).")")->save(array('is_win'=>1));
	}
	
	//佣金团团长返佣
	public function leaderRebate($team_found){
		$act_info = M('team_activity')->where(array('act_id'=>$team_found['act_id']))->find();
		$rebate_money = $team_found['need']*$act_info['bonus'];
		accountLog($team_found['user_id'],$rebate_money,0,'佣金团'.$act_info['title'].'团长获取佣金');
	}
	
}

