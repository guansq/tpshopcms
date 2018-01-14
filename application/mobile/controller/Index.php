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
 * $Author: 当燃 2016-01-09
 */ 
namespace app\mobile\controller;
use app\common\logic\JssdkLogic;
use app\common\logic\StoreLogic;
use Think\Db;
class Index extends MobileBase {

    public function test(){
        
        echo " name : ".MODULE_NAME;
        
    }
    public function index(){                
        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;        
            // 微信Jssdk 操作类 用分享朋友圈 JS            
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();              
            print_r($signPackage);
        */
        $hot_goods = M('goods')->where("is_hot=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页热卖商品
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('thems',$thems);
        $this->assign('hot_goods',$hot_goods);
        $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
        //秒杀商品
        $now_time = time();  //当前时间
        if(is_int($now_time/7200)){      //双整点时间，如：10:00, 12:00
            $start_time = $now_time;
        }else{
            $start_time = floor($now_time/7200)*7200; //取得前一个双整点时间
        }
        $end_time = $start_time+7200;   //结束时间
        $flash_sale_list = M('goods')->alias('g')
            ->field('g.goods_id,f.price,s.item_id')
            ->join('__FLASH_SALE__ f','g.goods_id = f.goods_id','LEFT')
            ->join('__SPEC_GOODS_PRICE__ s','s.prom_id = f.id AND g.goods_id = s.goods_id','LEFT')
            ->where('f.status', 1)
            ->where("f.start_time = $start_time and f.end_time = $end_time and f.recommend=1")
            ->limit(3)->select();
        $this->assign('flash_sale_list',$flash_sale_list);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    /**
     * 分类列表显示
     */
    public function categoryList(){
        return $this->fetch();
    }

    /**
     * 模板列表
     */
    public function mobanlist(){
        $arr = glob("D:/wamp/www/svn_tpshop/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_tpshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";            
        }        
    }
    
    /**
     * 商品列表页
     */
    public function goodsList(){
        $id = I('get.id/d',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        return $this->fetch();
    }
    
    public function ajaxGetMore(){
    	$p = I('p/d',1);
    	$favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1  and goods_state = 1 ")->order('sort DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
    	echo $this->fetch();
    }

    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/15
     */
    public function street()
    {
        $store_class = M('store_class')->where('')->select();
        $this->assign('store_class', $store_class);//店铺分类
        return $this->fetch();
    }

    /**
     * ajax 获取店铺街
     */
    public function ajaxStreetList()
    {
        $p = I('p',1);
        $sc_id = I('get.sc_id/d',0);
        $province_id = I('province_id');
        $city_id = I('city_id');
        $district_id = I('district_id');
        $order = I('order', 0);
        $user_id = cookie('user_id') ? cookie('user_id'):0;
        if (empty($province_id) && empty($city_id) && empty($district_id) && $sc_id == 0) {
            $province_id = cookie('province_id');
            $city_id =  cookie('city_id');
            $district_id =  cookie('district_id');
        }
        //地区ID,目前搜索时只精确到市
        $address=['province_id'=>$province_id, 'city_id'=>$city_id, 'district_id'=>$district_id];
        $storeLogic = new StoreLogic();
        $store_list = $storeLogic->getStreetList($sc_id,$p,10, $order, $user_id,$address);
        foreach($store_list as $key=>$value){
            $store_list[$key]['goods_array'] = $storeLogic->getStoreGoods($value['store_id'],3);
        }
        $this->assign('province_id', $province_id);
        $this->assign('city_id', $city_id);
        $this->assign('district_id', $district_id);
        $this->assign('store_list',$store_list);
        echo $this->fetch();
      
    }

    /**
     * 品牌街
     * @author dyr
     * @time 2016/08/15
     */
    public function brand()
    {
        $brand_where['status'] = 0;
        $brand_where['is_hot'] = 1;
        $goods = M('goods')->field('goods_id,shop_price,market_price')->where(['is_on_sale'=> 1,'is_recommend'=>1])->limit(3)->order('sort desc')->select();
        $brand_list = M('brand')->field('id,name,logo,url')->order(array('sort'=>'desc'))->cache(true)->where($brand_where)->select();
        for($i=0;$i<3;$i++)
        {
           $Goods_group[]= array_slice($goods, $i * 3 ,3);//每三个一组，取三组
            if(!empty($Goods_group[$i])){ //去掉空的
                $recommendGoods=$Goods_group;
            }
        }
        $this->assign('brand_list', $brand_list);//品牌列表
        $this->assign('recommendGoods', $recommendGoods);//品牌列表
        return $this->fetch();
    }
    
    //微信Jssdk 操作类 用分享朋友圈 JS
    public function ajaxGetWxConfig(){
    	$askUrl = I('askUrl');//分享URL
    	$weixin_config = M('wx_user')->find(); //获取微信配置
    	$jssdk = new JssdkLogic($weixin_config['appid'], $weixin_config['appsecret']);
    	$signPackage = $jssdk->GetSignPackage(urldecode($askUrl));
    	if($signPackage){
    		$this->ajaxReturn($signPackage,'JSON');
    	}else{
    		return false;
    	}
    }
}