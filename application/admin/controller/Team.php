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

namespace app\admin\controller;

use app\common\model\TeamActivity;
use think\Loader;
use think\Db;
use think\Page;

class Team extends Base
{
	public function index()
	{
		$act_name = input('act_name');
		$team_where = [];
		if ($act_name) {
			$team_where['act_name'] = ['like', '%' . $act_name . '%'];
		}
		$TeamActivity = new TeamActivity();
		$count = $TeamActivity->count();
		$Page = new Page($count, 10);
		$list = $TeamActivity->append(['team_type_desc','time_limit_hours','status_desc'])->with('store')->where($team_where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('page', $Page);
		$this->assign('list', $list);
		return $this->fetch();
	}

	public function info()
	{
		$team_id = input('team_id');
		if (empty($team_id)) {
			$this->error('非法操作');
		}
		$TeamActivity = new TeamActivity();
		$teamActivity = $TeamActivity->append(['time_limit_hours'])->with('specGoodsPrice,goods,store')->find($team_id);
		if (empty($teamActivity)) {
			$this->error('该数据不存在或已被删除');
		}
		$this->assign('teamActivity', $teamActivity);
		return $this->fetch();
	}

	/**
	 * 审核
	 */
	public function examine(){
		$team_id = input('team_id');
		$status = input('status');
		if (empty($team_id) || empty($status)) {
			$this->ajaxReturn(['status' =>0,'msg' => '参数有误','result' => '']);
		}
		$teamActivity = TeamActivity::get($team_id);
		if($teamActivity){
			$teamActivity->status = $status;
			$row = $teamActivity->save();
			if($row !== false){
				$this->ajaxReturn(['status' =>1,'msg' => '操作成功','result' => '']);
			}else{
				$this->ajaxReturn(['status' =>0,'msg' => '操作失败','result' => '']);
			}
		}else{
			$this->ajaxReturn(['status' =>0,'msg' => '没有找到数据','result' => '']);
		}
	}
}