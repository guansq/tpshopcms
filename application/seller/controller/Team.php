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
 * 专题管理
 * Date: 2016-06-09
 */

namespace app\seller\controller;

use app\common\model\TeamActivity;
use think\Loader;
use think\Db;
use think\Page;

class Team extends Base
{
	public function index()
	{
		$TeamActivity = new TeamActivity();
		$count = $TeamActivity->count();
		$Page = new Page($count, 10);
		$show = $Page->show();
		$list = $TeamActivity->append(['team_type_desc','time_limit_hours','status_desc'])->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('page', $show);
		$this->assign('list', $list);
		return $this->fetch();
	}

	public function info()
	{
		$team_id = input('team_id');
		if ($team_id) {
			$TeamActivity = new TeamActivity();
			$teamActivity = $TeamActivity->append(['time_limit_hours'])->with('specGoodsPrice,goods')->where(['team_id'=>$team_id,'store_id'=>STORE_ID])->find();
			if(empty($teamActivity)){
				$this->error('非法操作');
			}
			$this->assign('teamActivity', $teamActivity);
		}
		return $this->fetch();
	}
	
	public function save(){
		$data = input('post.');
		$data['time_limit'] = $data['time_limit'] * 60 * 60;
		$teamValidate = Loader::validate('Team');
		if (!$teamValidate->batch()->check($data)) {
			$this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => $teamValidate->getError()]);
		}
		if($data['team_id']){
			$teamActivity = TeamActivity::get(['team_id' => $data['team_id'], 'store_id' => STORE_ID]);
			if(empty($teamActivity)){
				$this->ajaxReturn(array('status' => 0, 'msg' => '非法操作','result'=>''));
			}
		}else{
			$teamActivity = new TeamActivity();
		}
		$teamActivity->data($data, true);
		$teamActivity['store_id'] = STORE_ID;
		$row = $teamActivity->allowField(true)->save();
		if($row !== false){
			$this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'result' => '']);
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => '']);
		}
	}

	public function delete(){
		$team_id = input('team_id');
		if($team_id){
			$order_goods = Db::name('order_goods')->where(['prom_type' => 6, 'prom_id' => $team_id])->find();
			if($order_goods){
				$this->ajaxReturn(['status' => 0, 'msg' => '该活动有订单参与不能删除!', 'result' => '']);
			}
			$teamActivity = TeamActivity::get(['store_id'=>STORE_ID,'team_id'=>$team_id]);
			if($teamActivity){
				$row = $teamActivity->delete();
				if($row !== false){
					$this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
				}else{
					$this->ajaxReturn(['status' => 0, 'msg' => '删除失败', 'result' => '']);
				}
			}else{
				$this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
			}
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
		}
	}
}