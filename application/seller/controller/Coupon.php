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
 * Date: 2016-06-11
 */
namespace app\seller\controller;

use think\AjaxPage;
use think\Page;
use think\Db;
use think\Loader;

class Coupon extends Base
{
    /**
     * 优惠券类型列表
     */
    public function index()
    {
        //获取优惠券列表        
        $count = M('coupon')->where("store_id", STORE_ID)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $lists = M('coupon')->where("store_id", STORE_ID)->order('add_time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('lists', $lists);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('coupons', C('COUPON_TYPE'));
        return $this->fetch();
    }

    /**
     * 添加编辑一个优惠券类型
     */
    public function coupon_info()
    {
        if (IS_POST) {
            $data = I('post.');
            if ($data['type']>0){
                $data['send_start_time'] = strtotime($data['send_start_time']);
                $data['send_end_time'] = strtotime($data['send_end_time']);
            }else{
                $data['send_start_time'] = '';
                $data['send_end_time'] = '';
            }
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            $couponValidate = Loader::validate('Coupon');
            if (!$couponValidate->batch()->check($data)) {
                $this->ajaxReturn([
                    'status' => 0, 'msg' => '操作失败',
                    'result' => $couponValidate->getError(),
                    'token'    => \think\Request::instance()->token()
                ]);
            }
            if (empty($data['id'])) {
                $data['add_time'] = time();
                $data['store_id'] = STORE_ID;
                $row = Db::name('coupon')->insertGetId($data);
                //指定商品
                if($data['use_type'] == 1){
                    foreach ($data['goods_id'] as $v) {
                        Db::name('goods_coupon')->add(['coupon_id'=>$row,'goods_id'=>$v]);
                    }
                }
                //指定商品分类id
                if($data['use_type'] == 2){
                    Db::name('goods_coupon')->add(['coupon_id'=>$row,'goods_category_id'=>$data['cat_id3']]);
                }
            } else {
                $row = M('coupon')->where(array('id' => $data['id'], 'store_id' => STORE_ID))->save($data);
                Db::name('goods_coupon')->where('coupon_id',$data['id'])->delete();//先删除后添加
                //指定商品
                if($data['use_type'] == 1){
                    foreach ($data['goods_id'] as $v) {
                        Db::name('goods_coupon')->add(['coupon_id'=>$data['id'],'goods_id'=>$v]);
                    }
                }
                //指定商品分类id
                if($data['use_type'] == 2){
                    Db::name('goods_coupon')->add(['coupon_id'=>$data['id'],'goods_category_id'=>$data['cat_id3']]);
                }
            }
            if ($row !== false) {
                $this->ajaxReturn(['status' => 1, 'msg' => '编辑代金券成功', 'result' => '']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '编辑代金券失败', 'result' => '']);
            }
        }
        $coupon_price_list = Db::name('coupon_price')->where('')->select();
        if(empty($coupon_price_list)){
            $this->error('总平台没有设置优惠券面额，商家不能添加优惠券');
        }
        $cid = I('get.id/d');
        if ($cid) {
            $coupon = M('coupon')->where(array('id' => $cid, 'store_id' => STORE_ID))->find();
            if (empty($coupon)) {
                $this->error('代金券不存在');
            }else{
            	if($coupon['use_type'] == 2){
                    $goods_coupon = Db::name('goods_coupon')->where('coupon_id',$cid)->find();
            		$cat_info = M('goods_category')->where(array('id'=>$goods_coupon['goods_category_id']))->find();
            		$cat_path = explode('_', $cat_info['parent_id_path']);
            		$coupon['cat_id1'] = $cat_path[1];
            		$coupon['cat_id2'] = $cat_path[2];
                    $coupon['cat_id3'] = $goods_coupon['goods_category_id'];
            	}
            	if($coupon['use_type'] == 1){
                    $coupon_goods_ids = Db::name('goods_coupon')->where('coupon_id',$cid)->getField('goods_id',true);
            		$enable_goods = M('goods')->where("goods_id", "in", $coupon_goods_ids)->select();
            		$this->assign('enable_goods',$enable_goods);
            	}
            }
            $this->assign('coupon', $coupon);
        } else {
            $def['send_start_time'] = strtotime("+1 day");
            $def['send_end_time'] = strtotime("+1 month");
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('coupon', $def);
        }
        $bind_all_gc = M('store')->where(array('store_id'=>STORE_ID))->getField('bind_all_gc');
        if ($bind_all_gc == 1) {
            $cat_list = M('goods_category')->where(['parent_id' => 0])->select();//自营店已绑定所有分类
        } else {
            //自营店已绑定所有分类
            $cat_list = Db::name('goods_category')->where(['parent_id' => 0])->where('id', 'IN', function ($query) {
                $query->name('store_bind_class')->where('store_id', STORE_ID)->where('state', 1)->field('class_1');
            })->select();
        }
        $this->assign('cat_list',$cat_list);
        $this->assign('coupon_price_list',$coupon_price_list);
        return $this->fetch();
    }

    /**
    * 优惠券发放
    */
    public function make_coupon()
    {
        //获取优惠券ID
        $cid = I('id/d');
        $type = I('type');
        //查询是否存在优惠券
        $data = M('coupon')->where(array('id' => $cid, 'store_id' => STORE_ID))->find();
        if($data['send_start_time'] > time()) $this->error('该优惠券未到发放时间');
        if($data['send_end_time']< time()) $this->error('该优惠券已过发放时间');
        if (IS_POST) {
            if ($type != 3) $this->ajaxReturn(['status'=>'-1','msg'=>'该优惠券类型不支持发放']);
            if (!$data) $this->ajaxReturn(['status'=>'-1','msg'=>'优惠券类型不存在']);
        	if($data['createnum']>0){    //不是无限发放的，要计算剩余派发量
        		$remain = $data['createnum'] - $data['send_num'];
        		if ($remain <= 0) $this->ajaxReturn(['status'=>'-1','msg'=>$data['name'].'已经发放完了']);
        	}
            $num = I('post.num/d');
            if ($num > $remain and $data['createnum']>0) $this->ajaxReturn(['status'=>'-1','msg'=>$data['name'] . '发放量不够了，只剩下'.$remain.'份了']);
            if (!$num > 0) $this->ajaxReturn(['status'=>'-1','msg'=>'发放数量不能小于0']);
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            $add['store_id'] = STORE_ID;
            for ($i = 0; $i < $num; $i++) {
                do {
                    $code = get_rand_str(8, 0, 1);//获取随机8位字符串
                    $check_exist = M('coupon_list')->where(array('code' => $code))->find();
                } while ($check_exist);
                $add['code'] = $code;
                M('coupon_list')->add($add);
            }
            $coupon_where = array('id' => $cid, 'store_id' => STORE_ID);
            M('coupon')->where($coupon_where)->setInc('send_num', $num);
            sellerLog("发放" . $num . '张' . $data['name']);
            $this->ajaxReturn(['status'=>'1','msg'=>'发放成功']);
            exit;
        }
        $this->assign('coupon', $data);
        return $this->fetch();
    }

    /**
     * 获取用户列表
     */
    public function ajax_get_user()
    {
        //搜索条件
    	$condition = array();
    	I('mobile') ? $condition['u.mobile'] = I('mobile') : false;
    	I('email') ? $condition['u.email'] = I('email') : false;
    	$cid = I('cid');
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['u.nickname'] = array('like',"%$nickname%");
    	}
    	$level_id = I('level_id/d');
    	if($level_id > 0){
    		$condition['u.level'] = $level_id;
    	}
    	if($level_id  ==  -1){
    		$tb = C('DB_PREFIX').'store_collect';
    		$condition['c.store_id'] = STORE_ID;
    		$count = M('users')->alias('u')->join($tb.' c', 'u.user_id = c.user_id','LEFT')->where($condition)->count();
    		$Page  = new AjaxPage($count,10);
    		$userList = M('users')->field('u.*')->alias('u')
                ->join($tb.' c', 'u.user_id = c.user_id','LEFT')
                ->where($condition)->order("u.user_id desc")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
    	}else{
            $exclude_uids  = M('coupon_list')->where(['cid'=>$cid])->getField('uid',true);  //查找看一集给谁发过了
    		$count = M('users')->alias('u')->whereNotIn('u.user_id',$exclude_uids)->where($condition)->count();
    		$Page  = new AjaxPage($count,10);
    		$userList = M('users')->alias('u')
                ->where($condition)
                ->whereNotIn('u.user_id',$exclude_uids)
                ->order("u.user_id desc")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
    	}
        $user_level = M('user_level')->getField('level_id,level_name',true);       
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$show = $Page->show();
    	$this->assign('page',$show);
        return $this->fetch();
    }

    /**
     * 发放优惠券
     */
    public function send_coupon()
    {
        $cid = I('cid/d');
        if (IS_POST) {
            $level_id = I('level_id/d');
            $user_id = I('user_id/a');
            $insert = '';
            $coupon_where = array('id' => $cid, 'store_id' => STORE_ID);
            $coupon = M('coupon')->where($coupon_where)->find();
            if ($coupon['createnum'] > 0) {
                $remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
                if ($remain <= 0) $this->error($coupon['name'] . '已经发放完了');
            }

            if (empty($user_id) && $level_id >= 0) {
                $user_where = array('is_lock' => 0);
                if ($level_id == 0) {
                    $user = M('users')->where($user_where)->select();
                } else {
                    $user_where['level'] = $level_id;
                    $user = M('users')->where($user_where)->select();
                }
                if ($user) {
                    $able = count($user);//本次发送量
                    if ($coupon['createnum'] > 0 && $remain < $able) {
                        $this->error($coupon['name'] . '派发量只剩' . $remain . '张');
                    }
                    foreach ($user as $k => $val) {
                        $user_id = $val['user_id'];
                        $time = time();
                        $gap = ($k + 1) == $able ? '' : ',';
                        $insert .= "($cid,1,$user_id,$time," . STORE_ID . ")$gap";
                    }
                }
            } else {
                $able = count($user_id);//本次发送量
                if ($coupon['createnum'] > 0 && $remain < $able) {
                    $this->error($coupon['name'] . '派发量只剩' . $remain . '张');
                }
                foreach ($user_id as $k => $v) {
                    $time = time();
                    $gap = ($k + 1) == $able ? '' : ',';
                    $insert .= "($cid,1,$v,$time," . STORE_ID . ")$gap";
                }
            }
            $sql = "insert into __PREFIX__coupon_list (`cid`,`type`,`uid`,`send_time`,store_id) VALUES $insert";
            Db::execute($sql);
            M('coupon')->where("id", $cid)->setInc('send_num', $able);
            sellerLog("发放" . $able . '张' . $coupon['name']);
            $this->success("发放成功");
            exit;
        }
        $level = M('user_level')->select();
        $this->assign('level', $level);
        $this->assign('cid', $cid);
        return $this->fetch();
    }

    /**
     * 删除优惠券类型
     */
    public function del_coupon()
    {
        //获取优惠券ID
        $cid = I('get.id/d');
        //查询是否存在优惠券
        $row = M('coupon')->where(array('id' => $cid, 'store_id' => STORE_ID))->delete();
        if ($row) {
            //删除此类型下的优惠券
            M('coupon_list')->where(array('cid' => $cid, 'store_id' => STORE_ID))->delete();
            $this->ajaxReturn(['status'=>'1','msg'=>'删除成功']);
        } else {
            $this->ajaxReturn(['status'=>'-1','msg'=>'删除失败']);
        }
    }

    /**
     * 优惠券详细查看
     */
    public function coupon_list()
    {
        //获取优惠券ID
        $cid = I('get.id/d');
        //查询是否存在优惠券        
        $check_coupon = M('coupon')->field('id,type')->where(array('id' => $cid, 'store_id' => STORE_ID))->find();
        if (!$check_coupon['id'] > 0) {
            $this->error('不存在该类型优惠券');
        }
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l " .
            "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid " . //联合优惠券表查询名称
            "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id " .     //联合订单表查询订单编号
            "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid";    //联合用户表去查询用户名

        $count = Db::query($sql, ['cid' => $cid]);
        $count = $count[0]['c'];
        $Page = new Page($count, 10);
        $show = $Page->show();

        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l " .
            "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid " . //联合优惠券表查询名称
            "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id " .     //联合订单表查询订单编号
            "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid" .    //联合用户表去查询用户名
            " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = Db::query($sql,['cid'=>$cid]);
        $this->assign('coupon_type', C('COUPON_TYPE'));
        $this->assign('type', $check_coupon['type']);
        $this->assign('lists', $coupon_list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     * 删除一张优惠券
     */
    public function coupon_list_del()
    {
        //获取优惠券ID
        $cid = I('get.id/d');
        if (!$cid)
            $this->ajaxReturn(['status'=>'1','msg'=>'删除成功']);
        //查询是否存在优惠券
        $row = M('coupon_list')->where(array('id' => $cid, 'store_id' => STORE_ID))->delete();
        Db::name('goods_coupon')->where('coupon_id', $cid)->delete();
        if ($row){
            $this->ajaxReturn(['status'=>'1','msg'=>'删除成功']);
        } else {
            $this->ajaxReturn(['status'=>'-1','msg'=>'删除失败']);
        }
    }
}