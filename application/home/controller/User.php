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
namespace app\home\controller;

use app\common\logic\ActivityLogic;
use app\common\logic\MessageLogic;
use app\common\logic\OrderLogic;
use app\common\logic\UsersLogic;
use app\common\logic\CartLogic;
use app\common\logic\StoreLogic;
use app\common\model\GoodsCollect;
use app\common\model\GoodsVisit;
use think\Db;
use think\Page;
use think\Verify;


class User extends Base
{

    public $user_id = 0;
    public $user = array();

    public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->cache(true,10)->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
            $this->assign('user_id', $this->user_id);
            //获取用户信息的数量
            $messageLogic = new MessageLogic();
            $user_message_count = $messageLogic->getUserMessageCount();
            $this->assign('user_message_count', $user_message_count);
        } else {
            $nologin = array(
                'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
                'verifyHandle', 'reg', 'send_sms_reg_code', 'identity', 'check_validate_code',
                'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code',
            );
            if (!in_array(ACTION_NAME, $nologin)) {
                header("location:" . U('Home/User/login'));
                exit;
            }
            if (ACTION_NAME == 'password') $_SERVER['HTTP_REFERER'] = U("Home/User/index");
        }

        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user', $navigate_user);
    }

    /*
     * 用户中心首页
     */
    public function index()
    {
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $logic = new UsersLogic();
        $order = new \app\common\model\Order();
        $user = $logic->getHomeUserInfo($this->user_id);
        $user = $user['result'];
        $order_obj = M('order'.$select_year)->where(['user_id'=>$user['user_id'],'deleted'=>0,'order_prom_type'=>['lt',5]])->order('order_id DESC')->find();
        //先判断是否存在, 否则新注册用户进入用户中心报错
        if($order_obj && $order_obj['order_id'] && $order_obj['store_id']){
            $order_obj['order_status_detail'] = $order->getOrderStatusDetailAttr(null,$order_obj);
            $order_obj['order_button'] = $order->getOrderButtonAttr(null,$order_obj);
            $order_obj['order_goods'] = M('order_goods'.$select_year)->cache(true,3)->where('order_id = '.$order_obj['order_id'])->select();
            $order_obj['store'] = M('store')->cache(true)->where('store_id = '.$order_obj['store_id'])->field('store_id,store_name,store_qq')->find();
            $collect_result =Db::name('goods_collect')->alias('c')->field('c.*,g.shop_price')
                ->join('goods g','c.goods_id = g.goods_id','INNER')->where("c.user_id = ".$user['user_id'])
                ->order('collect_id')->select(); //收藏商品
        }
        $level = M('user_level')->cache(true)->select();
        $level = convert_arr_key($level, 'level_id');
        $this->assign('level', $level);
        $this->assign('collect_result', $collect_result);
        $this->assign('user', $user);
        $this->assign('order', $order_obj);
        return $this->fetch();
    }


    public function logout()
    {
        setcookie('uname', '', time() - 3600, '/');
        setcookie('cn', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        setcookie('PHPSESSID','',time()-3600,'/');
        session_unset();
        session_destroy();
        $this->redirect(U('User/login'));
    }

    /*
     * 账户资金
     */
    public function account()
    {
        $user = session('user');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id, I('get.type'), I('order_sn'), I('order_start'), I('order_end'), I('desc'));
        $account_log = $data['result'];
        $this->assign('user', $user);
        $this->assign('account_log', $account_log);
        $this->assign('page', $data['show']);
        $this->assign('active', 'account');
        return $this->fetch();
    }

    /*
     * 优惠券列表
     */
    public function coupon()
    {
        $belone = I('belone_type/d',0);//0:全部,1:自营店, 2:商家
        $logic = new UsersLogic();
        $data = $logic->get_coupon($this->user_id, I('type'), I('order') , $belone);
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
        $this->assign('active', 'coupon');
        return $this->fetch();
    }
    
    /*
     * 删除优惠券
     */
    public function del_coupon()
    {
        $list_id = I('list_id/d',0);
        $row = M('coupon_list')->where('id' , $list_id)->update(['deleted' => 1]);
        if($row){
            $res = array('status'=>1 , 'msg'=>'删除成功');
        }else{
            $res = array('status'=>-1 , 'msg'=>'删除失败');
        }
        exit(json_encode($res));
    }

    /**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
            $this->redirect(U('User/index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl', $referurl);
        return $this->fetch();
    }

    public function pop_login()
    {
//        if ($this->user_id > 0) {
//            $this->redirect(U('User/index'));
//        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl', $referurl);
        return $this->fetch();
    }

    public function do_login()
    {
        $username = I('post.username');
        $password = I('post.password');
        $username = trim($username);
        $password = trim($password);
        $verify_code = I('post.verify_code');

        $verify = new Verify();
        if (!$verify->check($verify_code, 'user_login')) {
            $res = array('status' => 0, 'msg' => '验证码错误');
            exit(json_encode($res));
        }

        $logic = new UsersLogic();
        $res = $logic->login($username, $password);

        if ($res['status'] == 1) {
            $res['url'] = urldecode(I('post.referurl'));
            $res['result']['nickname'] = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('user_id', $res['result']['user_id'], null, '/');
            setcookie('is_distribut', $res['result']['is_distribut'], null, '/');
            setcookie('uname', urlencode($res['result']['nickname']), null, '/');
            setcookie('head_pic', urlencode($res['result']['head_pic']), null, '/');
            setcookie('cn', 0, time() - 3600, '/');
            session('user', $res['result']);
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
        if ($this->user_id > 0) $this->redirect(U('User/index'));
        $reg_sms_enable = tpCache('sms.regis_sms_enable');
        $reg_smtp_enable = tpCache('smtp.regis_smtp_enable');
        if (IS_POST) {
            $logic = new UsersLogic();
            //验证码检验
            $username = I('post.username', '');
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            $code = I('post.code', '');
            $scene = I('post.scene', 1);
            $session_id = session_id();
            if(check_mobile($username)){
                if($reg_sms_enable){   //是否开启注册验证码机制
                    //手机功能没关闭
                    $check_code = $logic->check_validate_code($code, $username, 'phone', $session_id, $scene);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn(['status'=>0,'msg'=>$check_code['msg'],'result'=>'']);
                    }
                }else{
                    $verify = $this->verifyHandle('user_reg');
                    if(!$verify){
                        $this->ajaxReturn(['status'=>0,'msg'=>'图像验证码有误','result'=>'']);
                    }
                }
            }
            if(check_email($username)){
                if($reg_smtp_enable){        //是否开启注册邮箱验证码机制
                    //邮件功能未关闭
                    $check_code = $logic->check_validate_code($code, $username);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn(['status'=>0,'msg'=>$check_code['msg'],'result'=>'']);
                        $this->error($check_code['msg']);
                    }
                }else{
                    $verify = $this->verifyHandle('user_reg');
                    if(!$verify){
                        $this->ajaxReturn(['status'=>0,'msg'=>'图像验证码有误','result'=>'']);
                    }
                }
            }
            $data = $logic->reg($username, $password, $password2);
            if ($data['status'] != 1) {
                $this->ajaxReturn($data);
            }
            session('user', $data['result']);
            setcookie('user_id', $data['result']['user_id'], null, '/');
            setcookie('is_distribut', $data['result']['is_distribut'], null, '/');
            $nickname = empty($data['result']['nickname']) ? $username : $data['result']['nickname'];
            setcookie('uname', $nickname, null, '/');
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($data['result']['user_id']);
            $cartLogic->doUserLoginHandle();  //用户登录后 需要对购物车 一些操作
            $this->ajaxReturn($data);
        }
        $this->assign('regis_sms_enable', tpCache('sms.regis_sms_enable')); // 注册启用短信：
        $this->assign('regis_smtp_enable', tpCache('smtp.regis_smtp_enable')); // 注册启用邮箱：
        $sms_time_out = tpCache('sms.sms_time_out') > 0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
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
        $this->assign('active', 'address_list');

        return $this->fetch();
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
        header("Content-type:text/html;charset=utf-8");
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, I('post.'));
            if ($data['status'] != 1)
                exit('<script>alert("' . $data['msg'] . '");history.go(-1);</script>');
            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province', $p);
        return $this->fetch('edit_address');

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        header("Content-type:text/html;charset=utf-8");
        $id = I('get.id/d');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($data['status'] != 1)
                exit('<script>alert("' . $data['msg'] . '");history.go(-1);</script>');

            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
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

    /**
     * 设置默认收货地址
     */
    public function setAddressDefault()
    {
        $id = input('id/d');
        Db::name('user_address')->where(['user_id'=>$this->user_id])->update(['is_default' => 0]);
        $row = Db::name('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->update(array('is_default' => 1));
        if ($row !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'设置成功','result'=>'']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'设置失败','result'=>$row]);
        }
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
        $id = I('get.id/d');

        $address = M('user_address')->where("address_id", $id)->find();
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = M('user_address')->where("user_id", $this->user_id)->find();
            $address2 && M('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if (!$row)
            $this->error('操作失败', U('User/address_list'));
        else
            $this->success("操作成功", U('User/address_list'));
    }

    /**
     * 个人信息
     */
    public function info()
    {
        $userLogic = new UsersLogic();
        $user_info = M('users')->where('user_id', $this->user_id)->find();
        if (IS_POST) {
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            if (!$userLogic->update_info($this->user_id, $post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        if($user_info['province'])
        {
            //  获取省份
            $province = M('region')->cache(true)->where(array('parent_id' => 0, 'level' => 1))->select();
            //  获取订单城市
            $city = M('region')->cache(true)->where(array('parent_id' => $user_info['province'], 'level' => 2))->select();
            //获取订单地区
            $area = M('region')->cache(true)->where(array('parent_id' => $user_info['city'], 'level' => 3))->select();
        }
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('user', $user_info);
        $this->assign('sex', C('SEX'));
        $this->assign('active', 'info');
        return $this->fetch();
    }

    /*
     * 邮箱验证
     */
    public function email_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = M('users')->where('user_id', $this->user_id)->find();
        $step = I('get.step', 1);
        if (IS_POST) {
            $email = I('post.email');
            $old_email = I('post.old_email'); //旧邮箱
            $code = I('post.code');
            $info = session('validate_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['time'] < time()) {
                session('validate_code', null);
                $this->error('验证超时，请重新验证');
            }
            //检查原邮箱是否正确
            if ($user_info['email_validated'] == 1 && $old_email != $user_info['email'])
                $this->error('原邮箱匹配错误');
            //验证邮箱和验证码
            if ($info['sender'] == $email && $info['code'] == $code) {
                session('validate_code', null);
                if (!$userLogic->update_email_mobile($email, $this->user_id))
                    $this->error('邮箱已存在');
                $this->success('绑定成功', U('Home/User/index'));
                exit;
            }
            $this->error('邮箱验证码不匹配');
        }
        $this->assign('step', $step);
        $this->assign('user_info', $user_info);
        return $this->fetch();
    }


    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); //获取用户信息
        $user_info = $user_info['result'];
        $config = tpCache('sms');
        $sms_time_out = $config['sms_time_out'];
        $step = I('get.step', 1);
        if (IS_POST) {
            $mobile = I('post.mobile');
            $old_mobile = I('post.old_mobile');
            $code = I('post.code');
            $scene = I('post.scene', 6);
            $session_id = I('unique_id', session_id());

            $logic = new UsersLogic();
            $res = $logic->check_validate_code($code, $mobile, 'phone', $session_id, $scene);

            if (!$res && $res['status'] != 1) $this->error($res['msg']);

            //检查原手机是否正确
            if ($user_info['mobile_validated'] == 1 && $old_mobile != $user_info['mobile'])
                $this->error('原手机号码错误');
            //验证手机和验证码

            if ($res['status'] == 1) {
                //验证有效期
                if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                    $this->error('手机已存在');
                $this->success('绑定成功', U('Home/User/index'));
                exit;
            } else {
                $this->error($res['msg']);
            }

        }
        $this->assign('time', $sms_time_out);
        $this->assign('step', $step);
        $this->assign('user_info', $user_info);
        return $this->fetch();
    }

    /**
     *我的收藏
     */
    public function goods_collect()
    {
        $type = I('get.collect_type/d', 1);
        $show_type = I('get.show_type/d', -1);   //-1: 全部商品, 2:活动商品
        if ($type == 1) {
            //商品收藏
            $userLogic = new UsersLogic();
            
            $data = $userLogic->get_goods_collect($this->user_id , -1);//全部商品
            $prom_data = $userLogic->get_goods_collect($this->user_id , 2);//活动商品
            if($show_type==-1){//全部
                $this->assign('lists', $data['result']);
                $this->assign('promPager', $prom_data['page']);
                $this->assign('pager', $data['page']);
            }else{//活动
                $this->assign('lists', $prom_data['result']);
                $this->assign('promPager', $prom_data['page']);
                $this->assign('pager', $data['page']);
            }
            $this->assign('page', $data['show']);// 赋值分页输出
            $this->assign('active', 'goods_collect');
            return $this->fetch();
        } else {
            //店铺收藏
            $sc_id = I('get.sc_id/d');
            $store_class = M('store_class')->field('sc_id,sc_name')->where('')->select();
            $storeLogic = new StoreLogic();
            $store_collect_list = $storeLogic->getCollectStore($this->user_id, $sc_id);
            $this->assign('page', $store_collect_list['show']);// 赋值分页输出
            $this->assign('store_collect_list', $store_collect_list['result']);
            $this->assign('store_class', $store_class);//店铺分类
            return $this->fetch('bookmark');
        }
    }

    public function myCollect()
    {
        $item = input('item', 12);
        $goodsCollectModel = new GoodsCollect();
        $user_id = $this->user_id;
        $goodsList = $goodsCollectModel->with('goods')->where('user_id', $user_id)->limit($item)->order('collect_id', 'desc')->select();
        foreach($goodsList as $key=>$goods){
            $goodsList[$key]['url'] = $goods->url;
            $goodsList[$key]['imgUrl'] = goods_thum_images($goods['goods_id'], 160, 160);
        }
        if ($goodsList) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $goodsList]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '没有记录', 'result' => '']);
        }
    }

    /*
     * 删除一个收藏商品
     */
    public function del_goods_collect()
    {
        $id = I('get.id');//这里为字符串
        if (!$id)
            $this->error("缺少ID参数");
        $row = M('goods_collect')->where(array('collect_id' => array('in', $id), 'user_id' => $this->user_id))->delete();
        if (!$row)
            $this->error("删除失败");
        $this->success('删除成功');
    }

    /**
     *  删除一个收藏店铺
     */
    public function del_store_collect()
    {
        $id = I('get.log_id/d');
        if (!$id)
            $this->ajaxReturn(['status'=>0,'msg'=>"参数错误", 'url'=>U('User/goods_collect',['collect_type'=>2])]);
        $store_id = M('store_collect')->where(array('log_id' => $id, 'user_id' => $this->user_id))->getField('store_id');
        $row = M('store_collect')->where(array('log_id' => $id, 'user_id' => $this->user_id))->delete();
        M('store')->where(array('store_id' => $store_id))->setDec('store_collect');
        if ($row){
            $this->ajaxReturn(['status'=>1,'msg'=>"取消成功", 'url'=>U('User/goods_collect',['collect_type'=>2])]);
        } else {
            $this->ajaxReturn(['status'=>0,'msg'=>"取消失败", 'url'=>U('User/goods_collect',['collect_type'=>2])]);
        }
    }

    /*
     * 密码修改
     */
    public function password()
    {
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if ($user['mobile'] == '' && $user['email'] == '')
            $this->error('请先绑定手机或邮箱', U('Home/User/info'));
        $step = I('step', 1);
        if ($step > 1) {
            $check = session('validate_code');
            if (empty($check)) {
                $this->error('验证码还未验证通过', U('Home/User/password'));
            }
        }
        if (IS_POST && $step == 3) {
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id, I('new_password'), I('confirm_password'));
            if ($data['status'] == -1) $this->error($data['msg']);
            $this->redirect(U('Home/User/password', array('step' => 3)));
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    public function paypwd()
    {
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if ($user['mobile'] == '')
            $this->error('请先绑定手机',U('User/mobile_validate',['action'=>'mobile']));
        $step = I('step', 1);
        if ($step > 1) {
            $check = session('validate_code');
            if (empty($check)) {
                $this->error('验证码还未验证通过', U('Home/User/paypwd'));
            }
        }
        if (IS_POST && $step == 3) {
            $userLogic = new UsersLogic();
            $oldpaypwd = trim(I('old_paypwd'));
            //以前设置过就得验证原来密码
            if(!empty($user['paypwd']) && ($user['paypwd'] != encrypt($oldpaypwd))){
                $this->error('原密码验证错误！');
            }
            $data = $userLogic->paypwd($this->user_id, I('new_password'), I('confirm_password'));
            if ($data['status'] == -1)
                $this->error($data['msg']);
            $this->redirect(U('Home/User/paypwd', array('step' => 3)));
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    public function forget_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/index'));exit;
        }
        if (IS_POST) {
            $username = I('username');
            if (!empty($username)) {
                $field = 'mobile';
                if (check_email($username)) {
                    $field = 'email';
                }
                $user = M('users')->where("email", $username)->whereOr('mobile', $username)->find();
                if ($user) {
                    session('find_password', array('user_id' => $user['user_id'], 'username' => $username,
                        'email' => $user['email'], 'mobile' => $user['mobile'], 'type' => $field));
                    header("Location: " . U('User/identity'));
                    exit;
                } else {
                    $this->error("用户名不存在，请检查");
                }
            }
        }
        return $this->fetch();
    }

    public function set_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/index'));exit;
        }
        $check = session('validate_code');
        $logic = new UsersLogic();
        if (empty($check)) {
            header("Location:" . U('Home/User/forget_pwd'));exit;
        } elseif ($check['is_check'] == 0) {
            $this->error('验证码还未验证通过', U('Home/User/forget_pwd'));
        }
        if (IS_POST) {
            $password = I('post.password');
            $password2 = I('post.password2');
            if ($password2 != $password) {
                $this->error('两次密码不一致', U('Home/User/forget_pwd'));
            }
            if ($check['is_check'] == 1) {
                $user = M('users')->where("mobile", $check['sender'])->whereOr('email', $check['sender'])->find();
                if ($user) {
                    if (M('users')->where("user_id", $user['user_id'])->save(array('password' => encrypt($password)))) {
			session('validate_code',null);
                        header("Location:" . U('Home/User/finished'));exit;
                    } else {
                        $this->error('操作失败，请稍后再试', U('Home/User/forget_pwd'));
                    }
                }
            } else {
                $this->error('验证码还未验证通过', U('Home/User/forget_pwd'));
            }
        }
        return $this->fetch();
    }

    public function finished()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/index'));exit;
        }
        return $this->fetch();
    }

    public function bind_auth()
    {
         header("Content-type: text/html; charset=utf-8");
         exit('请联系TPshop官网客服购买高级版支持此功能'); 
    }

    public function bind_remove()
    {
        $oauth = I('oauth');
        if (M('users')->where(array('user_id' => $this->user_id))->save(array('oauth' => '', 'openid' => '', 'unionid' => ''))) {
            $this->success('解除绑定成功', U('Home/User/bind_auth'));
        } else {
            $this->error('解除绑定失败', U('Home/User/bind_auth'));
        }
    }

    public function check_captcha()
    {
        $verify = new Verify();
        $type = I('post.type', 'user_login');
        if (!$verify->check(I('post.verify_code'), $type)) {
            exit(json_encode(0));
        } else {
            exit(json_encode(1));
        }
    }

    public function check_username()
    {
        $username = I('post.username');
        if (!empty($username)) {
            $count = M('users')->where("email", $username)->whereOr('mobile', $username)->count();
           if($count)$this->ajaxReturn(['status'=>1,'msg'=>'验证成功']);
            $this->ajaxReturn(['status'=>0,'msg'=>'用户名验证有误']);
        } else {
            $this->ajaxReturn(['status'=>0,'msg'=>'请输入用户名']);
        }
    }

    public function identity()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/index'));exit;
        }
        $user = session('find_password');
        if (empty($user)) {
            $this->error("请先验证用户名", U('User/forget_pwd'));
        }
        $this->assign('userinfo', $user);
        $sms_time_out = tpCache('sms.sms_time_out') > 0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        return $this->fetch();
    }

    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        $result = $verify->check(I('post.verify_code'), $id ? $id : 'user_login');
        if (!$result) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
		exit();
    }

    /**
     * 安全设置
     */
    public function safety_settings()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $this->assign('user', $user_info);
        return $this->fetch();
    }

    /**
     * 申请提现记录
     */
    public function withdrawals()
    {
        //C('TOKEN_ON',true);
        if($this->user['is_lock'] == 1)$this->error('账号异常已被锁定！');
        if (IS_POST) {
            //$this->verifyHandle('withdrawals');
            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $data['create_time'] = time();
            $distribut_min = tpCache('basic.min'); // 最少提现额度
            $distribut_need = tpCache('basic.need'); //满多少才能提
            if ($data['money'] < $distribut_min) {
                $this->error('每次最少提现额度' . $distribut_min);
            }
            if ($data['money'] > $this->user['user_money']) {
                $this->error("你最多可提现{$this->user['user_money']}账户余额.");
            }
            if ($this->user['user_money'] < $distribut_need) {
                $this->error('账户余额最少达到' . $distribut_need . '才能提现');
            }

            $withdrawal = M('withdrawals')->where(array('user_id' => $this->user_id, 'status' => 0))->sum('money');
            if ($this->user['user_money'] < ($withdrawal + $data['money'])) {
                $this->error('您有提现申请待处理，本次提现余额不足');
            }

            if (encrypt($data['paypwd']) != $this->user['paypwd']) {
                $this->error('支付密码错误');
            } else {
                if (M('withdrawals')->add($data)) {
                    $bank['bank_name'] = $data['bank_name'];
                    $bank['bank_card'] = $data['bank_card'];
                    $bank['realname'] = $data['realname'];
                    M('users')->where(array('user_id' => $this->user_id))->save($bank);
                    $this->success("已提交申请");
                    exit;
                } else {
                    $this->error('提交失败,联系客服!');
                }
            }
        }
        $Userlogic = new UsersLogic();
        $result = $Userlogic->get_withdrawals_log($this->user_id);  //提现记录
        $this->assign('show',$result['show']);//赋值分页输出
        $this->assign('list',$result['result']); //下线
        return $this->fetch();
    }


    public function recharge()
    {
        if (IS_POST) {
            $user = session('user');
            $data['user_id'] = $this->user_id;
            $data['nickname'] = $user['nickname'];
            $data['account'] = I('account');
            $data['order_sn'] = 'recharge' . get_rand_str(10, 0, 1);
            $data['ctime'] = time();
            $order_id = M('recharge')->add($data);
            if ($order_id) {
                $url = U('Home/Payment/getPay', array('pay_radio' => $_REQUEST['pay_radio'], 'order_id' => $order_id));
                $this->redirect($url);
            } else {
                $this->error('提交失败,参数有误!');
            }
        }
        $paymentList = M('Plugin')->cache(true)->where("`type`='payment' and code!='cod' and status = 1 and scene in(0,2)")->select();
        $paymentList = convert_arr_key($paymentList, 'code');
        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        $this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
        $this->assign('bankCodeList', $bankCodeList);

        $type = I('type');
        $Userlogic = new UsersLogic();
        if($type == 1){
            $result=$Userlogic->get_account_log($this->user_id);  //用户资金变动记录
        }else if($type == 2){
            $result=$Userlogic->get_withdrawals_log($this->user_id);  //提现记录
        }else{
            $result=$Userlogic->get_recharge_log($this->user_id);  //充值记录
        }
        $this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        return $this->fetch();
    }

    /**
     *  用户消息通知
     * @author dyr
     * @time 2016/09/01
     */
    public function message_notice()
    {
        return $this->fetch();
    }

    /**
     * ajax用户消息通知请求
     * @author dyr
     * @time 2016/09/01
     */
    public function ajax_message_notice()
    {
        $type = I('type');
        $user_logic = new UsersLogic();
        $message_model = new MessageLogic();
        if ($type == 0) {
            //系统消息
            $user_sys_message = $message_model->getUserMessageNotice();
        } else if ($type == 1) {
            //活动消息
            $user_sys_message = $message_model->getUserSellerMessage();
        } else {
            //全部消息
            $user_sys_message = $message_model->getUserAllMessage();
        }
        $this->assign('messages', $user_sys_message);
        echo $this->fetch();
    }

    /**
     * ajax用户消息通知请求
     * @time 2016/09/01
     */
    public function set_message_notice()
    {
        $type = I('type');
        $msg_id = I('msg_id');
        $user_logic = new UsersLogic();
        $res = $user_logic->setMessageForRead($type,$msg_id);
        $this->ajaxReturn($res);
    }

    /**
     * 删除足迹
     */
    public function del_visit_log(){
        
        $visit_id = I('visit_id/d' , 0);
        $row = M('goods_visit')->where(array('visit_id'=>$visit_id))->delete();
        if($row>0){
            return $this->ajaxReturn(['status'=>1 , 'msg'=> '删除成功']);
        }else{
            return $this->ajaxReturn(['status'=>-1 , 'msg'=> '删除失败']);
        }
    }

    public function visit_log()
    {
        $cat_id3 = I('cat_id3', 0);
        $map['user_id'] = $this->user_id;
        $visit_total = M('goods_visit a')->where($map)->count();
        if ($cat_id3 > 0) $map['a.cat_id3'] = $cat_id3;
        $count = M('goods_visit a')->where($map)->count();
        $Page = new Page($count, 50);
        $visit_list = M('goods_visit a')->field("a.*,g.goods_name,g.shop_price")
            ->join('__GOODS__ g', 'a.goods_id = g.goods_id', 'LEFT')->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)->order('a.visittime desc')->select();
        $visit_log = $cates = array();
        if ($visit_list) {
            $now = time();
            $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            foreach ($visit_list as $k => $val) {
                if ($now - $val['visittime'] < 3600 * 24 * 7) {
                    if (date('Y-m-d') == date('Y-m-d', $val['visittime'])) {
                        $val['date'] = '今天';
                    } else {
                        if ($val['visittime'] < $endLastweek) {
                            $val['date'] = "上周" . $weekarray[date("w", $val['visittime'])];
                        } else {
                            $val['date'] = "周" . $weekarray[date("w", $val['visittime'])];
                        }
                    }
                } else {
                    $val['date'] = '更早以前';
                }
                $cat_ids[] = $val['cat_id3'];
                $visit_log[$val['date']][] = $val;
            }
            $cateArr = M('goods_category')->cache(true)->where(array('id' => array('in', $cat_ids)))->getField('id,name');
            $cates = M('goods_visit a')->field('cat_id3,COUNT(cat_id3) as csum')->where($map)->group('cat_id3')->select();
            foreach ($cates as $k => $v) {
                if (isset($cateArr[$v['cat_id3']])) $cates[$k]['name'] = $cateArr[$v['cat_id3']];
            }
        }
        $this->assign('visit_total', $visit_total);
        $this->assign('catids', $cates);
        $this->assign('page', $Page->show());
        $this->assign('visit_log', $visit_log);//浏览记录
        return $this->fetch();
    }

    /**
     * 历史记录
     */
    public function historyLog(){
        $item = input('item', 12);
        $goodsCollectModel = new GoodsVisit();
        $user_id = $this->user_id;
        $goodsList = $goodsCollectModel->with('goods')->where('user_id', $user_id)->limit($item)->order('visit_id', 'desc')->select();
        foreach($goodsList as $key=>$goods){
            $goodsList[$key]['url'] = $goods->url;
            $goodsList[$key]['imgUrl'] = goods_thum_images($goods['goods_id'], 160, 160);
        }
        if ($goodsList) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $goodsList]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '没有记录', 'result' => '']);
        }
    }

    /**
     * 用户领取优惠券
     */
    public function getCoupon(){
        $coupon_id = input('coupon_id');
        if(empty($coupon_id)){
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择要领取的优惠券', 'result' => '']);
        }
        $activityLogic = new ActivityLogic();
        $return = $activityLogic->get_coupon($coupon_id, $this->user_id);
        $this->ajaxReturn($return);
    }
}