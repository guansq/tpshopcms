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
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace app\home\controller;
use app\common\logic\ActivityLogic;
use app\common\logic\GoodsLogic;
use app\common\model\Coupon;
use app\common\model\FlashSale;
use app\common\model\Goods;
use app\common\model\GroupBuy;
use think\Page;
use think\Db;

class Activity extends Base {

    /**
     * 团购活动列表
     */
    public function group_list()
    {
        $cat_id = input('cat_id/d');
        $title = input('title');
        $orderBy = input('order');
        $where = array(
            'gb.start_time'        =>array('elt',time()),
            'gb.end_time'          =>array('egt',time()),
            'gb.status'            =>1,
            'gb.recommend'         =>1,
            'gb.is_end'            =>0,
        );
        $order = array();
        if($orderBy == 1){
            //最新
            $order['gb.id'] = 'desc';
        }else if($orderBy == 2){
            //推荐
            $order['gb.recommend'] = 'desc';
        }else{
            $order['gb.id'] = 'asc';
        }
        //分类
        if($cat_id){
            $where['g.cat_id1'] = $cat_id;
        }
        //名称
        if($title){
            $where['gb.title'] = array('like','%'.$title.'%');
        }
        $GroupBuy = new GroupBuy();
    	$count = $GroupBuy->alias('gb')->join('__GOODS__ g', 'g.goods_id = gb.goods_id')->alias('gb')->where($where)->count('gb.goods_id');// 查询满足要求的总记录数
    	$Page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    	$show = $Page->show();// 分页显示输出
    	$this->assign('page',$show);// 赋值分页输出
        $list = $GroupBuy
                    ->alias('gb')
                    ->with(['goods','specGoodsPrice'])
                    ->join('__GOODS__ g', 'g.goods_id = gb.goods_id')
                    ->where($where)
                    ->limit($Page->firstRow.','.$Page->listRows)
                    ->order($order)
                    ->select();
        $cat_list = M('goods_category')->where(array('level'=>1))->select();
        $this->assign('cat_list', $cat_list);
        $this->assign('list', $list);
        $this->assign('pages',$Page);
        return $this->fetch();
    }

    public function flash_sale_list()
    {
        $time_space = flash_sale_time_space();
        $this->assign('time_space', $time_space);
        return $this->fetch();
    }
    /**
     * 抢购活动列表ajax
     */
    public function ajax_flash_sale()
    {
//        $p = I('p',1);
        $start_time = input('start_time');
        $end_time = input('end_time');
        $FlashSale = new FlashSale();
        $where = array(
            'status' => 1,
            'start_time'=>array('egt',$start_time),
            'end_time'=>array('elt',$end_time),
            'recommend'=>1
        );
        $flash_sale_goods = $FlashSale->with(['specGoodsPrice','goods'])->field('*,100*(FORMAT(buy_num/goods_num,2)) as percent')->where($where)->order('recommend desc')->select();
        $this->assign('flash_sale_goods',$flash_sale_goods);
        echo $this->fetch();
    }

    // 促销活动页面
    public function promoteList()
    {
        $goods_where['p.start_time']  = array('lt',time());
        $goods_where['p.end_time']  = array('gt',time());
        $goods_where['p.status']  = 1;
        $goods_where['p.is_end']  = 0;
        $goods_where['g.prom_type']  = 3;
        $goods_where['g.is_on_sale']  = 1;
        $goodsList = Db::name('goods')
            ->field('g.*,p.end_time,s.item_id')
            ->alias('g')
            ->join('__PROM_GOODS__ p', 'g.prom_id = p.id')
            ->join('__SPEC_GOODS_PRICE__ s','g.prom_id = s.prom_id AND s.goods_id = g.goods_id','LEFT')
            ->group('g.goods_id')
            ->where($goods_where)
            ->order('p.recommend desc')
            ->cache(true,5)
            ->select();
        $brandList = M('brand')->cache(true)->getField("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        return $this->fetch();
    }

    /**
     * 领券列表
     * @return mixed
     */
    public function coupon_list()
    {
        $atype = I('atype', 1);
        $p = I('p', 0);
        $user_id = cookie('user_id')?: 0;
        $where = array('type' => 2,'status'=>1,'send_start_time'=>['elt',time()],'send_end_time'=>['egt',time()]);
        if ($atype == 2) {
            //即将过期
            $order = ['send_end_time' => 'asc'];
        } elseif ($atype == 3) {
            //面值最大
            $order = ['money' => 'desc'];
        }else{
            $order = ['id' => 'desc'];
        }
        $Coupon = new Coupon();
        $count = $Coupon->where($where)->count('id');
        $Page = new Page($count,15);
        $show = $Page->show();
        $coupon = $Coupon->where($where)->page($p, 15)->order($order)->select();
        if($coupon) {
            $couponList = collection($coupon)->append(['store', 'goods_coupon', 'use_type_title', 'is_lead_end'])->toArray();
        }
        if($couponList){
            if ($user_id) {
                $user_coupon = Db::name('coupon_list')->where(['uid' => $user_id, 'type' => 2, 'status' => 0])->getField('cid', true);
            }
            foreach ($couponList as $couponKey => $coupon) {
                if (!empty($user_coupon) && in_array($coupon['id'],$user_coupon)) {
                    $couponList[$couponKey]['is_get'] = 1;
                }
                if($coupon['goods_coupon']){
                    $goods_coupon = collection($coupon['goods_coupon'])->append(['goods','goods_category'])->toArray();
                    $use_scope = '';
                    foreach($goods_coupon as $goodsCouponKey =>$goodsCouponVal){
                        if($goodsCouponVal['goods']){
                            $use_scope .= $goodsCouponVal['goods']['goods_name'].',';
                        }
                        if($goodsCouponVal['goods_category']){
                            $use_scope .= $goodsCouponVal['goods_category']['name'].',';
                        }
                    }
                    $couponList[$couponKey]['use_scope'] = trim($use_scope, ',');
                }
            }
        }
        $this->assign('page',$show);
        $this->assign('coupon_list', $couponList);
        return $this->fetch();
    }
    /**
     * 领券
     */
    public function get_coupon()
    {
        $id = I('coupon_id/d');
        $user_id = cookie('user_id')?: 0;
        $activityLogic = new ActivityLogic();
        $return = $activityLogic->get_coupon($id, $user_id);
        $this->assign('res',$return);
        return $this->fetch();
    }

}