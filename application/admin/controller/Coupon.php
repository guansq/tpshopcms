<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。.
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Date: 2015-12-11
 */
namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class Coupon extends Base {
    /**----------------------------------------------*/
     /*                优惠券控制器                  */
    /**----------------------------------------------*/
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表
        $map = array();
        $name = I('name');
        if(!empty($name)) $map['name'] = array('like',"%$name%");
        $begin = strtotime(I('add_time_begin'));
        $end = strtotime(I('add_time_end'));
        if($begin && $end){
            $map['add_time'] = array('between',"$begin,$end");
        }
        $count =  M('coupon')->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $lists = M('coupon')->where($map)->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $store_id = get_arr_column($lists,'store_id');
        if(!empty($store_id)){
            $store = M('store')->where("store_id in (".implode(',', $store_id).")")->getField('store_id,store_name');
        }
        $this->assign('store',$store);
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('coupons',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function coupon_info(){
        $cid = I('get.id');
        if($cid){
            $coupon = M('coupon')->where(array('id'=>$cid))->find();
            $coupon['store_name'] = M('store')->where(array('store_id'=>$coupon['store_id']))->getField('store_name');
            $this->assign('coupon',$coupon);
        }else{
            $def['send_start_time'] = strtotime("+1 day");
            $def['send_end_time'] = strtotime("+1 month");
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('coupon',$def);
        }
        $this->assign('coupons',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
    * 优惠券发放
    */
    public function make_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        $type = I('get.type');
        //查询是否存在优惠券
        $data = M('coupon')->where(array('id'=>$cid))->find();
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    	if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("优惠券类型不存在");
        if($type != 3) $this->error("该优惠券类型不支持发放");
        if(IS_POST){
            $num  = I('post.num/d');
            if($num>$remain) $this->error($data['name'].'发放量不够了');
            if(!$num > 0) $this->error("发放数量不能小于0");
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            for($i=0;$i<$num; $i++){
                do{
                    $code = get_rand_str(8,0,1);//获取随机8位字符串
                    $check_exist = M('coupon_list')->where(array('code'=>$code))->find();
                }while($check_exist);
                $add['code'] = $code;
                M('coupon_list')->add($add);
            }
            M('coupon')->where("id=$cid")->setInc('send_num',$num);
            adminLog("发放".$num.'张'.$data['name']);
            $this->success("发放成功",U('Admin/Coupon/index'));
            exit;
        }
        $this->assign('coupon',$data);
        return $this->fetch();
    }
    
    public function ajax_get_user(){
    	//搜索条件
    	$condition = array();
    	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    	I('email') ? $condition['email'] = I('email') : false;
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
    	$model = M('users');
    	$count = $model->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $user_level = M('user_level')->getField('level_id,level_name',true);       
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$this->assign('page',$show);
    	return $this->fetch();
    }
    
    public function send_coupon(){
    	$cid = I('cid');    	
    	if(IS_POST){
    		$level_id = I('level_id');
    		$user_id = I('user_id');
    		$insert = '';
    		$coupon = M('coupon')->where("id=$cid")->find();
    		if($coupon['createnum']>0){
    			$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
    			if($remain<=0) $this->error($coupon['name'].'已经发放完了');
    		}
    		
    		if(empty($user_id) && $level_id>=0){
    			if($level_id==0){
    				$user = M('users')->where("is_lock=0")->select();
    			}else{
    				$user = M('users')->where("is_lock=0 and level_id=$level_id")->select();
    			}
    			if($user){
    				$able = count($user);//本次发送量
    				if($coupon['createnum']>0 && $remain<$able){
    					$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    				}
    				foreach ($user as $k=>$val){
    					$user_id = $val['user_id'];
    					$time = time();
    					$gap = ($k+1) == $able ? '' : ',';
    					$insert .= "($cid,1,$user_id,$time)$gap";
    				}
    			}
    		}else{
    			$able = count($user_id);//本次发送量
    			if($coupon['createnum']>0 && $remain<$able){
    				$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    			}
    			foreach ($user_id as $k=>$v){
    				$time = time();
    				$gap = ($k+1) == $able ? '' : ',';
    				$insert .= "($cid,1,$v,$time)$gap";
    			}
    		}
			$sql = "insert into __PREFIX__coupon_list (`cid`,`type`,`uid`,`send_time`) VALUES $insert";
            Db::execute($sql);
			M('coupon')->where("id=$cid")->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$coupon['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->select();
    	$this->assign('level',$level);
    	$this->assign('cid',$cid);
    	return $this->fetch();
    }

    /*
     * 删除优惠券类型
     */
    public function del_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $row = M('coupon')->where(array('id'=>$cid))->delete();
        if (!$row) {
            $this->ajaxReturn(['status' => 0, 'msg' => '优惠券不存在，删除失败']);
        }
        
        //删除此类型下的优惠券
        M('coupon_list')->where(array('cid'=>$cid))->delete();
        $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
    }


    /*
     * 优惠券详细查看
     */
    public function coupon_list(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $check_coupon = M('coupon')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_coupon['id'] > 0)
            $this->error('不存在该类型优惠券');
       
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid;    //联合用户表去查询用户名        
        
        $count = Db::query($sql);
        $count = $count[0]['c'];
    	$Page = new Page($count,10);
    	$show = $Page->show();
        
        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid.    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = Db::query($sql);
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);            	
    	$this->assign('page',$show);        
    	$this->assign('pager',$Page);
        return $this->fetch();
    }
    
    /*
     * 删除一张优惠券
     */
    public function coupon_list_del(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
         $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }

    /**
     * 优惠券面额列表
     * @return mixed
     */
    public function coupon_price_list()
    {
        $couponPriceCount =  Db::name('coupon_price')->where('')->count();
        $Page = new Page($couponPriceCount, 10);
        $show = $Page->show();
        $couponPriceList = Db::name('coupon_price')->where('')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('couponPriceList',$couponPriceList);
        $this->assign('page',$show);
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function coupon_price_info()
    {
        $coupon_price_id = input('coupon_price_id');
        if (IS_POST) {
            $coupon_price_value = input('coupon_price_value');
            if ($coupon_price_id) {
                //更新
                $same_count = Db::name('coupon_price')->where('coupon_price_id','<>',$coupon_price_id)->where('coupon_price_value',$coupon_price_value)->count();
                if($same_count > 0){
                    $this->error('已存在相同面额');
                }
                $res = Db::name('coupon_price')->where(['coupon_price_id' => $coupon_price_id])->update(['coupon_price_value' => $coupon_price_value]);
            } else {
                //插入
                $same_count = Db::name('coupon_price')->where('coupon_price_value',$coupon_price_value)->count();
                if($same_count > 0){
                    $this->error('已存在相同面额');
                }
                $res = Db::name('coupon_price')->insert(['coupon_price_value' => $coupon_price_value]);
            }
            if ($res !== false) {
                $this->success('操作成功', U('Coupon/coupon_price_list'));
            }else{
                $this->error('操作失败');
            }
        }
        if ($coupon_price_id) {
            $coupon_price_info = Db::name('coupon_price')->where('coupon_price_id', $coupon_price_id)->find();
            $this->assign('coupon_price_info', $coupon_price_info);
        }
        return $this->fetch();
    }

    public function coupon_price_del()
    {
        $coupon_price_id= I('post.del_id');
        if(empty($coupon_price_id)){
            $this->ajaxReturn('参数错误');
        }
        $del = Db::name('coupon_price')->where('coupon_price_id',$coupon_price_id)->delete();
        if($del !== false){
            $this->ajaxReturn(['code'=>1,'msg'=>'删除成功','result'=>'']);
        }else{
            $this->ajaxReturn(['code'=>0,'msg'=>'删除失败','result'=>'']);
        }
    }
}