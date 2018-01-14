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
namespace app\api\controller;

use app\common\logic\GoodsLogic;
use app\common\logic\DistributLogic;
use think\Db;

class Distribut extends Base 
{
    /**
     * 分销用户中心首页（分销中心）
     */
    public function index()
    {
        // 销售额 和 我的奖励
        $result = DB::query("select sum(goods_price) as goods_price, sum(money) as money from __PREFIX__rebate_log where user_id = {$this->user_id}");
        $result = $result[0];
        $result['goods_price'] = $result['goods_price'] ?: 0;
        $result['money'] = $result['money'] ?: 0;

        $lower_count[] = Db::name('users')->where("first_leader", $this->user_id)->count();
        $lower_count[] = Db::name('users')->where("second_leader", $this->user_id)->count();
        $lower_count[] = Db::name('users')->where("third_leader", $this->user_id)->count();


        $result2 = DB::query("select status,count(1) as c , sum(goods_price) as goods_price from `__PREFIX__rebate_log` where user_id = :user_id group by status",['user_id'=>$this->user_id]);
        $level_order = convert_arr_key($result2, 'status');
        for ($i = 0; $i <= 5; $i++) {
            $level_order[$i]['c'] = $level_order[$i]['c'] ? $level_order[$i]['c'] : 0;
            $level_order[$i]['goods_price'] = $level_order[$i]['goods_price'] ? $level_order[$i]['goods_price'] : 0;
        }

        $money['withdrawals_money'] = Db::name('withdrawals')->where(['user_id'=>$this->user_id, 'status'=>1])->sum('money') ?: 0; // 已提现财富
        $money['achieve_money'] = Db::name('rebate_log')->where(['user_id'=>$this->user_id,'status'=>3])->sum('money') ?: 0;  //累计获得佣金
        $time=strtotime(date("Y-m-d"));
        $money['today_money'] = Db::name('rebate_log')->where("user_id=$this->user_id and status in(2,3) and create_time>$time")->sum('money') ?: 0;    //今日收入

        $store = Db::name('user_store')->field('store_time,store_name')->where("user_id", $this->user_id)->find();
         
        $user = [
            'nickname'      => $this->user['nickname'],
            'head_pic'      => empty($this->user['head_pic']) ? "" : $this->user['head_pic'],
            'user_money'    => $this->user['user_money'],
        ];
                
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => [
            'level_order'   => $level_order,            // 下线订单
            'lower_count'   => $lower_count,            // 下线人数
            'sales_volume'  => $result['goods_price'],  // 销售额
            'reward'        => $result['money'],        // 奖励
            'money'         => $money,
            'store_time'    => $store['store_time'] ?: 0,
            'store_name'    => $store['store_name'] ?: '',
            'user'          => $user
        ]]);
    }

    /**
     * 下线列表(我的团队)
     */
    public function lower_list() {
        if ($this->user['is_distribut'] != 1) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您还不是分销商']);
        }

        $level = I('get.level', 1);
        $q = I('post.q', '', 'trim');
        
        $logic = new DistributLogic;
        $result = $logic->lowerList($this->user_id, $level, $q);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['lists']]);
    }

    /**
     * 下线订单列表（分销订单）
     */
    public function order_list()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您还不是分销商']);
        }
        
        $status = I('get.status', 0);
        
        $logic = new DistributLogic;
        $result = $logic->orderList($this->user_id, $status);

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['list']]);
    }

    /**
     * 个人推广二维码 （我的名片）
     */
    public function qr_code(){
        $ShareLink = urlencode("http://{$_SERVER[HTTP_HOST]}/index.php?m=Mobile&c=Index&a=index&first_leader={$this->user_id}"); //默认分享链接
        if($this->user['is_distribut'] == 1) {
            $this->assign('ShareLink',$ShareLink);
        }
        $this->assign('user',$this->user);
        return $this->fetch();
    }

    /**
     * 平台分销商品列表
     * @author  lxl
     * @time2017-4-6
     */
    public function goods_list()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您还不是分销商']);
        }
        
        $sort = I('sort', 'goods_id'); // 排序
        $order = I('order', 0); // 排序
        $cat_id = I('cat_id/d', 0);
        $brand_id = I('brand_id/d', 0);//品牌
        $key_word = trim(I('key_word/s', ''));
        
        $logic = new DistributLogic;
        $result = $logic->goodsList($this->user_id, $sort, $order, $cat_id, $brand_id, $key_word);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result['goodsList']]);
    }

    public function goods_types()
    {
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => [
            'categoryList' => $categoryList,//分类
            'brandList' => $brandList,//品牌
        ]]);        
    }
    
    /**
     * 添加分销商品
     * @author  lxl
     * @time2017-4-6
     */
    public function add_goods()
    {
        if (!$this->user_id) {
            $this->redirect('Mobile/User/index');
        }
        $goods_ids = I('post.goods_ids/a', []);
        
        $distributLogic = new DistributLogic;
        $result = $distributLogic->addGoods($this->user, $goods_ids);
        if (!$result) {
            $this->ajaxReturn(['status' => -1, 'msg' => '添加失败']);
        }
        
        $this->ajaxReturn(['status' => 1, 'msg' => '添加成功']);
    }

    /**
     * 店铺设置
     * @author  lxl
     * @time2017-4-6
     */
    public function store()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您还不是分销商']);
        }
        
        if (request()->isGet()) {
            $logic = new DistributLogic;
            $return = $logic->getStoreInfo($this->user_id);
            $this->ajaxReturn($return);
        }
        
        if (request()->isPost()) {
            $storeName = I('store_name', '');
            $trueName = I('true_name', '');
            $mobile = I('mobile', '');
            $qq = I('qq', '');
            $logic = new DistributLogic;
            $result = $logic->setStoreInfo($this->user_id, $storeName, $trueName, $mobile, $qq);

            $this->ajaxReturn($result);
        }
       
        $this->ajaxReturn(['status' => -1, 'msg' => '请求方式不对']);
    }

    /**
     * 用户分销商品
     * @author  lxl
     * @time2017-4-6
     */
    public function my_store()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您还不是分销商']);
        }
        
        $logic = new DistributLogic;
        $goods = $logic->getStoreGoods($this->user_id);
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $goods['list']]);
    }
    
    /**
     * 获取商店的概况信息
     */
    public function store_summery()
    {
        if ($this->user['is_distribut'] != 1) {
            $this->ajaxReturn(['status' => -1, 'msg' => '您还不是分销商']);
        }
        
        $logic = new DistributLogic;
        $wait_add_num = $logic->getUserNotAddGoodsNum($this->user_id);
        $had_add_num = M('user_distribution')->where(['user_id'=>$this->user_id])->count();
        
        $store = M('user_store')->field('store_img,store_name')->where('user_id', $this->user_id)->find();
        $head_pic = M('users')->where('user_id', $this->user_id)->limit(1)->getField('head_pic');
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => [
            'wait_add_num' => $wait_add_num, //未上架商品数
            'had_add_num'  => $had_add_num,  //已上架商品数
            'store'     => $store,
            'head_pic'  => $head_pic
        ]]);        
    }


    /**
     * 新手必看
     * @author  lxl
     * @time2017-4-6
     */
    public function must_see(){
        return $this->fetch();
    }

    /**
     *分销排行
     * @author  lxl
     * @time2017-4-6
     */
    public function rankings()
    {
        $sort = I('get.sort', 'distribut_money');
        $p= I('get.p/d', 1);

        $logic = new DistributLogic;
        $result = $logic->rankings($this->user, $sort, $p);
        
        $this->assign('lists', $result['lists']);
        $this->assign('firstRow', $result['firstRow']);  //当前分页开始数
        $this->assign('place', $result['place']);  
        
        if(I('is_ajax')){
            return $this->fetch('ajax_rankings');
        }
        return $this->fetch();
    }

    /**
     * 分成记录页面
     * @author  lxl
     * @time2017-4-6
     */
    public function rebate_log()
    {
        $status = I('status',''); //日志状态
        $order = I('sort_asc','desc');  //排序
        $sort  = I('sort','create_time'); //排序条件
        
        $logic = new DistributLogic;
        $result = $logic->getRebateLog($this->user_id, $status, $sort, $order);        
        
        $this->assign('lists',$result['list']);
        if(I('is_ajax')){
            return $this->fetch('ajax_rebate_log');
        }
        return $this->fetch();
    }
}
