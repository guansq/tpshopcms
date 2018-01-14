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
namespace app\api\controller;
use app\common\logic\ActivityLogic;
use app\common\logic\GoodsPromFactory;
use think\Db;
use think\Page;

class Activity extends Base {
    /**
     * @author dyr
     * @time 2016/09/20
     * 团购活动列表
     */
    public function group_list()
    {
        $page_size = I('page_size', 10);
        $p = I('p',1);
        $type = I('type', '');
        
        $activityLogic = new ActivityLogic();
        $groups = $activityLogic->getGroupBuyList($type, $p, $page_size);
        
        // 具体策略待决定
        $ad = M('ad')->field('ad_name,ad_link,ad_code')->find();
        
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=> [
                'groups' => $groups,
                'ad' => $ad,
                'server_current_time' => time()
            ]
        );
        $this->ajaxReturn($json);
    }

    /**
     * @author wangqh
     * 抢购活动时间节点
     */
    public function flash_sale_time()
    {
        $time_space = flash_sale_time_space();
        $times = array();
        foreach ($time_space as $k => $v){
            $times[] = $v;
        }
        
        $ad = M('ad')->field(['ad_link','ad_name','ad_code'])->where('pid', 2)->cache(true, TPSHOP_CACHE_TIME)->find();
        
         $return = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=> [
                'time' => $times,
                'ad' => $ad
            ] ,
        );
        $this->ajaxReturn($return);
    }
    
 
    /**
     * @author wangqh
     * 抢购活动列表
     */
    public function flash_sale_list()
    {
        $p = I('p',1);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = array(
            'f.status' => 1,
            'f.start_time'=>array('egt',$start_time),
            'f.end_time'=>array('elt',$end_time)
        );
         
        $flash_sale_goods = M('flash_sale')
        ->field('f.goods_name,f.price,f.goods_id,f.price,g.shop_price,100*(FORMAT(f.buy_num/f.goods_num,2)) as percent')
        ->alias('f')
        ->join('__GOODS__ g','g.goods_id = f.goods_id')
        ->where($where)
        ->page($p,10)
        ->cache(true,TPSHOP_CACHE_TIME)
        ->select();
        
        $return = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$flash_sale_goods ,
        );
        $this->ajaxReturn($return);
    }

    /**
     * 领券列表：与手机网页版的接口一样
     */
    public function coupon_list()
    {
        $type = I('type', 1);
        $p = I('p', 1);

        $activityLogic = new ActivityLogic();
        $result = $activityLogic->getCouponList($type, $this->user_id, $p);
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }
    
    /**
     * 领券中心
     */
    public function coupon_center()
    {
        $p = I('get.p', 1);
        $cat_id = I('get.cat_id', 0);
        
        $activityLogic = new ActivityLogic();
        $result = $activityLogic->getCouponCenterList($cat_id, $this->user_id, $p);
        
        $return = array(
            'status' => 1,
            'msg' => '获取成功',
            'result' => $result ,
        );
        $this->ajaxReturn($return); 
    }
    
    /**
     * 优惠券类型列表
     */
    public function coupon_type_list()
    {
        $p = I('get.p', 1);
        
        $activityLogic = new ActivityLogic();
        $result = $activityLogic->getCouponTypes($p, 15);

        $return = array(
            'status' => 1,
            'msg' => '获取成功',
            'result' => $result ,
        );
        $this->ajaxReturn($return); 
    }
    
    /**
     * 领取优惠券
     */
    public function get_coupon()
    {
        $id = I('post.coupon_id/d', 0);
        
        $activityLogic = new ActivityLogic();
        $return = $activityLogic->get_coupon($id, $this->user_id);
        
        $this->ajaxReturn($return);
    }
    
    /**
     * 优惠活动
     * $author lxl
     * $time 2017-1
     */
    public function promote_goods(){
        $now_time = time();
        $where = " start_time <= $now_time and end_time >= $now_time ";
        $count = M('prom_goods')->where($where)->count();  // 查询满足要求的总记录数
        $pagesize = 10;  //每页显示数
        $Page  = new Page($count,$pagesize); //分页类
        $promote = M('prom_goods')->field('id,title,start_time,end_time,prom_img')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();    //查询活动列表
        $this->assign('promote',$promote);
        if(I('is_ajax')){
            return $this->fetch('ajax_promote_goods');
        }
        return $this->fetch();
    }
}