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
 * Author: 当燃
 * Date: 2016-05-28
 */

namespace app\home\controller;
 
use app\common\logic\StoreLogic;
use app\common\logic\StoreDecorationLogic;
use think\Page;

class Store extends Base
{
    public $navigation = array();
    public $store = array();

    public function _initialize()
    {
        parent::_initialize();                
        
        $store_id = I('store_id/d', 1);
        $store_info = M('store')->where(array('store_id' => $store_id))->find();
        if ($store_info) {
            if ($store_info['store_state'] == 0) {
                $this->error('该店铺不存在或者已关闭', U('Index/index'));
            }
            $store_slide = explode(',', $store_info['store_slide']);
            $store_slide_url= explode(',', $store_info['store_slide_url']);
            $store_slide_count = count($store_slide);
            for ($i = 0; $i < $store_slide_count; $i++) {
                $store_info['store_slide_list'][$i]['store_slide_img'] = $store_slide[$i];
                $store_info['store_slide_list'][$i]['store_slide_url'] =$store_slide_url[$i];
            }
            $store_info['store_presales'] = unserialize($store_info['store_presales']);
            $store_info['store_aftersales'] = unserialize($store_info['store_aftersales']);
            $this->navigation = M('store_navigation')->field('sn_content', true)->where(array('sn_store_id' => $store_id, 'sn_is_show' => 1))->select();//店铺导航
            $this->assign('user', session('user'));
            $decoration_id = I('decoration_id/d', 0);
            if ($store_info['store_decoration_switch'] > 0 && $decoration_id == 0) {
                $model_store_decoration = new StoreDecorationLogic();
                $decoration_info = $model_store_decoration->getStoreDecorationInfoDetail($store_info['store_decoration_switch'], $store_id);
                if ($decoration_info) {
                    $this->_output_decoration_info($decoration_info);
                }
                $store_info['store_theme'] = 'default';
            }
            $this->store = $store_info;
            $this->assign('store', $store_info);
            $store_logic = new StoreLogic();
            $storeStatistics = $store_logic->storeComparison($store_info['sc_id']);//获取业内的评论统计
            //店铺分类导航
            $link_cat = M('store_goods_class')->where(array('store_id' => $store_id, 'is_nav_show' => 1))->order('cat_sort desc')->select();
            $this->assign('link_cat', $link_cat);
            $this->assign('storeStatistics', $storeStatistics);
            /*
             if ($link_cat) {
            $cat_id = get_arr_column($link_cat, 'cat_id');
            $cat_id = implode(',', $cat_id);
            $map = array('store_id' => $store_id, 'is_on_sale' => 1);
            $map['_string'] = "store_cat_id1 in ($cat_id) OR store_cat_id2 in($cat_id)";
            $cat_goods = M('goods')->field('goods_content', true)->where($map)->order('goods_id desc')->select();
            }
            */
            $this->assign('action', ACTION_NAME);
        } else {
            $this->error('该店铺不存在或已关闭', U('Index/index'));
        }
    }

    public function index()
    {
        $store_id = $this->store['store_id'];
        //$key = md5($store_id.$this->store['store_theme'].$this->store['store_decoration_switch'].$this->store['store_decoration_only'].$this->session_id);
        //$html = S($key);
        //if (!empty($html)) {
        //    exit($html);
        //}
        
        //店铺内部分类
        $store_goods_class_list = M('store_goods_class')->where(array('store_id' => $store_id, 'is_show' => 1))->select();
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
        $goodsModel =M('goods')->field('goods_content', true);
        $goods_commom_where = array('store_id' => $store_id,'is_on_sale'=>1); //公共条件
        //热门商品排行
        $hot_goods = $goodsModel->where($goods_commom_where)->order('sales_sum desc')->limit(10)->select();
        //收藏商品排行
        $collect_goods = $goodsModel->where($goods_commom_where)->order('collect_sum desc')->limit(10)->select();
        //新品
        $new_goods = $goodsModel->where($goods_commom_where)->where(['is_new' => 1])->order('goods_id desc')->limit(10)->select();
        //推荐商品
        $recomend_goods = $goodsModel->where($goods_commom_where)->where(['is_recommend' => 1])->order('goods_id desc')->limit(10)->select();

        $goods_id_arr = array_merge(get_arr_column($new_goods, 'goods_id'), get_arr_column($recomend_goods, 'goods_id'));
        if ($goods_id_arr)
            $goods_images = M('goods_images')->where("goods_id in (" . implode(',', $goods_id_arr) . ")")->cache(true)->select();
        $this->assign('navigation', $this->navigation);
        $this->assign('hot_goods', $hot_goods);
        $this->assign('collect_goods', $collect_goods);
        $this->assign('new_goods', $new_goods);
        $this->assign('recomend_goods', $recomend_goods);
        $this->assign('goods_images', $goods_images); //相册图片

        $html = $this->fetch();
        //S($key, $html);
        return $html;
    }

    /**
     * 收藏店铺
     */
    function collect_store()
    {
        $user_id = cookie('user_id');
        if(empty($user_id))exit(json_encode(array('status' => 0, 'msg' => '请先登录'))); //未登录不能收藏
        $store_id = I('store_id');
        $type = I('type', 0);
        if ($type == 1) {
            //删除收藏店铺
            M('store_collect')->where(array('user_id' => $user_id, 'store_id' => $store_id))->delete();
            $store_collect = M('store')->where(array('store_id' => $store_id))->getField('store_collect');
            if ($store_collect > 0) {
                M('store')->where(array('store_id' => $store_id))->setDec('store_collect');
            }
            exit(json_encode(array('status' => 1, 'msg' => '成功取消收藏')));
        }
        $count = M('store_collect')->where(array('user_id' => $user_id, 'store_id' => $store_id))->count();
        if ($count > 0) exit(json_encode(array('status' => 0, 'msg' => '您已收藏过该店铺', 'result' => array())));
        $data = array(
            'store_id' => $store_id,
            'user_id' => $user_id,
            'add_time' => time()
        );
        $data['user_name'] = M('users')->where(array('user_id' => $user_id))->getField('nickname');
        $data['store_name'] = M('store')->where(array('store_id' => $store_id))->getField('store_name');
        M('store_collect')->add($data);
        M('store')->where(array('store_id' => $store_id))->setInc('store_collect');
        exit(json_encode(array('status' => 1, 'msg' => '收藏成功')));
    }

    function goods_list()
    {
        $store_id = I('store_id/d', 1);
        $cat_id = I('cat_id/d', 0);
        $key = I('key', 'is_new');
        $sort = I('sort', 'desc');
        $keyword = urldecode(trim(I('keyword', '')));
        $map = array('store_id' => $store_id, 'is_on_sale' => 1);
        $keyword && $map['goods_name'] = array('like', '%' . $keyword . '%');
        $cat_name = "全部商品";
        if ($cat_id > 0) {
            $cat_name = M('store_goods_class')->where(array('cat_id' => $cat_id))->getField('cat_name');
        }
        $filter_goods_id = M('goods')->where($map)->where(function ($query) use ($cat_id) {
            if ($cat_id > 0) {
                $query->where("store_cat_id1", $cat_id)->whereOr('store_cat_id2', $cat_id);
            } else {
                $query->where("1=1");
            }
        })->getField("goods_id", true);//->cache(true)
        $count = count($filter_goods_id);
        $Page = new Page($count, 20);
        if ($count > 0) {
            $goods_list = M('goods')->where("goods_id", "in", implode(',', $filter_goods_id))->order("$key $sort")->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if ($filter_goods_id2) {
                $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
            }
        }

        $sort = ($sort == 'desc') ? 'asc' : 'desc';
        $this->assign('sort', $sort);
        $this->assign('keys', $key);
        $link_arr = array(
            array('key' => 'is_new', 'name' => '新品', 'url' => U('Store/goods_list', array('store_id' => $store_id, 'key' => 'is_new', 'sort' => $sort, 'cat_id' => $cat_id, 'keyword' => $keyword))),
            array('key' => 'shop_price', 'name' => '价格', 'url' => U('Store/goods_list', array('store_id' => $store_id, 'key' => 'shop_price', 'sort' => $sort, 'cat_id' => $cat_id, 'keyword' => $keyword))),
            array('key' => 'sales_sum', 'name' => '销量', 'url' => U('Store/goods_list', array('store_id' => $store_id, 'key' => 'sales_sum', 'sort' => $sort, 'cat_id' => $cat_id, 'keyword' => $keyword))),
            array('key' => 'collect_sum', 'name' => '收藏', 'url' => U('Store/goods_list', array('store_id' => $store_id, 'key' => 'collect_sum', 'sort' => $sort, 'cat_id' => $cat_id, 'keyword' => $keyword))),
            array('key' => 'is_recommend', 'name' => '人气', 'url' => U('Store/goods_list', array('store_id' => $store_id, 'key' => 'is_recommend', 'sort' => $sort, 'cat_id' => $cat_id, 'keyword' => $keyword)))
        );
        $this->assign('link_arr', $link_arr);
        $this->assign('goods_list', $goods_list);
        $this->assign('goods_images', $goods_images);  //相册图片
        $this->assign('cat_name', $cat_name);
        $page_show = $Page->show();// 分页显示输出
        $this->assign('page_show', $page_show);// 赋值分页输出
        $this->assign('navigation', $this->navigation);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

    function store_news()
    {
        $sn_id = I('sn_id/d');
        $news = M('store_navigation')->where(array('sn_store_id' => $this->store['store_id'], 'sn_id' => $sn_id))->find();
        $this->assign('news', $news);
        $this->assign('navigation', $this->navigation);
        return $this->fetch();
    }

    public function dynamic()
    {
        $this->assign('navigation', $this->navigation);
        $get_type = I('type','all');
        switch($get_type){
            case 'all' : '';break;
            case 'prom' : $map['prom_type'] = ['gt',0];break;
            case !empty($get_type) : $map["$get_type"] = 1;break;  //$type是goods表内的字段
        }
        $map['store_id'] = $this->store['store_id'];
        $map['is_on_sale']=1;
        $count = M('goods')->field('goods_content', true)->where($map)->count();
        $Page = new Page($count, 8);
        $goods_list = M('goods')->field('goods_content', true)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $page_show = $Page->show();// 分页显示输出
        $this->assign('page_show', $page_show);// 赋值分页输出
        $this->assign('goods_list', $goods_list);
        $store_collect = M('store_collect')
            ->where(array('store_id' => $this->store['store_id']))->limit(10)
            ->order('add_time desc')->select();
        if($store_collect){
        	$user_id_arr = get_arr_column($store_collect, 'user_id');
        	$user_arr = M('users')->where("user_id", "in" , implode(',', $user_id_arr))->getField('user_id,head_pic');
        	$this->assign('user_arr',$user_arr);
        }
        $this->assign('store_collect',$store_collect);
        return $this->fetch();
    }

    public function decoration_preview()
    {
        $decoration_id = I('decoration_id/d');
        $model_store_decoration = new StoreDecorationLogic();
        $decoration_info = $model_store_decoration->getStoreDecorationInfoDetail($decoration_id, $this->store['store_id']);
        if ($decoration_info) {
            $this->_output_decoration_info($decoration_info);
        } else {
            $this->error('该店铺没有启用店铺装修', U('Index/index'));
        }
        return $this->fetch();
    }

    private function _output_decoration_info($decoration_info)
    {
        $model_store_decoration = new StoreDecorationLogic();
        $decoration_info['decoration_background_style'] = $model_store_decoration->getDecorationBackgroundStyle($decoration_info['decoration_setting']);
        $this->assign('output', $decoration_info);
    }

    public function store_ma()
    {
        require_once 'vendor/phpqrcode/phpqrcode.php';
        error_reporting(E_ERROR);
        \QRcode::png(U('Mobile/Store/index', array('store_id' => $this->store['store_id']), true, true));
    }
}