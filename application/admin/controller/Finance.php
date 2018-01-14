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
 * Author: IT宇宙人      
 * 
 * Date: 2016-03-09
 */

namespace app\admin\controller;
use think\Page;
use think\Db;

class Finance extends Base {
    
    /*
     * 初始化操作
     */
    public function _initialize() {
       parent::_initialize();
    }    
 
    /**
     *  店家转账汇款记录
     */
    public function store_remittance(){
    	$status = I('status',1);
    	$this->assign('status',$status);
		$this->get_store_withdrawals($status);
        return $this->fetch();
    }
    /**
     *  转账汇款记录
     */
    public function remittance(){
    	$status = I('status',1);
    	$this->assign('status',$status);
    	$this->get_withdrawals_list($status);
        return $this->fetch();
    }

    /**
     * 提现申请记录
     */
    public function withdrawals()
    {
    	$this->get_withdrawals_list();
        return $this->fetch();
    }
    
    public function get_withdrawals_list($status=''){
    	$user_id = I('user_id/d');
    	$realname = I('realname');
    	$bank_card = I('bank_card');
    	$create_time = I('create_time');
    	$create_time = str_replace("+"," ",$create_time);
    	$create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
    	$create_time3 = explode(' - ',$create_time2);
    	$this->assign('start_time',$create_time3[0]);
    	$this->assign('end_time',$create_time3[1]);
    	$where['w.create_time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
    	$status = $status == '' ? I('status') : $status;
    	if($status !== ''){
    		$where['w.status'] = $status;
    	}
    	$user_id && $where['u.user_id'] = $user_id;
    	$realname && $where['w.realname'] = array('like','%'.$realname.'%');
    	$bank_card && $where['w.bank_card'] = array('like','%'.$bank_card.'%');
    	$export = I('export');
    	if($export == 1){
    		$strTable ='<table width="500" border="1">';
    		$strTable .= '<tr>';
    		$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">申请人</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">提现金额</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行名称</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行账号</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">开户人姓名</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请时间</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">提现备注</td>';
    		$strTable .= '</tr>';
    		$remittanceList = Db::name('withdrawals')->alias('w')->field('w.*,u.nickname')->join('__USERS__ u', 'u.user_id = w.user_id', 'INNER')->where($where)->order("w.id desc")->select();
    		if(is_array($remittanceList)){
    			foreach($remittanceList as $k=>$val){
    				$strTable .= '<tr>';
    				$strTable .= '<td style="text-align:center;font-size:12px;">'.$val['nickname'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['money'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].'</td>';
    				$strTable .= '<td style="vnd.ms-excel.numberformat:@">'.$val['bank_card'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['realname'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i:s',$val['create_time']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['remark'].'</td>';
    				$strTable .= '</tr>';
    			}
    		}
    		$strTable .='</table>';
    		unset($remittanceList);
    		downloadExcel($strTable,'remittance');
    		exit();
    	}
    	$count = Db::name('withdrawals')->alias('w')->join('__USERS__ u', 'u.user_id = w.user_id', 'INNER')->where($where)->count();
    	$Page  = new Page($count,20);
    	$list = Db::name('withdrawals')->alias('w')->field('w.*,u.nickname')->join('__USERS__ u', 'u.user_id = w.user_id', 'INNER')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('create_time',$create_time2);
    	$show  = $Page->show();
    	$this->assign('show',$show);
    	$this->assign('list',$list);
    	$this->assign('pager',$Page);
    	C('TOKEN_ON',false);
    }
    /**
     * 商家提现申请记录
     */
    public function store_withdrawals()
    {
		$this->get_store_withdrawals(null);
        return $this->fetch();
    }
    
    public function get_store_withdrawals($status){
    	$store_id = I('store_id');
    	$realname = I('realname');
    	$bank_card = I('bank_card');
    	$create_time = I('create_time');
    	
    	$create_time = str_replace("+"," ",$create_time);
    	$create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
    	$create_time3 = explode(' - ',$create_time2);
    	$this->assign('start_time', $create_time3[0]);
    	$this->assign('end_time', $create_time3[1]);
    	$where['sw.create_time'] =  array(array('gt', strtotime($create_time3[0])), array('lt', strtotime($create_time3[1])));
    	$store_id && $where['sw.store_id'] = $store_id;
        $status = empty($status) ? I('status') : $status;
//    	if($status === '0' || $status > 0) {
    		$where['sw.status'] = $status;
//    	}
    	$bank_card && $where['sw.bank_card'] = array('like','%'.$bank_card.'%');
    	$realname && $where['sw.realname'] = array('like','%'.$realname.'%');
    	$export = I('export');
    	if($export == 1){
    		$strTable ='<table width="500" border="1">';
    		$strTable .= '<tr>';
    		$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">申请人</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">提现金额</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行名称</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行账号</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">开户人姓名</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请时间</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">提现备注</td>';
    		$strTable .= '</tr>';
    		$remittanceList = Db::name('store_withdrawals')->alias('sw')->field('sw.*,s.store_name')->join('__STORE__ s','s.store_id = sw.store_id', 'INNER')->where($where)->order("sw.id desc")->select();
    		if(is_array($remittanceList)){
    			foreach($remittanceList as $k=>$val){
    				$strTable .= '<tr>';
    				$strTable .= '<td style="text-align:center;font-size:12px;">'.$val['store_name'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['money'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].'</td>';
    				$strTable .= '<td style="vnd.ms-excel.numberformat:@">'.$val['bank_card'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['realname'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i:s',$val['create_time']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['remark'].'</td>';
    				$strTable .= '</tr>';
    			}
    		}
    		$strTable .='</table>';
    		unset($remittanceList);
    		downloadExcel($strTable,'remittance');
    		exit();
    	}
    	$count = Db::name('store_withdrawals')->alias('sw')->field('sw.id')->join('__STORE__ s','s.store_id = sw.store_id', 'INNER')->where($where)->count();
    	$Page  = new Page($count,20);
    	$list = Db::name('store_withdrawals')->alias('sw')->field('sw.*,s.store_name')->join('__STORE__ s','s.store_id = sw.store_id', 'INNER')->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();

    	$this->assign('create_time',$create_time2);
    	$show  = $Page->show();
    	$this->assign('show',$show);
    	$this->assign('list',$list);
    	$this->assign('pager',$Page);
    	C('TOKEN_ON',false);
    }
    /**
     * 删除申请记录
     */
    public function delStoreWithdrawals()
    {
        $del_id = I('del_id');
        $del_where =  "id = $del_id and status < 0";
        $res = M("store_withdrawals")->where($del_where)->delete();
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'删除失败']);
        }
    }

	/**
	 * 修改编辑商家 申请提现
	 */
	public function editStoreWithdrawals()
	{
		$id = I('id');
		$withdrawals = Db::name('store_withdrawals')->where('id', $id)->find();
		$store = M('store')->where("store_id", $withdrawals['store_id'])->find();
		$this->assign('store', $store);
		$this->assign('data', $withdrawals);
		return $this->fetch();
	}

    /**
     * 删除申请记录
     */
    public function delWithdrawals()
    {
        $del_id = I('del_id/d');
        $res = DB::name("withdrawals")->where(['id '=>$del_id])->where('status < 0')->delete();
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'删除失败']);
        }

    } 
    
    /**
     * 修改编辑 申请提现
     */
    public  function editWithdrawals(){        
       $id = I('id');
       $model = M("withdrawals");
       $withdrawals = $model->find($id);
       $user = M('users')->where("user_id = {$withdrawals[user_id]}")->find();     
       if($user['nickname'])        
           $withdrawals['user_name'] = $user['nickname'];
       elseif($user['email'])        
           $withdrawals['user_name'] = $user['email'];
       elseif($user['mobile'])        
           $withdrawals['user_name'] = $user['mobile'];            
       
       $this->assign('user',$user);
       $this->assign('data',$withdrawals);
       return $this->fetch();
    }      
    
    /**
     *  商家结算记录
     */
    public function order_statis(){
        $store_id = I('store_id/d',0);
        $create_date = I('create_date');
        $create_date = str_replace("+"," ",$create_date);
        $create_date2 = $create_date  ? $create_date  : date('Y-m-d',strtotime('-1 month')).' - '.date('Y-m-d',strtotime('+1 month'));
        $create_date3 = explode(' - ',$create_date2);
        $where = " os.create_date >= '".strtotime($create_date3[0])."' and os.create_date <= '".strtotime($create_date3[1])."' ";
        $this->assign('start_time',$create_date3[0]);
        $this->assign('end_time',$create_date3[1]);
        $store_id && $where .= " and os.store_id = $store_id ";

        $count = Db::name('order_statis')->alias('os')->where($where)->count();
        $Page  = new Page($count,16);
        $list = Db::name('order_statis')
            ->alias('os')->join('__STORE__ s','s.store_id = os.store_id')
            ->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('create_date',$create_date2);
        $show  = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        return $this->fetch();
    }

    /**
     *  处理会员提现申请
     */
    public function withdrawals_update(){
    	$id = I('id/a');
        $data['status']= $status = I('status');
    	$data['remark'] = I('remark');
        if($status == 1) $data['check_time'] = time();
        if($status != 1) $data['refuse_time'] = time();
        $r = M('withdrawals')->where('id in ('.implode(',', $id).')')->update($data);
    	if($r){
    		$this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
    	}else{
    		$this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
    	}  	
    }

    /**
     *  处理店铺提现申请
     */
    public function store_withdrawals_update(){
    	$id = I('id/a');
        $data['status']= $status = I('status');
        if($status == 1) $data['check_time'] = time();
        if($status != 1) $data['refuse_time'] = time();
        $data['remark'] = I('remark');
        $r = M('store_withdrawals')->where('id in ('.implode(',', $id).')')->save($data);
    	if($r){
    		$this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
    	}else{
    		$this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
    	}
    }
    
    public function transfer(){
    	$id = I('selected/a');
    	if(empty($id))$this->error('请至少选择一条记录');
    	$atype = I('atype');
    	if(is_array($id)){
    		$withdrawals = M('withdrawals')->where('id in ('.implode(',', $id).')')->select();
    	}else{
    		$withdrawals = M('withdrawals')->where(array('id'=>$id))->select();
    	}
    	$alipay['batch_num'] = 0;
    	$alipay['batch_fee'] = 0;
    	foreach($withdrawals as $val){
    		$user = M('users')->where(array('user_id'=>$val['user_id']))->find();
    		if($user['user_money'] < $val['money'])
    		{
    			$data = array('status'=>-2,'remark'=>'账户余额不足');
    			M('withdrawals')->where(array('id'=>$val['id']))->save($data);
    			$this->error('账户余额不足');
    		}else{
    			$rdata = array('type'=>1,'money'=>$val['money'],'log_type_id'=>$val['id'],'user_id'=>$val['user_id']);
    			if($atype == 'online'){
    			 
header("Content-type: text/html; charset=utf-8");
exit('请联系TPshop官网客服购买高级版支持此功能'); 
    			}else{
    				accountLog($val['user_id'], ($val['money'] * -1), 0,"管理员处理用户提现申请");//手动转账，默认视为已通过线下转方式处理了该笔提现申请
    				$r = M('withdrawals')->where(array('id'=>$val['id']))->save(array('status'=>2,'pay_time'=>time()));
    				expenseLog($rdata);//支出记录日志
    			}
    		}
    	}
    	if($alipay['batch_num']>0){
    		//支付宝在线批量付款
    		include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
    		$alipay_obj = new \alipay();
    		$alipay_obj->transfer($alipay);
    	}
    	$this->success("操作成功!",U('remittance'),3);
    }
    
    
    public function store_transfer(){
    	$id = I('selected/a');
    	if(empty($id)) $this->error('请至少选择一条记录');

    	$atype = I('atype');
    	if(is_array($id)){
    		$withdrawals = M('store_withdrawals')->where('id in ('.implode(',', $id).')')->select();
    	}else{
    		$withdrawals = M('store_withdrawals')->where(array('id'=>$id))->select();
    	}
    	$alipay['batch_num'] = 0;
    	$alipay['batch_fee'] = 0;
    	foreach($withdrawals as $val){
    		$store = M('store')->where(array('store_id'=>$val['store_id']))->find();
    		if($store['store_money'] < $val['money'])
    		{
    			$data['status'] = -2;
    			$data['remark'] = '账户余额不足';
    			M('store_withdrawals')->where(array('id'=>$val['id']))->save($data);
    			$this->error('账户余额不足');
    		}else{
    			$rdata = array('type'=>0,'money'=>$val['money'],'log_type_id'=>$val['id'],'store_id'=>$val['store_id']);
    			if($atype == 'online'){
    				 header("Content-type: text/html; charset=utf-8");
				 exit('请联系TPshop官网客服购买高级版支持此功能'); 
    			}else{
    				storeAccountLog($val['store_id'], ($val['money'] * -1), 0,"管理员处理商家提现申请");//手动转账，默认视为已通过线下转方式处理了该笔提现申请    	
    				$r = M('store_withdrawals')->where(array('id'=>$val['id']))->save(array('status'=>2,'pay_time'=>time()));
    				expenseLog($rdata);//支出记录日志    				
    			}
    		}
    	}
    	if($alipay['batch_num']>0){
    		//支付宝在线批量付款
    		include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
    		$alipay_obj = new \alipay();
    		$alipay_obj->transfer($alipay);
    	}
    	$this->success("操作成功!",U('store_remittance'),3);
    }
    
    public function expense_log(){
    	$map = array();
    	$begin = strtotime(I('add_time_begin'));
    	$end = strtotime(I('add_time_end'));
    	if($begin && $end){
    		$map['addtime'] = array('between',"$begin,$end");
    	}
    	$count = M('expense_log')->where($map)->count();
    	$page = new Page($count);
    	$lists  = M('expense_log')->where($map)->limit($page->firstRow.','.$page->listRows)->select();
    	$this->assign('page',$page->show());
    	$this->assign('total_count',$count);
    	$this->assign('list',$lists);
    	$admin = M('admin')->getField('admin_id,user_name');
    	$this->assign('admin',$admin);
    	$typeArr = array('商家提现','会员提现','订单退款','其他');
    	$this->assign('typeArr',$typeArr);
    	return $this->fetch();
    }
}