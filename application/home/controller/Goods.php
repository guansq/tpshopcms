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


use app\common\logic\CommentLogic;
use app\common\logic\GoodsPromFactory;
use app\common\logic\SearchWordLogic;
use app\common\logic\StoreLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\ReplyLogic;
use app\common\model\SpecGoodsPrice;
use think\AjaxPage;
use think\Cookie;
use think\Db;
use think\Page;
use think\Verify;


class Goods extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    
    /**
     * 商品详情页
     */
    public function goodsInfo()
    {
        //  form表单提交
        C('TOKEN_ON', true);
        $goodsLogic = new GoodsLogic();
        $goodsPromFactory = new GoodsPromFactory();
        $goods_id = I("get.id/d", 0);
        $Goods = new \app\common\model\Goods();
        $goods = $Goods::get($goods_id);
        if ($goods['is_virtual']==1 && $goods['virtual_indate'] <= time()) {
            //虚拟商品过期，就下架
            $goods->save(['is_on_sale'=>0]);
        }
        if (empty($goods) || ($goods['is_on_sale'] != 1) && I('identity')!='admin') {
            $this->error('该商品已经下架', U('Index/index'));
        }
        if (!empty($goods['prom_id']) && $goodsPromFactory->checkPromType($goods['prom_type'])) {
            $goodsPromLogic = $goodsPromFactory->makeModule($goods, null);//这里会自动更新商品活动状态，所以商品需要重新查询
            $goods = $goodsPromLogic->getGoodsInfo();//上面更新商品信息后需要查询
        }
        if (cookie('user_id')) {
            $goodsLogic->add_visit_log(cookie('user_id'), $goods);
        }
        if ($goods['brand_id']) {
            $brand = M('brand')->where("id", $goods['brand_id'])->find();
            $goods['brand_name'] = $brand['name'];
            $this->assign('brand', $brand);
        }
        $goods_images_list = M('GoodsImages')->where(array('goods_id' => $goods_id))->order("img_sort asc")->select(); // 商品 图册
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id", $goods_id)->select(); // 查询商品属性表
        $filter_spec = $goodsLogic->get_spec($goods_id);
        $point_rate = tpCache('shopping.point_rate');
        $spec_goods_price = M('spec_goods_price')->where(array('goods_id' => $goods_id))->getField("key,item_id,price,store_count"); // 规格 对应 价格 库存表
        M('Goods')->where("goods_id", $goods_id)->save(array('click_count' => $goods['click_count'] + 1)); //统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $store_logic = new StoreLogic();
        $commentStoreStatistics = $store_logic->storeCommentStatistics($goods['store_id']);//获取商家的评论统计
        $goodsTotalComment = $goodsLogic->getGoodsTotalComment($goods_id); //获取商品达人评价
        $store = M('store')->where(array('store_id' => $goods['store_id']))->find();
        $comparisonStoreStatistics = $store_logic->storeComparison($store['sc_id']);//获取业内的评论统计
        $comparisonStatistics = $store_logic->storeMatch($comparisonStoreStatistics, $comparisonStoreStatistics);//获取商家的评论统计
        $store_goods_class_list = M('store_goods_class')->where(array('store_id' => $goods['store_id']))->cache(true)->select();
        if ($store_goods_class_list) {
            $sub_cat = $main_cat = array();
            foreach ($store_goods_class_list as $val) {
                if ($val['parent_id'] == 0) {
                    $main_cat[] = $val;
                } else {
                    $sub_cat[$val['parent_id']][] = $val;
                }
            }
            $this->assign('main_cat', $main_cat);
            $this->assign('sub_cat', $sub_cat);
        }
        $region_id = Cookie::get('district_id');
        if ($region_id) {
            $dispatching = $goodsLogic->getGoodsDispatching($goods['goods_id'], $region_id);
            $this->assign('dispatching', $dispatching);
        }
        $commentLogic = new CommentLogic();
        $goodsCommentList = $commentLogic->getGoodsComment($goods_id, 1);
        $this->assign('commentlist', $goodsCommentList['list']);// 商品评论
        $this->assign('page', $goodsCommentList['page']);// 赋值分页输出
        $this->assign('commentStoreStatistics', $commentStoreStatistics); // 商家评论概览
        $this->assign('commentStoreStatistics', $commentStoreStatistics); // 商家评论概览
        $this->assign('commentStoreStatistics', $commentStoreStatistics); // 商家评论概览
        $this->assign('spec_goods_price', json_encode($spec_goods_price, true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods', navigate_goods($goods_id, 1));// 面包屑导航
        $this->assign('commentStatistics', $commentStatistics);//评论概览
        $this->assign('goods_attribute', $goods_attribute);//属性值
        $this->assign('goods_attr_list', $goods_attr_list);//属性列表
        $this->assign('filter_spec', $filter_spec);//规格参数
        $this->assign('goods_images_list', $goods_images_list);//商品缩略图
        $this->assign('siblings_cate', $goodsLogic->get_siblings_cate($goods['cat_id2']));//相关分类
        $this->assign('look_see', $goodsLogic->get_look_see($goods));//看了又看
        $this->assign('goods', $goods->toArray());
        $this->assign('point_rate', $point_rate);
        $this->assign('goodsTotalComment', $goodsTotalComment);
        $this->assign('comparisonStoreStatistics', $comparisonStoreStatistics); // 行业评论概览
        $this->assign('comparisonStatistics', $comparisonStatistics); // 商家行业百分比
        $this->assign('store', $store);
        return $this->fetch();
    }

    public function activity(){
        $goods_id = input('goods_id/d');//商品id
        $item_id = input('item_id/d');//规格id
        $Goods = new \app\common\model\Goods();
        $goods = $Goods::get($goods_id,'',true);
        $goodsPromFactory = new GoodsPromFactory();
        if ($goodsPromFactory->checkPromType($goods['prom_type'])) {
            //这里会自动更新商品活动状态，所以商品需要重新查询
            if($item_id){
                $specGoodsPrice = SpecGoodsPrice::get($item_id,'',true);
                $goodsPromLogic = $goodsPromFactory->makeModule($goods,$specGoodsPrice);
            }else{
                $goodsPromLogic = $goodsPromFactory->makeModule($goods,null);
            }
            //检查活动是否有效
            if($goodsPromLogic->checkActivityIsAble()){
                $goods = $goodsPromLogic->getActivityGoodsInfo();
                $goods['activity_is_on'] = 1;
                $this->ajaxReturn(['status'=>1,'msg'=>'该商品参与活动','result'=>['goods'=>$goods]]);
            }else{
                $goods['activity_is_on'] = 0;
                $this->ajaxReturn(['status'=>1,'msg'=>'该商品没有参与活动','result'=>['goods'=>$goods]]);
            }
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'该商品没有参与活动','result'=>['goods'=>$goods]]);
    }

    /**
     *  查询配送地址，并执行回调函数
     */
    public function region()
    {
        $fid = I('fid/d');
        $callback = I('callback');

        $parent_region_code = $parent_region = Db::name('region')->field('id,name')->where(array('parent_id' => $fid))->cache(true)->select();
        foreach($parent_region_code as $key=>$val){
            $parent_region_code[$key]['name'] = urlencode($val['name']);
        }
//        Cookie::set('parent_region', $parent_region_code);
        echo $callback . '(' . json_encode($parent_region) . ')';
        exit;
    }

    /**
     * 商品物流配送和运费
     */
    public function dispatching()
    {        
        $goods_id = I('goods_id/d');//143
        $region_id = I('region_id/d');//28242
        $dispatching_data = S("goods_dispatching_{$goods_id}_$region_id");
        if($dispatching_data)
           $this->ajaxReturn($dispatching_data);         
        
        $goods_logic = new GoodsLogic();
        $dispatching_data = $goods_logic->getGoodsDispatching($goods_id, $region_id);
        S("goods_dispatching_{$goods_id}_$region_id", $dispatching_data ,60);
        $this->ajaxReturn($dispatching_data);
    }
        
    /**
     * 商品列表页
     */
    public function goodsList()
    {

        $key = md5($_SERVER['REQUEST_URI'] . $_POST['start_price'] . '_' . $_POST['end_price']);
        $html = S($key);
        if (!empty($html)) {
            exit($html);
        }

        $filter_param = array(); // 帅选数组                        
        $id = I('get.id/d', 0); // 当前分类id
        $brand_id = I('get.brand_id',0);

        $own_shop = I('get.own_shop/d', 0);     //自营商品
        $recommend = I('get.recommend/d', 0);    //推荐商品
        $stock = I('get.stock/d', 0);    //显示有货
        $promotion = I('get.promotion/d', 0);    //促销商品
        
        //$spec = I('get.spec',0); // 规格 
        $attr = I('get.attr', ''); // 属性
        $sort = I('get.sort', 'goods_id'); // 排序
        $sort_asc = I('get.sort_asc', 'asc'); // 排序
        $price = I('get.price', ''); // 价钱
        $start_price = trim(I('post.start_price', '0')); // 输入框价钱
        $end_price = trim(I('post.end_price', '0')); // 输入框价钱
        if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱

        $filter_param['id'] = $id; //加入帅选条件中                       
        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        //$spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
        $attr && ($filter_param['attr'] = $attr); //加入帅选条件中
        $price && ($filter_param['price'] = $price); //加入帅选条件中

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类

        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id", $id)->find();// 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 帅选 品牌 规格 属性 价格
        //$cat_id_arr = getCatGrandson ($id);        
        if ($goodsCate) {
            $filter_goods_id = M('goods')->where(['is_on_sale' => 1, 'goods_state' => 1, 'cat_id' . $goodsCate['level'] => $id])->cache(true)->getField("goods_id", true);
        } else {
            $filter_goods_id = M('goods')->where("is_on_sale=1 and goods_state = 1")->cache(true)->getField("goods_id", true);
        }
        $this->assign('filter_goods_id_str', implode(',', $filter_goods_id) ? implode(',', $filter_goods_id) : 0);
        // 过滤筛选的结果集里面找商品
        if ($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        
        if ($own_shop || $recommend || $stock || $promotion)// 自营商品 , 是否推荐 , 促销商品 , 显示有货
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByCheckbox($own_shop, $recommend, $promotion, $stock);//根据自营商品 , 是否推荐 , 促销商品 , 显示有货 条件帅选出 商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        
        //if($spec)// 规格
        //{
        //    $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
        //    $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个帅选条件的结果 的交集
        //}
        if ($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_3); // 获取多个帅选条件的结果 的交集
        }

        // 如果商品id 过多 分多次查询
        $s = 1000;
        $p = count($filter_goods_id)/$s;
        $p = ceil($p); 
        $filter_price = $filter_brand = $filter_attr = [];
        for($i=0; $i<$p; $i++)
        {   
            $start =  $p * $s;
            $filter_goods_id2 = array_slice($filter_goods_id,$p,$s);            
            
            $filter_price2 = $goodsLogic->get_filter_price($filter_goods_id2, $filter_param, 'goodsList'); // 帅选的价格期间
            $filter_price = array_merge($filter_price,$filter_price2);
            $filter_price = array_unique($filter_price);
            
            $filter_brand2 = $goodsLogic->get_filter_brand($filter_goods_id2, $filter_param, 'goodsList'); // 获取指定分类下的帅选品牌            
            $filter_brand = array_merge($filter_brand,$filter_brand2);
            $filter_brand = array_unique($filter_brand);            
            
            $filter_attr2 = $goodsLogic->get_filter_attr($filter_goods_id2, $filter_param, 'goodsList', 1); // 获取指定分类下的帅选属性
            $filter_attr = array_merge($filter_attr,$filter_attr2);
            $filter_attr = array_unique($filter_attr);             
  
        }    
        
        $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'goodsList'); // 获取显示的帅选菜单
        
        $count = count($filter_goods_id);
        $page = new Page($count, 40);
        if ($count > 0) {
            $goods_list = M('goods')->alias('g')
                ->join('__STORE__ s', 's.store_id = g.store_id')
                ->where("goods_id", "in", implode(',', $filter_goods_id))
                ->order("$sort $sort_asc")->limit($page->firstRow . ',' . $page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if ($filter_goods_id2)
                $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        $navigate_cat = navigate_goods($id); // 面包屑导航
        $this->assign('goods_list', $goods_list);
        $this->assign('navigate_cat', $navigate_cat);
        $this->assign('goods_category', $goods_category);
        $this->assign('goods_images', $goods_images);  // 相册图片
        $this->assign('filter_menu', $filter_menu);  // 帅选菜单
        //$this->assign('filter_spec', $filter_spec);  // 帅选规格
        $this->assign('filter_attr', $filter_attr);  // 帅选属性
        $this->assign('filter_brand', $filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price', $filter_price);// 帅选的价格期间
        $this->assign('goodsCate', $goodsCate);
        $this->assign('cateArr', $cateArr);
        $this->assign('filter_param', $filter_param); // 帅选条件
        $this->assign('cat_id', $id);
        $this->assign('page', $page);// 赋值分页输出
        C('TOKEN_ON', false);
        $html = $this->fetch();
        S($key, $html);
        echo $html;
    }

    /**
     * @author dyr
     * 回复显示页
     */
    public function reply()
    {
        $comment_id = I('get.comment_id/d', 1);
        $page = (I('get.page', 1) <= 0) ? 1 : I('get.page', 1);//页数
        $list_num = 10;//每页条数
        $reply_logic = new ReplyLogic();
        $reply_list = $reply_logic->getReplyPage($comment_id, $page - 1, $list_num);
        $page_sum = ceil($reply_list['count'] / $list_num);
        $comment_info = M('comment')->where(array('comment_id' => $comment_id))->find();
        if (empty($comment_info)) {
            $this->error('找不到该商品');
        }
        $comment_info['img'] = unserialize($comment_info['img']);
        $goods_info = M('goods')->where(array('goods_id' => $comment_info['goods_id']))->find();
        $order_info = M('order')->where(array('order_id' => $comment_info['order_id']))->find();
        $goods_rank = M('comment')->where(array('goods_id' => $comment_info['goods_id'], 'store_id' => $comment_info['store_id']))->avg('goods_rank');
        $order_goods_info = M('order_goods')->where(array('goods_id' => $comment_info['goods_id'], 'order_id' => $comment_info['order_id']))->find();
        $this->assign('goods_rank', number_format($goods_rank, 1));
        $this->assign('goods_info', $goods_info);
        $this->assign('order_info', $order_info);
        $this->assign('order_goods_info', $order_goods_info);
        $this->assign('comment_info', $comment_info);
        $this->assign('page_sum', intval($page_sum));//总页数
        $this->assign('page_current', intval($page));//当前页
        $this->assign('reply_count', $reply_list['count']);//总回复数
        $this->assign('reply_list', $reply_list['list']);//回复列表
        return $this->fetch();
    }

    /**
     * @author dyr
     * 获取回复
     */
    public function ajaxReply()
    {
        $comment_id = I('post.comment_id/d', 1);
        $reply_logic = new ReplyLogic();
        $reply_list = $reply_logic->getReplyListToArray($comment_id, 4);
        exit(json_encode($reply_list));
    }


    /**
     * 商品搜索列表页
     */
    public function search()
    {
        //C('URL_MODEL',0);
        $filter_param = array(); // 帅选数组                        
        $id = I('get.id/d', 0); // 当前分类id
        $brand_id = I('brand_id', 0);

        $own_shop = I('get.own_shop/d', 0);     //自营商品
        $recommend = I('get.recommend/d', 0);    //推荐商品
        $stock = I('get.stock/d', 0);    //显示有货
        $promotion = I('get.promotion/d', 0);    //促销商品

        $sort = I('sort', 'goods_id'); // 排序
        $sort_asc = I('sort_asc', 'asc'); // 排序
        $price = I('price', ''); // 价钱
        $start_price = trim(I('start_price', '0')); // 输入框价钱
        $end_price = trim(I('end_price', '0')); // 输入框价钱
        $store_id = I('store_id/d');
        if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱
        $q = urldecode(trim(I('q', ''))); // 关键字搜索
//        empty($q) && $this->error('请输入搜索词');
        $id && ($filter_param['id'] = $id); //加入帅选条件中
        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $price && ($filter_param['price'] = $price); //加入帅选条件中
        $q && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        $SearchWordLogic = new SearchWordLogic();
        $where = $SearchWordLogic->getSearchWordWhere($q);
        $where['is_on_sale'] = 1;
        $where['goods_state'] = 1;
        if ($store_id) {
            $where['store_id'] = $store_id;
        }
        Db::name('search_word')->where('keywords', $q)->setInc('search_num');
        $goodsHaveSearchWord = Db::name('goods')->where($where)->count();
        if ($goodsHaveSearchWord) {
            $SearchWordIsHave = Db::name('search_word')->where('keywords',$q)->find();
            if($SearchWordIsHave){
                Db::name('search_word')->where('id',$SearchWordIsHave['id'])->update(['goods_num'=>$goodsHaveSearchWord]);
            }else{
                $SearchWordData = [
                    'keywords' => $q,
                    'pinyin_full' => $SearchWordLogic->getPinyinFull($q),
                    'pinyin_simple' => $SearchWordLogic->getPinyinSimple($q),
                    'search_num' => 1,
                    'goods_num' => $goodsHaveSearchWord
                ];
                Db::name('search_word')->insert($SearchWordData);
            }
        }
        if ($id) {
            // 分类菜单显示
            $goodsCate = M('GoodsCategory')->where("id", $id)->find();// 当前分类
//            $cat_id_arr = getCatGrandson ($id);
            $where['cat_id' . $goodsCate['level']] = $id;
        }

        $search_goods = M('goods')->where($where)->getField('goods_id,cat_id3');
        $filter_goods_id = array_keys($search_goods);
        $filter_cat_id = array_unique($search_goods); // 分类需要去重
        if ($filter_cat_id) {
            $cateArr = M('goods_category')->where("id", "in", implode(',', $filter_cat_id))->select();
            $tmp = $filter_param;
            foreach ($cateArr as $k => $v) {
                $tmp['id'] = $v['id'];
                $cateArr[$k]['href'] = U("/Home/Goods/search", $tmp);
            }
        }
        // 过滤帅选的结果集里面找商品        
        if ($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        $this->assign('filter_goods_id_str', implode(',', $filter_goods_id) ? implode(',', $filter_goods_id) :1);
        if ($own_shop || $recommend || $stock || $promotion)// 自营商品 , 是否推荐 , 促销商品 , 显示有货
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByCheckbox($own_shop, $recommend, $promotion, $stock);//根据自营商品 , 是否推荐 , 促销商品 , 显示有货 条件帅选出 商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'search'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id, $filter_param, 'search'); // 帅选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id, $filter_param, 'search'); // 获取指定分类下的帅选品牌

        $count = count($filter_goods_id);
        $page = new Page($count, 20);
        if ($count > 0) {
            $goods_list = M('goods')->where(['is_on_sale' => 1, 'goods_id' => ['in', implode(',', $filter_goods_id)]])->order("$sort $sort_asc")->limit($page->firstRow . ',' . $page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if ($filter_goods_id2)
                $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->select();
        }

        $this->assign('goods_list', $goods_list);
        $this->assign('goods_images', $goods_images);  // 相册图片
        $this->assign('filter_menu', $filter_menu);  // 帅选菜单
        $this->assign('filter_brand', $filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price', $filter_price);// 帅选的价格期间
        $this->assign('cateArr', $cateArr);
        $this->assign('filter_param', $filter_param); // 帅选条件
        $this->assign('cat_id', $id);
        $this->assign('q', I('q'));
        $this->assign('page', $page);// 赋值分页输出
        C('TOKEN_ON', false);
        return $this->fetch();
    }

    /**
     * 商品咨询ajax分页
     */
    public function ajax_consult()
    {
        $goods_id = I("goods_id/d", '0');
        $consult_type = I('consult_type', '0'); // 0全部咨询  1 商品咨询 2 支付咨询 3 配送 4 售后

        $where = ['parent_id' => 0, 'goods_id' => $goods_id];
        if ($consult_type > 0) {
            $where['consult_type'] = $consult_type;
        }

        $count = M('GoodsConsult')->where($where)->count();
        $page = new AjaxPage($count, 5);
        $show = $page->show();
        $list = M('GoodsConsult')->where($where)->order("id desc")->limit($page->firstRow . ',' . $page->listRows)->select();
        $replyList = M('GoodsConsult')->where("parent_id > 0")->order("id desc")->select();

        $this->assign('consultCount', $count);// 商品咨询数量
        $this->assign('consultList', $list);// 商品咨询
        $this->assign('replyList', $replyList); // 管理员回复
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * @author dyr
     * 商品评论ajax分页
     */
    public function ajaxComment()
    {
        $goods_id = I("goods_id/d", '0');
        $commentType = I('commentType', '1'); // 1 全部 2好评 3 中评 4差评 5晒图
        $commentLogic = new CommentLogic();
        $goodsCommentList = $commentLogic->getGoodsComment($goods_id, $commentType);
        $this->assign('commentlist', $goodsCommentList['list']);// 商品评论
//        $this->assign('replyList', $replyList); // 管理员回复
        $this->assign('page', $goodsCommentList['page']);// 赋值分页输出
        return $this->fetch();
    }


    /**
     *  商品咨询
     */
    public function goodsConsult()
    {
        //  form表单提交
        C('TOKEN_ON', true);
        $goods_id = I("goods_id/d", '0'); // 商品id
        $consult_type = I("consult_type", '1'); // 商品咨询类型
        $username = I("username", 'TPshop用户'); // 网友咨询
        $content = I("content"); // 咨询内容

        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), 'consult')) {
            $this->error("验证码错误");
        }

        $goodsConsult = M('goodsConsult');
        if (!$goodsConsult->autoCheckToken($_POST)) {
            $this->error('你已经提交过了!', U('/Home/Goods/goodsInfo', array('id' => $goods_id)));
            exit;
        }

        $data = array(
            'goods_id' => $goods_id,
            'consult_type' => $consult_type,
            'username' => $username,
            'content' => $content,
            'add_time' => time(),
        );
        $goodsConsult->add($data);
        $this->success('咨询已提交!', U('/Home/Goods/goodsInfo', array('id' => $goods_id)));
    }

    /**
     * 用户收藏某一件商品
     */
    public function collect_goods()
    {
        $goods_id = I('goods_id/d');
        $goodsLogic = new GoodsLogic();
        $result = $goodsLogic->collect_goods(cookie('user_id'), $goods_id);
        exit(json_encode($result));
    }

    public function collects(){
        $goods_ids = I('goods_ids/a',[]);
        if(empty($goods_ids)){
            $this->ajaxReturn(['status'=>0,'msg'=>'请至少选择一个商品','result'=>'']);
        }
        $goodsLogic = new GoodsLogic();
        $result = [];
        foreach($goods_ids as $key=>$val){
            $result[] = $goodsLogic->collect_goods(cookie('user_id'), $val);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'已添加至我的收藏','result'=>$result]);
    }

    /**
     * 加入购物车弹出
     */
    public function open_add_cart()
    {
        return $this->fetch();
    }

    public function integralMall()
    {
        $cat_id = I('get.id/d');
        $minValue = I('get.minValue');
        $maxValue = I('get.maxValue');
        $brandType = I('get.brandType');
        $point_rate = tpCache('shopping.point_rate'); //多少积分抵1元
        $exchange = I('get.exchange', 0);
        $goods_where = 'is_virtual=0 and is_on_sale = 1 and exchange_integral > 0 ';
        //积分兑换筛选
        $exchange_integral_where_array = array(array('gt', 0));
        // 分类id
        if (!empty($cat_id)) {
            $store_id_arr = M('store')->where(array('sc_id' => $cat_id))->cache(true, TPSHOP_CACHE_TIME)->getField('store_id', true);
            if (!empty($store_id_arr)) {
                $store_id_str = implode($store_id_arr, ',');
                $goods_where.=" and store_id in ($store_id_str) ";
            } else {
                $goods_where .= " and store_id = -1 ";
            }
        }
        //积分截止范围
        if (!empty($maxValue)) {
            $goods_where .= " and exchange_integral >= $minValue";
        }
        //积分起始范围
        if (!empty($minValue)) {
            $goods_where .= " and exchange_integral >= $minValue";
        }
        //积分+金额
        if ($brandType == 1) {
            $goods_where .= " and exchange_integral < shop_price * $point_rate ";
        }
        //全部积分
        if ($brandType == 2) {
            $goods_where .= " and exchange_integral = shop_price * $point_rate ";
        }
        //我能兑换
        /*
        $user_id = cookie('user_id');
        if ($exchange == 1 && !empty($user_id)) {
            $user_pay_points = intval(M('users')->where(array('user_id' => $user_id))->getField('pay_points'));
            if ($user_pay_points !== false) {
                array_push($exchange_integral_where_array, array('lt', $user_pay_points));
            }
        }         
         $goods_where['exchange_integral'] = $exchange_integral_where_array;
         */
        
        $goods_list_count = Db::name('goods')->cache(true)->where($goods_where)->count();
        $page = new Page($goods_list_count, 10);
        $goods_list = M('goods')
            ->cache(true)
            ->where($goods_where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $store_category = M('store_class')->cache(true)->select();

        $this->assign('goods_list', $goods_list);
        $this->assign('page', $page->show());
        $this->assign('goods_list_count', $goods_list_count);
        $this->assign('store_category', $store_category);//商品1级分类
        $this->assign('point_rate', $point_rate);//兑换率
        $this->assign('pages', $page);
        return $this->fetch();
    }

    /**
     * 全部商品分类
     * @time17-4-18
     */
    public function all_category(){
        return $this->fetch();
    }

    /**
     * 全部品牌列表
     * @time17-4-18
     */
    public function all_brand(){
        return $this->fetch();
    }
}