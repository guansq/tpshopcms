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
use app\common\logic\GoodsLogic;
use app\common\logic\StoreLogic;
use think\Db;
use think\Page;
class Index extends Base {

    public function index(){
        return $this->fetch();
    }

     /*
     * 获取首页数据 (安卓多商已不需要)
     */
    public function home(){
        //获取轮播图
        $data = M('ad')->where('pid = 2')->field(array('ad_link','ad_name','ad_code'))->cache(true,TPSHOP_CACHE_TIME)->select();
        //广告地址转换
        foreach($data as $k=>$v){
            if(!strstr($v['ad_link'],'http'))
                $data[$k]['ad_link'] = SITE_URL.$v['ad_link'];
            $data[$k]['ad_code'] = SITE_URL.$v['ad_code'];

        }
        //获取大分类
//        $category_arr = M('goods_category')->where('id in(4,5,7)')->field('id,name')->limit(3)->cache(true,TPSHOP_CACHE_TIME)->select();
        $goodsLogic = new GoodsLogic();
        $promotion_goods = $goodsLogic->getPromotionGoods();
        $high_quality_goods = $goodsLogic->getRecommendGoods();
        $new_goods = $goodsLogic->getNewGoods();
        $hot_goods = $goodsLogic->getHotGood();
        $result = array(
            array('name'=>'促销商品','goods_list'=>$promotion_goods),
            array('name'=>'精品推荐','goods_list'=>$high_quality_goods),
            array('name'=>'新品上市','goods_list'=>$new_goods),
            array('name'=>'热销商品','goods_list'=>$hot_goods),
        );
        $this->ajaxReturn(array('status'=>1,'msg'=>'获取成功','result'=>array('goods'=>$result,'ad'=>$data)));
    }

    /**
     * 获取首页数据
     */
    public function homePage()
    {
        $goodsLogic = new GoodsLogic();
        $promotion_goods = $goodsLogic->getPromotionGoods();
        $high_quality_goods = $goodsLogic->getRecommendGoods(1);
        $flash_sale_goods = $goodsLogic->getFlashSaleGoods(3);
        $new_goods = $goodsLogic->getNewGoods();
        $hot_goods = $goodsLogic->getHotGood();
        $adv =  $goodsLogic->getHomeAdv();
        $this->ajaxReturn(array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>array(
                'promotion_goods'=>$promotion_goods,
                'high_quality_goods'=>$high_quality_goods,
                'flash_sale_goods' => $flash_sale_goods,
                'new_goods'=>$new_goods,
                'hot_goods'=>$hot_goods,
                'ad'=>$adv
            ),
        ));
    }

    /**
     * 猜你喜欢: 根据经纬度, 返回距离由近到远的商品
     */
    public function favourite()
    {
       $p = I('p',1);
        
        $lng =trim(I('lng/s',114.067345));  //经度
        $lat =trim(I('lat/s',22.632611));    //纬度   
  
        $count= Db::query("SELECT COUNT(store_id) as num  FROM `tp_store` WHERE store_state = 1");//正常店铺
        $Page=new Page($count[0]['num'],10);
        $firstRow = ($p-1)*10;
        $goods_list = Db::query("SELECT g.goods_id, goods_name,shop_price,cat_id3, s.store_id , ROUND(SQRT((POW((($lng - longitude)* 111),2))+ (POW((($lat - latitude)* 111),2))),2) AS distance FROM tp_goods AS g INNER JOIN tp_store AS s
                                            ON g.`store_id` = s.store_id  AND store_state=1 AND is_recommend=1 AND g.goods_state=1 AND  g.is_on_sale=1 ORDER BY distance ASC  LIMIT {$firstRow},{$Page->listRows} ");
        
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result' => array(
                'favourite_goods'=>$goods_list,
            ),
        );    
        
       $this->ajaxReturn($json);
    }

    /**
     * 获取服务器配置
     */
    public function getConfig()
    {
        $data = M('plugin')->where("type='login' and code in ('weixin','qq')")->select();
        $arr = array();
        foreach($data as $k=>$v){
            unset( $data[$k]['config']);
        
			if(!$v['config_value']){
				$data[$k]['config_value'] = "";
			}else{
				$data[$k]['config_value'] = unserialize($v['config_value']);
			}
		 
            if($data[$k]['type'] == 'login'){
                $arr['login'][] =  $data[$k];
            }
        } 
        
        $config_name = ['qq', 'qq2', 'qq3', 'store_name', 'point_rate', 'phone', 
            'address','hot_keywords', 'app_test', 'sms_time_out', 'regis_sms_enable', 
            'forget_pwd_sms_enable', 'bind_mobile_sms_enable'];
        $inc_type = ['ios','app'];
        $config = M('config')->where('name', 'IN', $config_name)->whereOr('inc_type' , 'IN' , $inc_type)->select();
        $result = ['config' => $config] + $arr;
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }
 
    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/15
     * 根据百度坐标，获取周边商家
     *  $lng 经度
     *  $lat 纬度
     *  $scope 范围  千米
     *  $fourpoint
     * */
    public function store_street()
    {
        $lng =trim(I('lng/s',114.067345));  //经度
        $lat =trim(I('lat/s',22.632611));    //纬度
        $scope = trim(I('scope/d',1000)); //1000千米
        $sc_id = I('get.sc_id/d', '');
        $p = I('get.p',1);
        $sale_order = I('get.sale_order', 0);
        $storeLogic = new StoreLogic();
        $count= Db::query("SELECT COUNT(store_id) as num  FROM `tp_store` WHERE SQRT((POW((($lng - longitude)* 111),2))+  (POW((($lat - latitude)* 111),2))) <=$scope");
        $Page=new Page($count[0]['num'],10);
        $store=Db::query("SELECT store_id,store_avatar,store_name,round(SQRT((POW((($lng - longitude)* 111),2))+  (POW((($lat - latitude)* 111),2))),2) AS distance FROM `__PREFIX__store`
WHERE SQRT((POW((($lng - longitude)* 111),2))+  (POW((($lat - latitude)* 111),2)))  <=$scope   LIMIT {$Page->firstRow},{$Page->listRows}");
       
        $distance = group_same_key($store,"store_id");
        $store_list = $storeLogic->getStreetList($sc_id,$p,10,$sale_order, $this->user_id,$lng,$lat);//获取店铺列表
       
        //遍历获取店铺的四个商品数据
        foreach ($store_list as $key => $value) {
            $goodsList = $storeLogic->getStoreGoods($value['store_id'], 4);
            $store_list[$key]['cartList'] = $goodsList['goods_list'];
            $store_list[$key]['store_count'] = $goodsList['goods_count'];
            $store_list[$key]['distance'] = $distance[$value['store_id']][0]['distance'];
            $store_list[$key]['distance'] = $distance[$value['store_id']][0]['distance'] == null ? -1 :$distance[$value['store_id']][0]['distance'] ;
            $store_list[$key]['is_collect'] = $value['add_time'] ? 1 : 0;
        }
        $result['store_list'] = $store_list;
        
        if ($p <= 1) {
            $result['store_class'] = M('store_class')->field('sc_id,sc_name')->select();
            array_unshift($result['store_class'], ['sc_id' => 0, 'sc_name' => '全部分类']);
            $result['ad'] = M('ad')->field(['ad_link','ad_name','ad_code'])->where('pid', 2)->cache(true, TPSHOP_CACHE_TIME)->find();
        }

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }
    
    /**
     * 小程序的店铺街
     */
    public function store_street_list()
    {
        $p = I('p',1);
        $sc_id = I('get.sc_id/d',0);
        $province_id = I('province_id');
        $city_id = I('city_id');
        $district_id = I('district_id');
        $order = I('sale_order', 0);
        if (empty($province_id) && empty($city_id) && empty($district_id) && $sc_id == 0) {
            $province_id = cookie('province_id');
            $city_id =  cookie('city_id');
            $district_id =  cookie('district_id');
        }
        //地区ID,目前搜索时只精确到市
        $address=['province_id'=>$province_id, 'city_id'=>$city_id, 'district_id'=>$district_id];
        $storeLogic = new StoreLogic();
        $store_list = $storeLogic->getStreetList($sc_id, $p, 10, $order, $this->user_id ,$address);
        foreach ($store_list as &$store) {
            $goodsList = $storeLogic->getStoreGoods($store['store_id'], 4);
            $store['cartList'] = $goodsList['goods_list'];
            $store['store_count'] = $goodsList['goods_count'];
            $store['is_collect'] = $store['add_time'] ? 1 : 0;
            $store['distance'] = -1;//遗留
        }
        $result['store_list'] = $store_list;
        
        if ($p <= 1) {
            $result['store_class'] = M('store_class')->field('sc_id,sc_name')->select();
            array_unshift($result['store_class'], ['sc_id' => 0, 'sc_name' => '全部分类']);
            $result['ad'] = M('ad')->field(['ad_link','ad_name','ad_code'])->where('pid', 2)->cache(true, TPSHOP_CACHE_TIME)->find();
        }
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 店铺分类
     */
    public function store_class()
    {
        $store_class = M('store_class')->field('sc_id,sc_name')->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $store_class]);
    }

    /**
     * 品牌街
     * @author dyr
     * @time 2016/08/15
     */
    public function brand_street()
    {
        $p = I('get.p', 1);
        
        $brand_list = M('brand')->field('id,name,logo,url')
                ->order(['sort' => 'desc', 'id' => 'asc'])
                ->where('status', 0)
                ->page($p, 30)
                ->select();
        $result['brand_list'] = $brand_list;
        
        if ($p <= 1) {
            $goodsLogic = new GoodsLogic();
            $result['ad'] = M('ad')->field(['ad_link','ad_name','ad_code'])->where('pid', 2)->cache(true, TPSHOP_CACHE_TIME)->find();
            $result['hot_list'] = $goodsLogic->getHotGood();
        }

        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $result]);
    }

    /**
     * 获取区域地址列表，region_id=0是获取所有省份
     */
    public function get_region()
    {
        $parent_id = I('get.parent_id/d', 0);
        $data = M('region')->field('id,name')->where("parent_id", $parent_id)->select();
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $data]);
    }
}