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
 * 2015-11-21
 */
namespace app\mobile\controller;

use app\common\logic\CartLogic;
use app\common\logic\StoreLogic;
use app\common\logic\UsersLogic;
use app\common\logic\OrderGoodsLogic;
use app\common\logic\MessageLogic;
use app\common\logic\CommentLogic;
use think\Page;
use think\Verify;
use think\Db;

class User extends MobileBase
{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id",$user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle', 'reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express',
        );
        if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) {
            header("location:" . U('Mobile/User/login'));
            exit;
        }

        $order_status_coment = array(
            'WAITPAY' => '待付款 ', //订单查询状态 待支付
            'WAITSEND' => '待发货', //订单查询状态 待发货
            'WAITRECEIVE' => '待收货', //订单查询状态 待收货
            'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
        );
        $this->assign('order_status_coment', $order_status_coment);
    }

    /*
     * 用户中心首页
     */
    public function index()
    {

        $user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->getMobileUserInfo($user_id); //当前登录用户信息
        $comment_count = M('comment')->where("user_id", $user_id)->count();   // 我的评论数
        $level_name = M('user_level')->where("level_id", $this->user['level'])->getField('level_name'); // 等级名称
        //获取用户信息的数量
        $messageLogic = new MessageLogic();
        $user_message_count = $messageLogic->getUserMessageCount();
        $this->assign('user_message_count', $user_message_count);
        $this->assign('level_name', $level_name);
        $this->assign('comment_count', $comment_count);
        $this->assign('user',$user['result']);
        return $this->fetch();
    }


    public function logout()
    {
        session_unset();
        session_destroy();
        setcookie('cn', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        //$this->success("退出成功",U('Mobile/Index/index'));
        header("Location:" . U('Mobile/Index/index'));
        exit();
    }

    /*
     * 账户资金
     */
    public function account()
    {
        $user = session('user');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id, I('get.type'));
        $account_log = $data['result'];

        $this->assign('user', $user);
        $this->assign('account_log', $account_log);
        $this->assign('page', $data['show']);

        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_account_list');
        }
        return $this->fetch();
    }

    public function coupon()
    {
        //
        $logic = new UsersLogic();
        $data = $logic->get_coupon($this->user_id, $_REQUEST['type']);
        foreach($data['result'] as $k =>$v){
            if($v['use_type']==1){ //指定商品
                $data['result'][$k]['goods_id'] = M('goods_coupon')->field('goods_id')->where(['coupon_id'=>$v['cid']])->getField('goods_id');
            }
            if($v['use_type']==2){ //指定分类
                $data['result'][$k]['category_id'] = Db::name('goods_coupon')->where(['coupon_id'=>$v['cid']])->getField('goods_category_id');
            }
        }
        $coupon_list = $data['result'];
        $store_id = get_arr_column($coupon_list,'store_id');
        if(!empty($store_id)){
            $store = M('store')->where("store_id in (".implode(',', $store_id).")")->getField('store_id,store_name');
        }
        $this->assign('store',$store);
        $this->assign('coupon_list', $coupon_list);
        $this->assign('page', $data['show']);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_coupon_list');
        }
        return $this->fetch();
    }

    /**
     * 确定订单的使用优惠券
     */
    public function checkcoupon()
    {
        $type = input('type');
        $now = time();
        $cartLogic = new \app\common\logic\CartLogic();
        // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $cartLogic->setUserId($this->user_id);
        $cartList = $cartLogic->getCartList(1);//获取购物车商品
        $cartTotalPrice = array_sum(array_map(function($val){return $val['total_fee'];}, $cartList));//商品优惠总价
        $where = '';
        if(empty($type)){
            $where = " c2.uid = {$this->user_id} and {$now} < c1.use_end_time and {$now} > c1.use_start_time and c1.condition <= {$cartTotalPrice} ";
        }
        if($type == 1){
            $where = " c2.uid = {$this->user_id} or c1.use_end_time < {$now} and c1.use_start_time > {$now} and c1.condition >= {$cartTotalPrice}";
        }
        $coupon_list = DB::name('coupon')
            ->alias('c1')
            ->field('c1.name,c1.money,c1.condition,c1.use_end_time, c2.*')
            ->join('coupon_list c2','c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0','LEFT')
            ->where($where)
            ->select();
        $this->assign('coupon_list', $coupon_list); // 优惠券列表
        return $this->fetch();
    }

    /**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
            $this->redirect(U('Mobile/User/index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Mobile/User/index");
        $this->assign('referurl', $referurl);
        return $this->fetch();
    }


    public function do_login()
    {
        $username = I('post.username');
        $password = I('post.password');
        $username = trim($username);
        $password = trim($password);
        //验证码验证
        if (isset($_POST['verify_code'])) {
            $verify_code = I('post.verify_code');
            $verify = new Verify();
            if (!$verify->check($verify_code, 'user_login')) {
                $res = array('status' => 0, 'msg' => '验证码错误');
                exit(json_encode($res));
            }
        }
        $logic = new UsersLogic();
        $res = $logic->login($username, $password);
        if ($res['status'] == 1) {
            $res['url'] = urldecode(I('post.referurl'));
            session('user', $res['result']);
            setcookie('user_id', $res['result']['user_id'], null, '/');
            setcookie('is_distribut', $res['result']['is_distribut'], null, '/');
            $nickname = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('uname', urlencode($nickname), null, '/');
            setcookie('cn',0,time()-3600,'/');
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($res['result']['user_id']);
            $cartLogic->doUserLoginHandle();  //用户登录后 需要对购物车 一些操作
        }
        exit(json_encode($res));
    }

    /**
     *  注册
     */
    public function reg()
    {
    	if($this->user_id > 0) {
            $this->redirect(U('Mobile/User/index'));
        }
        $reg_sms_enable = tpCache('sms.regis_sms_enable');
        $reg_smtp_enable = tpCache('sms.regis_smtp_enable');
        if (IS_POST) {
            $logic = new UsersLogic();
            //验证码检验
            //$this->verifyHandle('user_reg');
            $username = I('post.username', '');
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            //是否开启注册验证码机制
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 1);

            $session_id = session_id();
           if($this->verifyHandle('user_reg') == false){
                $this->ajaxReturn(['status'=>0,'msg'=>'图像验证码错误']);
            };
            //是否开启注册验证码机制
            if(check_mobile($username)){
                if($reg_sms_enable){
                    //手机功能没关闭
                    $check_code = $logic->check_validate_code($code, $username, 'phone', $session_id, $scene);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn($check_code);
                    }
                }
            }
            //是否开启注册邮箱验证码机制
            if(check_email($username)){
                if($reg_smtp_enable){
                    //邮件功能未关闭
                    $check_code = $logic->check_validate_code($code, $username);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn($check_code);
                    }
                }
            }
            $data = $logic->reg($username, $password, $password2);
            if ($data['status'] != 1){
                $this->ajaxReturn($data);
            }
            session('user', $data['result']);
            setcookie('user_id', $data['result']['user_id'], null, '/');
            setcookie('is_distribut', $data['result']['is_distribut'], null, '/');
            $cartLogic = new CartLogic();
            $cartLogic->doUserLoginHandle($this->session_id, $data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
            $this->ajaxReturn($data);
            exit;
        }
        $this->assign('regis_sms_enable',$reg_sms_enable); // 注册启用短信：
        $this->assign('regis_smtp_enable',$reg_smtp_enable); // 注册启用邮箱：
        $sms_time_out = tpCache('sms.sms_time_out')>0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        return $this->fetch();
    }

    public function express()
    {
        $order_id = I('get.order_id/d', 0);
        $order_goods = M('order_goods')->where("order_id" , $order_id)->select();
        $delivery = M('delivery_doc')->where("order_id" , $order_id)->limit(1)->find();
        $this->assign('order_goods', $order_goods);
        $this->assign('delivery', $delivery);
        return $this->fetch();
    }

    /*
     * 用户地址列表
     */
    public function address_list()
    {
        $address_lists = Db::name('user_address')->where(array('user_id' => $this->user_id))->select();
        $region_list = Db::name('region')->cache(true)->getField('id,name');
        $this->assign('region_list', $region_list);
        $this->assign('lists', $address_lists);
        return $this->fetch();
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, I('post.'));
            if ($data['status'] != 1)
                $this->ajaxReturn($data);
            elseif ($_POST['source'] == 'cart2') {
                $data['url']=U('/Mobile/Cart/cart2', array('address_id' => $data['result']));
                $this->ajaxReturn($data);
            }elseif($_POST['source'] == 'team'){
                $order_id = input('order_id/d');
                $data['url']= U('/Mobile/Team/order', array('address_id' => $data['result'],'order_id'=>$order_id));
                $this->ajaxReturn($data);
            }
            $data['url']= U('/Mobile/User/address_list');
            $this->ajaxReturn($data);
        }
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province', $p);
        return $this->fetch();

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('id/d');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($data['status'] != 1)
                $this->ajaxReturn($data);
            elseif ($_POST['source'] == 'cart2') {
                $data['url']=U('/Mobile/Cart/cart2', array('address_id' => $data['result']));
                $this->ajaxReturn($data);
            }
            $data['url']= U('/Mobile/User/address_list');
            $this->ajaxReturn($data);
        }
        //获取省份
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $c = M('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
        $d = M('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
        if ($address['twon']) {
            $e = M('region')->where(array('parent_id' => $address['district'], 'level' => 4))->select();
            $this->assign('twon', $e);
        }

        $this->assign('province', $p);
        $this->assign('city', $c);
        $this->assign('district', $d);
                                                        
        $this->assign('address', $address);
        return $this->fetch();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default()
    {
        $id = I('get.id/d');
        $source = I('get.source');
        M('user_address')->where(array('user_id' => $this->user_id))->save(array('is_default' => 0));
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->save(array('is_default' => 1));
        if ($source == 'cart2') {
            header("Location:" . U('Mobile/Cart/cart2'));
        } else {
            header("Location:" . U('Mobile/User/address_list'));
        }
        exit();
    }

    /*
     * 地址删除
     */
    public function del_address(){
        $id = I('id/d','');
        
        $address = M('user_address')->where("address_id" , $id)->find();
        $row = M('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->delete();                
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if($address['is_default'] == 1)
        {
            $address2 = M('user_address')->where("user_id",$this->user_id)->find();            
            $address2 && M('user_address')->where("address_id",$address2['address_id'])->save(array('is_default'=>1));
        }        
        if(!$row)
            $this->ajaxReturn(['status'=>0,'msg'=>'操作失败','url'=>U('User/address_list')]);
        else
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('User/address_list')]);
    }

    /**
     * @time 2016/8/5
     * @author dyr
     * 订单评价列表
     */
    public function comment_list()
    {
        $order_id = I('get.order_id/d');
        $store_id = I('get.store_id/d');
        $goods_id = I('get.goods_id/d');
        $part_finish = I('get.part_finish/d', 0);
        if (empty($order_id) || empty($store_id)) {
            $this->error("参数错误");
        } else {
            //查找店铺信息
            $store_where['store_id'] = $store_id;
            $store_info = M('store')->field('store_id,store_name,store_phone,store_address,store_logo')->where($store_where)->find();
            if (empty($store_info)) {
                $this->error("该商家不存在");
            }
            //查找订单是否已经被用户评价
            $order_comment_where['order_id'] = $order_id;
            $order_comment_where['deleted'] = 0;
            $order_info = M('order')->field('order_id,order_sn,is_comment,add_time')->where($order_comment_where)->find();
            //查找订单下的所有未评价的商品
            $order_goods_logic = new OrderGoodsLogic();
            $no_comment_goods = $order_goods_logic->get_no_comment_goods($order_id, $goods_id);
            $this->assign('store_info', $store_info);
            $this->assign('order_info', $order_info);
            $this->assign('no_comment_goods', $no_comment_goods);
            $this->assign('part_finish', $part_finish);
            return $this->fetch();
        }
    }

    /**
     * @time 2016/8/5
     * @author dyr
     *  添加评论
     */
    public function addComment()
    {
        $data['order_id']       = I('post.order_id/d', 0);
        $data['goods_id']       = I('post.goods_id/d', 0);
        $data['service_rank']   = I('post.store_speed_hidden', 0);
        $data['deliver_rank']   = I('post.store_sever_hidden', 0);
        $data['goods_rank']     = I('post.store_packge_hidden', 0);
        $data['goods_score']    = I('post.rank', 0);
        $data['spec_key_name']  = I('post.spec_key_name', '');
        $data['content']        = I('post.content', '');
        $anonymous      = I('post.anonymous');
        $tag            = I('post.tag', '');

        $data['impression']   = (empty($tag[0])) ? '' : implode(',', $tag);
        $data['is_anonymous'] = empty($anonymous) ? 1 : 0;
        $data['user_id']      = $this->user_id;

        $commentLogic = new CommentLogic;
        $return = $commentLogic->addGoodsAndServiceComment($data);

        if ($return['status'] !== 1) {
            return $this->error($return['msg']);
        }

        $this->success("评论成功", U('User/comment'));
    }

    /*
     * 个人信息
     */
    public function userinfo()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if (IS_POST) {
            $post = input('post.');
            $scene = input('post.scene', 6);
        	$return = $userLogic->upload_headpic(false);
            if ($return['status'] !== 1) {
                $this->error($return['msg']);
            }else{
                $post['head_pic'] = $return['result'];
            }
            if (!empty($post['email'])) {
                $c = M('users')->where(['email' => $post['email'], 'user_id' => ['<>', $this->user_id]])->count();
                $c && $this->error("邮箱已被使用");
            }
            if (!empty($post['mobile'])) {
                $c = M('users')->where(['mobile' => $post['mobile'], 'user_id' => ['<>', $this->user_id]])->count();
                $c && $this->error("手机已被使用");
                if (!$post['mobile_code'])
                    $this->error('请输入验证码');
                $check_code = $userLogic->check_validate_code($post['mobile_code'], $post['mobile'], 'phone', $this->session_id, $scene);
                if ($check_code['status'] != 1)
                    $this->error($check_code['msg']);
            }

            if (!$userLogic->update_info($this->user_id, $post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //  获取订单城市
        $city = M('region')->where(array('parent_id' => $user_info['province'], 'level' => 2))->select();
        //  获取订单地区
        $area = M('region')->where(array('parent_id' => $user_info['city'], 'level' => 3))->select();
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('user', $user_info);
        $this->assign('sex', C('SEX'));
        
        $action = I('get.action');
        if ($action != '') {
            return $this->fetch("$action");
        }
        return $this->fetch();
    }

    /*
     * 邮箱验证
     */
    public function email_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['email_validated'] == 0)
            $step = 2;
        //原邮箱验证是否通过
        if ($user_info['email_validated'] == 1 && session('email_step1') == 1)
            $step = 2;
        if ($user_info['email_validated'] == 1 && session('email_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $email = I('post.email');
            $code = I('post.code');
            $info = session('email_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $email || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('email_code', null);
                    session('email_step1', null);
                    if (!$userLogic->update_email_mobile($email, $this->user_id))
                        $this->error('邮箱已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('email_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/email_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码邮箱不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $mobile = I('post.mobile');
            $code = I('post.code');
            $info = session('mobile_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $mobile || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('mobile_code', null);
                    session('mobile_step1', null);
                    if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                        $this->error('手机已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('mobile_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/mobile_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码手机不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /*
     *取消收藏
     */
    public function cancel_collect()
    {
        $collect_id = I('collect_id/d');
        $user_id = $this->user_id;
        if (M('goods_collect')->where(["collect_id" => $collect_id, "user_id" => $user_id])->delete()) {
            $this->ajaxReturn(['status'=>1,'msg'=>"取消收藏成功", 'url'=>U('User/collect_list')]);
        } else {
            $this->ajaxReturn(['status'=>1,'msg'=>"取消收藏失败", 'url'=>U('User/collect_list')]);
        }
    }

    /**
     *  删除一个收藏店铺
     * @author lxl
     * @time17-3-28
     */
    public function del_store_collect()
    {
        $id = I('get.log_id/d');
        if (!$id)
            $this->error("缺少ID参数");
        $store_id = M('store_collect')->where(array('log_id' => $id, 'user_id' => $this->user_id))->getField('store_id');
        $row = M('store_collect')->where(array('log_id' => $id, 'user_id' => $this->user_id))->delete();
        M('store')->where(array('store_id' => $store_id))->setDec('store_collect');
        if ($row){
            $this->ajaxReturn(['status'=>1,'msg'=>"取消收藏成功", 'url'=>U('User/collect_list')]);
        } else {
            $this->ajaxReturn(['status'=>1,'msg'=>"取消收藏失败", 'url'=>U('User/collect_list')]);
        }
    }

    public function message_list()
    {
        C('TOKEN_ON', true);
        if (IS_POST) {
            $this->verifyHandle('message');

            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $user = session('user');
            $data['user_name'] = $user['nickname'];
            $data['msg_time'] = time();
            if (M('feedback')->add($data)) {
                $this->success("留言成功", U('User/message_list'));
                exit;
            } else {
                $this->error('留言失败', U('User/message_list'));
                exit;
            }
        }
        $msg_type = array(0 => '留言', 1 => '投诉', 2 => '询问', 3 => '售后', 4 => '求购');
        $count = M('feedback')->where("user_id=" . $this->user_id)->count();
        $Page = new Page($count, 100);
        $Page->rollPage = 2;
        $message = M('feedback')->where("user_id=" . $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $showpage = $Page->show();
        header("Content-type:text/html;charset=utf-8");
        $this->assign('page', $showpage);
        $this->assign('message', $message);
        $this->assign('msg_type', $msg_type);
        return $this->fetch();
    }

    public function points_list()
    {
        $type = I('type','all');
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->points($this->user_id, $type);
        
        $this->assign('type', $type);
		$showpage = $result['page']->show();
        $this->assign('account_log', $result['account_log']);
        $this->assign('page', $showpage);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_points');
        }
        return $this->fetch();
    }

    public function account_list()
    {
    	$type = I('type','all');
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->account($this->user_id, $type);
    
    	$this->assign('type', $type);
    	$showpage = $result['page']->show();
    	$this->assign('account_log', $result['account_log']);
    	$this->assign('page', $showpage);
    	if ($_GET['is_ajax']) {
    		return $this->fetch('ajax_account_list');
    	}
    	return $this->fetch();
    }

    /**
     *资金详情
     */
    public function account_detail(){
        $log_id = I('log_id/d',0);
        $detail = Db::name('account_log')->where(['log_id'=>$log_id])->find();
        $this->assign('detail',$detail);
        return $this->fetch();
    }

    /*
     * 密码修改
     */
    public function password()
    {
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->get_info($this->user_id);
            $user = $data['result'];
            if ($user['mobile'] == '' && $user['email'] == '')
                $this->ajaxReturn(['status'=>-1,'msg'=>'请先绑定手机或邮箱','url'=>U('/Mobile/User/index')]);
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id, I('post.old_password'), I('post.new_password'), I('post.confirm_password'));
            if ($data['status'] == -1)
                $this->ajaxReturn(['status'=>-1,'msg'=>$data['msg']]);
            $this->ajaxReturn(['status'=>1,'msg'=>$data['msg'],'url'=>U('/Mobile/User/index')]);
            exit;
        }
        return $this->fetch();
    }

    function forget_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('User/Index'));
        }
        if (IS_POST) {
            $username = I('username');
            if (!empty($username)) {
                if(!$this->verifyHandle('forget')){
                    $this->ajaxReturn(['status'=>0,'msg'=>"验证码错误"]);
                    exit;
                }
                $field = 'mobile';
                if (check_email($username)) {
                    $field = 'email';
                }
                $user = M('users')->where(['email'=>$username])->whereOr(['mobile'=>$username])->find();
                if ($user) {
                    session('find_password', array('user_id' => $user['user_id'], 'username' => $username,
                        'email' => $user['email'], 'mobile' => $user['mobile'], 'type' => $field));
                    $this->ajaxReturn(['status'=>1,'msg'=>'', 'url'=>U('User/find_pwd')]);
                    exit;
                } else {
                    $this->ajaxReturn(['status'=>0,'msg'=>"用户名不存在，请检查"]);
                }
            }
        }
        return $this->fetch();
    }

    function find_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('User/Index'));
        }
        $user = session('find_password');
        if (empty($user)) {
            $this->error("请先验证用户名", U('User/forget_pwd'));
        }
        $this->assign('user', $user);
        return $this->fetch();
    }


    public function set_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('User/Index'));
        }
        $check = session('validate_code');
        if (empty($check)) {
            header("Location:" . U('User/forget_pwd'));
        } elseif ($check['is_check'] == 0) {
            $this->error('验证码还未验证通过', U('User/forget_pwd'));
        }
        if (IS_POST) {
            $password = I('post.password');
            $password2 = I('post.password2');
            if ($password2 != $password) {
                $this->error('两次密码不一致', U('User/forget_pwd'));
            }
            if ($check['is_check'] == 1) {
                $user = M('users')->where("mobile = '{$check['sender']}' or email = '{$check['sender']}'")->find();
                if($user){
                	M('users')->where("user_id=" . $user['user_id'])->save(array('password' => encrypt($password)));
			session('validate_code', null);
                	$this->success('新密码已设置行牢记新密码', U('User/index')); 
                	exit;
                }else{
                	$this->error('操作失败，请稍后再试',U('User/forget_pwd'));
                }               
            } else {
                $this->error('验证码还未验证通过', U('User/forget_pwd'));
            }
        }
        $is_set = I('is_set', 0);
        $this->assign('is_set', $is_set);
        return $this->fetch();
    }
 
    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            return false;
        }
        return true;
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'imageH' =>  60,
            'imageW' =>  300,
            'fontttf' => '5.ttf',
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
		exit();
    }

    /**
     * 账户管理
     */
    public function accountManage()
    {
        return $this->fetch();
    }

    public function order_confirm()
    {
        $id = I('get.id/d', 0);
        $data = confirm_order($id, $this->user_id);
        if ($data['status'] != 1) {
            $this->error($data['msg'], U('Mobile/Order/order_list'));
        } else {
            $this->success($data['msg'], U('Mobile/Order/order_detail', ['id' => $id]));
        }
    }

    public  function recharge(){
       	$order_id = I('order_id/d');
        $paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and  scene in(0,1)")->select();        
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code='weixin'")->select();            
        }        
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }        
        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('bankCodeList',$bankCodeList);
        
        if($order_id>0){
        	$order = M('recharge')->where("order_id = $order_id")->find();    
        	$this->assign('order',$order);
        }    
    	return $this->fetch();
    }
    
    public function recharge_list(){
    	$usersLogic = new UsersLogic;
    	$result= $usersLogic->get_recharge_log($this->user_id);  //充值记录
    	$this->assign('page', $result['show']);
    	$this->assign('lists', $result['result']);
    	if (I('is_ajax')) {
    		return $this->fetch('ajax_recharge_list');
    	}
    	return $this->fetch();
    }
    /**
     * 申请提现记录
     */
    public function withdrawals(){

        C('TOKEN_ON',true);
        if($this->user['is_lock'] == 1)
            $this->ajaxReturn(['status'=>0,'msg'=>'账号异常已被锁定！']);
        if(IS_POST)
        {
            if(!$this->verifyHandle('withdrawals')){
                $this->ajaxReturn(['status'=>0,'msg'=>'验证码错误']);
            }
            if(session('__token__') !== I('post.__token__','')){
                $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
            };
            $data['bank_name']      = trim(I('post.bank_name',''));
            $data['bank_card']   = trim(I('post.bank_card/d',''));
            $data['realname']   = trim(I('post.realname',''));
            $data['money']          = floatval(I('post.money/f',0));
    		$data['user_id']        = $this->user_id;
    		$data['create_time']    = time();
            $distribut_min = tpCache('basic.min'); // 最少提现额度
            $distribut_need  = tpCache('basic.need'); //满多少才能提
            if($data['money'] < $distribut_min)
            {
                $this->ajaxReturn(['status'=>0,'msg'=>'每次最少提现额度'.$distribut_min]);
            }
            if($data['money'] > $this->user['user_money'])
            {
                $this->ajaxReturn(['status'=>0,'msg'=>"你最多可提现{$this->user['user_money']}账户余额."]);
            } 
            if($this->user['user_money']<$distribut_need){
                $this->ajaxReturn(['status'=>0,'msg'=>'账户余额最少达到'.$distribut_need.'才能提现']);
            }    

            $withdrawal = M('withdrawals')->where(array('user_id'=>$this->user_id,'status'=>0))->sum('money');
            if($this->user['user_money'] < ($withdrawal + $data['money'])){
            	$this->ajaxReturn(['status'=>0,'msg'=>'您有提现申请待处理，本次提现余额不足']);
            }
    		if(M('withdrawals')->add($data)){
    			$bank['bank_name'] = $data['bank_name'];
    			$bank['bank_card'] = $data['account_bank'];
    			$bank['realname'] = $data['account_name'];
    			M('users')->where(array('user_id'=>$this->user_id))->save($bank);
    			$this->ajaxReturn(['status'=>1,'msg'=>"已提交申请",'url'=>U('Mobile/User/withdrawals_list')]);
    		}else{
    			$this->ajaxReturn(['status'=>0,'msg'=>'提交失败,联系客服!']);
    		}
            exit;
        }
        return $this->fetch();
    }
    /**
     * 申请提现记录列表
     */
    public function withdrawals_list()
    {
        $withdrawals_where['user_id'] = $this->user_id;
        $count = M('withdrawals')->where($withdrawals_where)->count();
        $pagesize = C('PAGESIZE') == 0 ? 10 : C('PAGESIZE');
        $page = new Page($count, $pagesize);
        $list = M('withdrawals')->where($withdrawals_where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('list', $list); // 下线
        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
        }
        return $this->fetch();
    }
    
    /**
     * 我的关注
     * @author lhb
     * @time   2017/4
     */
    public function myfocus()
    {
        /* 获取收藏的商家数量 */
        $sc_id = I('get.sc_id/d');
        $storeLogic = new StoreLogic();
        $storeNum = $storeLogic->getCollectNum($this->user_id, $sc_id);
        /* 获取收藏的商品数量 */
        $goodsNum = M('goods_collect')->where(array('user_id'=>$this->user_id))->count();
        $this->assign('storeNum', $storeNum);
        $this->assign('goodsNum', $goodsNum);
        
        $type = I('get.focus_type/d', 0);
        if ($type == 0) {
            //商品收藏
            $userLogic = new UsersLogic();
            $data = $userLogic->get_goods_collect($this->user_id);
            $this->assign('goodsList', $data['result']);
        } else {
            //店铺收藏
            $data= $storeLogic->getCollectStore($this->user_id, $sc_id);
            $this->assign('storeList', $data['result']);
        }
        
        if (I('get.is_ajax')) {
            return $this->fetch('ajax_myfocus');
        }
        return $this->fetch();
    }

        /*
     *取消收藏
     */
    public function del_goods_focus()
    {
        $collect_id = I('collect_id/d');
        $user_id = $this->user_id;
        if (M('goods_collect')->where(["collect_id" => $collect_id, "user_id" => $user_id])->delete()) {
            $this->success("取消收藏成功", U('User/myfocus'));
        } else {
            $this->error("取消收藏失败", U('User/myfocus'));
        }
    }

    /**
     *  删除一个收藏店铺
     * @author lxl
     * @time17-3-28
     */
    public function del_store_focus()
    {
        $id = I('get.log_id/d');
        if (!$id) {
            $this->error("缺少ID参数");
        }
        $store_id = M('store_collect')->where(array('log_id' => $id, 'user_id' => $this->user_id))->getField('store_id');
        $row = M('store_collect')->where(array('log_id' => $id, 'user_id' => $this->user_id))->delete();
        if ($row){
            M('store')->where(array('store_id' => $store_id))->setDec('store_collect');
            $this->success("取消收藏成功", U('User/myfocus', 'focus_type=1'));
        } else {
            $this->error("取消收藏失败", U('User/myfocus', 'focus_type=1'));
        }
    }

    /**
     *  用户消息通知
     */
    public function message_notice()
    {
        $messageModel = new MessageLogic();
        $messages = $messageModel->getUserAllMaskMessage();
        foreach ($messages as $key => &$message) {
            if ($message['category'] == 1) {
                $message['category_name'] = '物流通知';
            } elseif ($message['category'] == 2) {
                $message['category_name'] = '优惠促销';
            } elseif ($message['category'] == 3) {
                $message['category_name'] = '商品提醒';
            } elseif ($message['category'] == 4) {
                $message['category_name'] = '我的资产';
            } elseif ($message['category'] == 5) {
                $message['category_name'] = '商城好店';
            } else {
                $message['category_name'] = '系统通知';
            }
        }
        $this->assign('messages', $messages);  
        return $this->fetch('user/message_notice');
    }

    /**
     * ajax用户消息通知请求
     * @author dyr
     * @time 2016/09/01
     */
    public function ajax_message_notice()
    {
        $type = I('type', 0);
        $user_logic = new UsersLogic();
        $message_model = new MessageLogic();
        if ($type == 1) {
            //系统消息
            $user_sys_message = $message_model->getUserMessageNotice();
            $user_logic->setSysMessageForRead();
        } else if ($type == 2) {
            //活动消息：后续开发
            $user_sys_message = array();
        } else {
            //全部消息：后续完善
            $user_sys_message = $message_model->getUserMessageNotice();
        }
        $this->assign('messages', $user_sys_message);
        return $this->fetch('user/ajax_message_notice');

    }

    /**
     * 消息开关
     */
    public function message_switch()
    {
        $messageModel = new \app\common\logic\MessageLogic;
        $notice = $messageModel->getMessageSwitch($this->user['message_mask']);
        
        $this->assign('notice', $notice);
        return $this->fetch();
    }

    /**
     * 清除消息
     */
    public function clear_message()
    {
        $messageModel = new \app\common\logic\MessageLogic;
        $messageModel->setMessageRead($this->user_id);
        return $this->redirect('user/message_notice');
    }
    
    /**
     * 异步设置消息
     */
    public function set_message_switch()
    {
        if (!$this->user) {
            ajaxReturn(['status' => -1, 'msg' => '用户不存在']);
        }
        
        $type = I('post.type/d', 0); //开关类型
        $val = I('post.val', 0); //开关值
        
        $messageModel = new \app\common\logic\MessageLogic;
        $return = $messageModel->setMessageSwitch($type, $val, $this->user);
        ajaxReturn($return);
    }    
    
    /**
     * 浏览记录
     */
    public function visit_log()
    {
        $p = I('get.p', 1);

        $user_logic = new UsersLogic;
        $visit_list = $user_logic->visit_log($this->user_id, $p);
        
        $this->assign('visit_list', $visit_list);        
        if (I('get.is_ajax', 0)) {
            return $this->fetch('ajax_visit_log');
        }
        return $this->fetch();
    }
    
    /**
     * 删除浏览记录
     */
    public function del_visit_log()
    {
        $visit_ids = I('get.visit_ids', 0);
        $row = M('goods_visit')->where('visit_id','IN', $visit_ids)->delete();
        
        if(!$row) {
            $this->error('操作失败',U('User/visit_log'));
        } else {
            $this->success("操作成功",U('User/visit_log'));
        }
    }
    
    /**
     * 清空浏览记录
     */
    public function clear_visit_log()
    {
        $row = M('goods_visit')->where('user_id', $this->user_id)->delete();
        
        if(!$row) {
            $this->error('操作失败',U('User/visit_log'));
        } else {
            $this->success("操作成功",U('User/visit_log'));
        }
    }

    /**
     * 支付密码
     * @return mixed
     */
    public function paypwd()
    {
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if ($user['mobile'] == '')
            $this->error('请先绑定手机号或者邮箱',U('User/userinfo',['action'=>'mobile']));
        $step = I('step', 1);
        if ($step > 1) {
            $check = session('validate_code');
            if (empty($check)) {
                $this->error('验证码还未验证通过', U('Home/User/paypwd'));
            }
        }
        if (IS_POST && $step == 2) {
            $oldpaypwd = trim(I('old_paypwd'));
            $new_password = trim(I('new_password'));
            $confirm_password = trim(I('confirm_password'));
            $user = $this->user;
            //以前设置过就得验证原来密码
            if(!empty($user['paypwd']) && ($user['paypwd'] != encrypt($oldpaypwd))){
                $this->ajaxReturn(['status'=>-1,'msg'=>'原密码验证错误！','result'=>'']);
            }
            $userLogic = new UsersLogic();
            $data = $userLogic->paypwd($this->user_id, $new_password, $confirm_password);
            $this->ajaxReturn($data);
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }
}