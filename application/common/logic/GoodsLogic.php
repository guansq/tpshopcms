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
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace app\common\logic;

use think\Model;
use app\common\logic\CartLogic;
use think\Db;
use think\Page;
use app\common\model\SpecGoodsPrice;

/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package common\Logic
 */
class GoodsLogic extends Model
{

    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @return array|mixed 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选品牌
     */
    public function get_filter_brand($goods_id_arr, $filter_param, $action)
    {
        if (!empty($filter_param['brand_id'])){
            return array();
        }
        $brand_ids = Db::name('goods')->where('brand_id','>',0)->where('is_on_sale = 1')->where('goods_id','IN',$goods_id_arr)->getField('brand_id',true);
        $list_brand = DB::name('brand')->where('id','IN',$brand_ids)->limit('30')->select();
        foreach ($list_brand as $k => $v) {
            // 帅选参数
            $filter_param['brand_id'] = $v['id'];
            $list_brand[$k]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
        }
        return $list_brand;
    }
    

   /**
    * 获取 商品列表页帅选规格
    * @param type $id  
    * return array(status)  这里状态一般都为1 result 不是返回数据 就是空
    * $mode 0  返回数组形式  1 直接返回result
    */ 
   public function get_filter_spec($goods_id_arr,$filter_param,$action,$mode = 0)
   {       
        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';       
        $spec_key = Db::query("select group_concat(`key` separator  '_') as `key` from __PREFIX__spec_goods_price where goods_id in($goods_id_str)");  //where("goods_id in($goods_id_str)")->select();
        $spec_key = explode('_', $spec_key[0]['key']);
        $spec_key = array_unique($spec_key);
        $spec_key = array_filter($spec_key);
        
        if(empty($spec_key))
        {
            if($mode == 1) return  array();
            return array('status'=>1,'msg'=>'','result'=>array());       
        }
        $spec = M('spec')->getField('id,name');
        $spec_item = M('spec_item')->getField('id,spec_id,item');                
                               
        $list_spec = array();
        $old_spec = $filter_param['spec'];
        foreach($spec_key as $k => $v)
        {                          
           if(strpos($old_spec, $spec_item[$v]['spec_id'].'_') === 0 || strpos($old_spec, '@'.$spec_item[$v]['spec_id'].'_') || $spec_item[$v]['spec_id'] == '')
               continue;
           $list_spec[$spec_item[$v]['spec_id']]['spec_id'] = $spec_item[$v]['spec_id'];
           $list_spec[$spec_item[$v]['spec_id']]['name'] = $spec[$spec_item[$v]['spec_id']];
           //$list_spec[$spec_item[$v]['spec_id']]['item'][$v] = $spec_item[$v]['item'];
           
           // 帅选参数
           if(!empty($old_spec))
                $filter_param['spec'] = $old_spec.'@'.$spec_item[$v]['spec_id'].'_'.$v;
           else
                $filter_param['spec'] = $spec_item[$v]['spec_id'].'_'.$v;
           $list_spec[$spec_item[$v]['spec_id']]['item'][] = array('key'=>$spec_item[$v]['spec_id'],'val'=>$v,'item'=>$spec_item[$v]['item'],'href'=>urldecode(U("Goods/$action",$filter_param,''))); 
        }      
        
        if($mode == 1) return $list_spec;
        return array('status'=>1,'msg'=>'','result'=>$list_spec);
   }
   
   /**
    * 获取商品列表页帅选属性
    * @param type $id
    * $mode 0  返回数组形式  1 直接返回result
    */ 
   public function get_filter_attr($goods_id_arr = array(),$filter_param,$action, $mode = 0)
   {
        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $goods_attr = M('goods_attr')->where("goods_id in($goods_id_str) and attr_value != ''")->select();
        // $goods_attr = M('goods_attr')->where("attr_value != ''")->select();
        $goods_attribute = M('goods_attribute')->where("attr_index = 1")->getField('attr_id,attr_name,attr_index');
        if(empty($goods_attr))
        {
            if($mode == 1) return  array();
            return array('status'=>1,'msg'=>'','result'=>array());    
        }        
        $list_attr = $attr_value_arr = array();
        $old_attr = $filter_param['attr'];
        foreach($goods_attr as $k => $v)
        {
            // 存在的帅选不再显示
           if(strpos($old_attr, $v['attr_id'].'_') === 0 || strpos($old_attr, '@'. $v['attr_id'].'_'))           
               continue;            
            if($goods_attribute[$v['attr_id']]['attr_index'] == 0)
                continue;
            $v['attr_value'] = trim($v['attr_value']);
            // 如果同一个属性id 的属性值存储过了 就不再存贮
             
            if(!empty($attr_value_arr[$v['attr_id']]) && in_array($v['attr_id'].'_'.$v['attr_value'],$attr_value_arr[$v['attr_id']]))
                continue;
             $attr_value_arr[$v['attr_id']][] = $v['attr_id'].'_'.$v['attr_value'];
            
             $list_attr[$v['attr_id']]['attr_id'] = $v['attr_id'];
             $list_attr[$v['attr_id']]['attr_name'] = $goods_attribute[$v['attr_id']]['attr_name'];                                       
                          
           // 帅选参数
           if(!empty($old_attr))
                $filter_param['attr'] = $old_attr.'@'.$v['attr_id'].'_'.$v['attr_value'];
           else                                         
                $filter_param['attr'] = $v['attr_id'].'_'.$v['attr_value'];           
             
             $list_attr[$v['attr_id']]['attr_value'][] = array('key'=>$v['attr_id'],'val'=>$v['attr_value'],'attr_value'=>$v['attr_value'],'href'=>urldecode(U("Goods/$action",$filter_param,'')));
             //unset($filter_param['attr_id_'.$v['attr_id']]);
        }                
        if($mode == 1) return  $list_attr;
        return array('status'=>1,'msg'=>'','result'=>$list_attr);    
   }

    /**
     * 获取某个商品的评论统计
     * 全部评论数  好评数 中评数  差评数
     * @param $goods_id
     * @return array
     */
   public function commentStatistics($goods_id)
   {
       $commonWhere = ['is_show' => 1,'goods_id' => $goods_id,'user_id'=>['gt',0],'deleted'=>0]; //公共条件
       $c1 = M('Comment')->where($commonWhere)->where(" ceil(goods_rank) in(4,5)")->count(); // 好评
       $c2 = M('Comment')->where($commonWhere)->where(" ceil(goods_rank) in(3)")->count(); // 中评
       $c3 = M('Comment')->where($commonWhere)->where(" ceil(goods_rank) in(0,1,2)")->count(); // 差评
       $c4 = M('Comment')->where($commonWhere)->where(" img !='' and img NOT LIKE 'N;%'")->count(); // 晒图
 
       $c0 = $c1 + $c2 + $c3; // 所有评论
       if ($c0 > 0) {
           $rate1 = ceil($c1 / ($c1 + $c2 + $c3) * 100); // 好评率
           $rate2 = ceil($c2 / ($c1 + $c2 + $c3) * 100); // 中评率
           $rate3 = ceil($c3 / ($c1 + $c2 + $c3) * 100); // 差评率
       } else {
           $rate1 = 100; // 好评率
           $rate2 = 0; // 中评率
           $rate3 = 0; // 差评率
       }
       return array('c0'=>$c0, 'c1' =>$c1,'c2' =>$c2,'c3' =>$c3,'c4' =>$c4,'rate1'=>$rate1,'rate2'=>$rate2,'rate3'=>$rate3);
   }
   
   /**
    * 商品收藏
    * @param type $user_id 用户id
    * @param type $goods_id 商品id
    * @return type
    */
   public function collect_goods($user_id,$goods_id)
   {
       if(!is_numeric($user_id) || $user_id <= 0){
           return array('status'=>-1,'msg'=>'必须登录后才能收藏','result'=>array());
       }
       $count = Db::name('goods_collect')->where("user_id", $user_id)->where("goods_id", $goods_id)->count();
       if($count > 0){
           return array('status'=>-3,'msg'=>'商品已收藏','result'=>array());
       }
       Db::name('goods')->where('goods_id', $goods_id)->setInc('collect_sum');
       Db::name('goods_collect')->add(array('goods_id'=>$goods_id,'user_id'=>$user_id, 'add_time'=>time()));
       return array('status'=>1,'msg'=>'收藏成功!请到个人中心查看','result'=>array()); 
   }

   /**
    * 获取商品规格
    */
   public function get_spec($goods_id){
	   	//商品规格 价钱 库存表 找出 所有 规格项id
	   	$keys = M('SpecGoodsPrice')->where("goods_id",$goods_id)->getField("GROUP_CONCAT(`key` ORDER BY store_count desc SEPARATOR '_') ");
	   	$filter_spec = array();
	   	if($keys)
	   	{
	   		$specImage =  M('SpecImage')->where("goods_id",$goods_id)->where("src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
	   		$keys = str_replace('_',',',$keys);
	   		$sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
	   		$filter_spec2 = Db::query($sql);
	   		foreach($filter_spec2 as $key => $val)
	   		{
	   			$filter_spec[$val['name']][] = array(
	   					'item_id'=> $val['id'],
	   					'item'=> $val['item'],
	   					'src'=>$specImage[$val['id']],
	   			);
	   		}
		}
		return $filter_spec;
   }
   

   /**
    * 获取相关分类
    */
   public function get_siblings_cate($cat_id){
        if(empty($cat_id))return array();
   		$siblings_cate = M('goods_category')->where("parent_id",$cat_id)->select();
   		return empty($siblings_cate) ? array() : $siblings_cate;
   }
   
   /**
    * 看了又看
    */
   public function get_look_see($goods){
	   $goods['cat_id1'] = intval($goods['cat_id1']);
   	  return M('goods')->where("cat_id1 !=".$goods['cat_id1']." and is_on_sale = 1")->limit(12)->select();
   }
   
   /**
    * 用户浏览记录
    */
   public function add_visit_log($user_id,$goods){
   	  $record = M('goods_visit')->where(array('user_id'=>$user_id,'goods_id'=>$goods['goods_id']))->find();
   	  if($record){
   	  	M('goods_visit')->where(array('user_id'=>$user_id,'goods_id'=>$goods['goods_id']))->save(array('visittime'=>time()));
   	  }else{
   	  	$visit = array('user_id'=>$user_id,'goods_id'=>$goods['goods_id'],'visittime'=>time(),
   	  			'cat_id1'=>$goods['cat_id1'],'cat_id2'=>$goods['cat_id2'],'cat_id3'=>$goods['cat_id3']
   	  	);
   	  	M('goods_visit')->add($visit);
   	  }
   }
 /**
 * 帅选的价格期间 
 * @param type $goods_id_str 帅选的分类id
 * @param type $c   分几段 默认分5 段 
 */
function get_filter_price($goods_id_arr,$filter_param,$action,$c=5)
{ 
    
    if(!empty($filter_param['price']))
        return array();

    $goods_id_str = implode(',', $goods_id_arr);
    $goods_id_str = $goods_id_str ? $goods_id_str : '0';       
    $priceList = M('goods')->where("is_on_sale = 1 and goods_id in ($goods_id_str)")->getField('shop_price',true);  //where("goods_id in($goods_id_str)")->select();
    
    rsort($priceList);
    $max_price = (int)$priceList[0];
            
    $psize = ceil($max_price / $c); // 每一段累积的价钱
    $parr = array();
    for($i = 0; $i < $c; $i++)
    {
        $start = $i * $psize;
        $end = $start + $psize;
        
        // 如果没有这个价格范围的商品则不列出来
        $in = false;
        foreach($priceList as $k => $v)
        {
            if($v > $start && $v < $end)
                $in = true;        
        }
        if($in == false)
            continue;
        
        $filter_param['price'] = "{$start}-{$end}";
        if($i == 0)                
            $parr[] = array('value'=>"{$end}元以下",'href'=>urldecode(U("Goods/$action",$filter_param,'')));
        elseif($i == ($c-1) && ($max_price > $end))  
            $parr[] = array('value'=>"{$end}元以上",'href'=>urldecode(U("Goods/$action",$filter_param,'')));
        else    
            $parr[] = array('value'=>"{$start}-{$end}元",'href'=>urldecode(U("Goods/$action",$filter_param,'')));
    }    
    return $parr;
}
/**
 * 帅选条件菜单 
 */
function get_filter_menu($filter_param,$action)
{
    $menu_list = array();
    // 品牌
    if(!empty($filter_param['brand_id']))
    {
        $brand_list = M('brand')->getField('id,name');
        $brand_id = explode('_', $filter_param['brand_id']);
        $brand['text'] = "品牌:";
        foreach ($brand_id as $k => $v)
        {
            $brand['text'] .= $brand_list[$v].',';
        }
        $brand['text'] = substr($brand['text'], 0, -1);
        $tmp = $filter_param;                 
        unset($tmp['brand_id']); // 当前的参数不再带入
        $brand['href'] = urldecode(U("Goods/$action",$tmp,''));
        $menu_list[] = $brand;
    }
    // 规格
    if(!empty($filter_param['spec']))
    {
       $spec = M('spec')->getField('id,name');
       $spec_item = M('spec_item')->getField('id,item');
       $spec_group = explode('@',$filter_param['spec']);       
       foreach ($spec_group as $k => $v)
       {
            $spec_group2 = explode('_',$v);            
            $spec_menu['text'] = $spec[$spec_group2[0]].':';
            array_shift($spec_group2); // 弹出第一个规格名称
            foreach($spec_group2 as $k2 => $v2)
            {
                $spec_menu['text'] .= $spec_item[$v2].',';
            }            
            $spec_menu['text'] = substr($spec_menu['text'], 0, -1);
                        
            $tmp = $spec_group;
            $tmp2 = $filter_param;
            unset($tmp[$k]);            
            $tmp2['spec'] = implode('@', $tmp); // 当前的参数不再带入
            $spec_menu['href'] = urldecode(U("Goods/$action",$tmp2,''));
            $menu_list[] = $spec_menu;
       }
    }
    // 属性
    if(!empty($filter_param['attr']))
    {
       $goods_attribute = M('goods_attribute')->getField('attr_id,attr_name');       
       $attr_group = explode('@',$filter_param['attr']);       
       foreach ($attr_group as $k => $v)
       {
            $attr_group2 = explode('_',$v);            
            $attr_menu['text'] = $goods_attribute[$attr_group2[0]].':';
            array_shift($attr_group2); // 弹出第一个规格名称
            foreach($attr_group2 as $k2 => $v2)
            {
                $attr_menu['text'] .= $v2.',';
            }            
            $attr_menu['text'] = substr($attr_menu['text'], 0, -1);
                   
            $tmp = $attr_group;
            $tmp2 = $filter_param;
            unset($tmp[$k]);            
            $tmp2['attr'] = implode('@', $tmp); // 当前的参数不再带入
            $attr_menu['href'] = urldecode(U("Goods/$action",$tmp2,''));             
            $menu_list[] = $attr_menu;
       }       
    }     
    // 价格
    if(!empty($filter_param['price']))
    {
            $price_menu['text'] = "价格:".$filter_param['price'];
            unset($filter_param['price']);
            $price_menu['href'] = urldecode(U("Goods/$action",$filter_param,''));             
            $menu_list[] = $price_menu;      
    }         
    
    return $menu_list;
}
/**
 * 传入当前分类 如果当前是 2级 找一级
 * 如果当前是 3级 找2 级 和 一级
 * @param type $goodsCate
 */
function get_goods_cate(&$goodsCate)
{    
    if(empty($goodsCate)) return array();
    $cateAll = get_goods_category_tree();
    if($goodsCate['level']==1)
    {
    	$cateArr = $cateAll[$goodsCate['id']]['tmenu'];
    	$goodsCate['parent_name'] = $goodsCate['name'];
    	$goodsCate['select_id'] = 0;
    }elseif($goodsCate['level'] == 2)
    {
            $cateArr = $cateAll[$goodsCate['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$goodsCate['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $goodsCate['id'];//默认展开分类
            $goodsCate['select_id'] = 0;
    }else{
            $parent = M('GoodsCategory')->where("id =".$goodsCate['parent_id'])->order('`sort_order` desc')->find();//父类   
            $cateArr = $cateAll[$parent['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$parent['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $parent['id'];
            $goodsCate['select_id'] = $goodsCate['id'];//默认选中分类
    }	
    return $cateArr;
}


/**
 *  * 根据自营商品 , 是否推荐 , 促销商品 , 显示有货 条件帅选出 商品id
 * @param type $brand_id 帅选品牌id
 * @param type $price 帅选价格
 */
function getGoodsIdByCheckbox($own_shop,$recommend,$promotion,$stock)
{
    if(empty($own_shop) && empty($recommend) && empty($promotion) && empty($stock))
        return array();

    $where['is_on_sale'] = 1;
    if($own_shop){// 自营商品
   
        $where['is_own_shop']= ['>',0]; 
    }
    if($recommend){ // 是否推荐
        $where['is_recommend']= 1 ;
    }
    if($promotion){ // 促销商品
        $where['prom_id']= ['>',0]; 
    }
    if($stock){ // 显示库存
        $where['store_count']= ['>',0];
    }
    
    $arr = M('goods')->where($where)->getField('goods_id',true);
    return $arr ? $arr : array();
}


/**
 *  * 根据品牌或者价格条件帅选出 商品id
 * @param type $brand_id 帅选品牌id 
 * @param type $price 帅选价格
 */
function getGoodsIdByBrandPrice($brand_id,$price)
{
    if (empty($brand_id) && empty($price))
        return array();
    $brand_select_goods=$price_select_goods=array();
    if ($brand_id) // 品牌查询
    {
        $brand_id_arr = explode('_', $brand_id);
        $brand_select_goods = Db::name('goods')->where('is_on_sale = 1')->whereIn('brand_id',$brand_id_arr,'and')->getField('goods_id', 7200);
    }
    if ($price)// 价格查询
    {
        $price = explode('-', $price);
        $price[0] = intval($price[0]);
        $price[1] = intval($price[1]);
        $price_where=" shop_price >= $price[0] and shop_price <= $price[1] and is_on_sale = 1";
        $price_select_goods = M('goods')->where($price_where)->getField('goods_id', true);
    }
    if($brand_select_goods && $price_select_goods)
        $arr = array_intersect($brand_select_goods,$price_select_goods);
    else
        $arr = array_merge($brand_select_goods,$price_select_goods);
    return $arr ? $arr : array();
}
/**
 * 根据规格 查找 商品id 
 * @param type $spec 规格
 */
function getGoodsIdBySpec($spec)
{
    if(empty($spec)) 
         return array();
    
    $spec_group = explode('@',$spec);       
    $where = " where 1=1 ";
    foreach ($spec_group as $k => $v)
    {
         $spec_group2 = explode('_',$v);
         array_shift($spec_group2);
         $like = array();
         foreach ($spec_group2 as $k2 => $v2)
         {
 	     $v2 = addslashes($v2);
             $like[] = " key2  like '%\_$v2\_%' ";                     
         }   
         $where .=  " and (".  implode('or', $like).") ";                  
    }    
        //    $arr = M('spec_goods_price')->where($where)->getField('goods_id',true);
         $sql = "select * from (
                  select *,concat('_',`key`,'_') as key2 from __PREFIX__spec_goods_price as a
              ) b  $where";
        //$Model  = new \Think\Model();  
        $result = \think\Db::query($sql);              
        $arr = get_arr_column($result, 'goods_id');  // 只获取商品id 那一列        
        return ($arr ? $arr : array_unique($arr));            
}

/**
 * 根据属性 查找 商品id 
 * @param type $attr 属性
 * attr=
 * 59_直板_翻盖
 * 80_BT4.0_BT4.1
 */
function getGoodsIdByAttr($attr)
{
    if(empty($attr)) 
         return array();
    
    $attr_group = explode('@',$attr);       
    $attr_id = $attr_value = array();
    foreach ($attr_group as $k => $v)
    {
         $attr_group2 = explode('_',$v);
         $attr_id[] = array_shift($attr_group2);         
         $attr_value =array_merge($attr_value,$attr_group2);
    }
    $c = count($attr_id) - 1;
    if ($c > 0) {
        $arr = Db::name('goods_attr')
            ->where(['attr_id'=>['in',$attr_id],'attr_value'=>['in',$attr_value]])
            ->group('goods_id')
            ->having("count(goods_id) > $c")
            ->getField("goods_id", true);
    }else{
        $arr = M('goods_attr')
            ->where(['attr_id'=>['in',$attr_id],'attr_value'=>['in',$attr_value]])
            ->getField("goods_id", true); // 如果只有一个条件不再进行分组查询
    }
    return ($arr ? $arr : array_unique($arr));
}


    /**
     * @author dyr
     * @time 2016/10/10
     * 获取达人评价
     * @param $goods_id
     * @return array
     */
    public function getGoodsTotalComment($goods_id)
    {
        $comments = M('comment')->field('count(comment_id) AS c,impression')->where(array('deleted'=>0,'goods_id'=>$goods_id))->group('impression')->select();
        $res = array();
        foreach($comments as $key){
            if($key['impression']){
                $impression_array = explode(',',$key['impression']);
                foreach($impression_array as $k){
                    if(array_key_exists($k,$res)){
                        $res[$k] = $res[$k] + $key['c'];
                    }else{
                        $res[$k] = $key['c'];
                    }
                }
            }
        }
        return $res;
    }
    /**
     * 寻找Region_id的父级id
     * @param $cid
     * @return array
     */
    function getParentRegionList($cid){
        //$pids = '';
        $pids = array();
        $parent_id =  M('region')->cache(true)->where(array('id'=>$cid))->getField('parent_id');
        if($parent_id != 0){
            //$pids .= $parent_id;
            array_push($pids,$parent_id);
            $npids = $this->getParentRegionList($parent_id);
            if(!empty($npids)){
                //$pids .= ','.$npids;
                $pids = array_merge($pids,$npids);
            }

        }
        return $pids;
    }

    /**
     * 商品物流配送和运费
     * @param $goods_id
     * @param $region_id
     * @param $goods_num|同种商品数量
     * @return array
     */
    function getGoodsDispatching($goods_id,$region_id)
    {
        $return_data = array('status'=>1,'msg'=>'');
        $goods = M('goods')->cache(true)->where(array('goods_id'=>$goods_id))->find();
        if($goods['is_free_shipping']){
            $return_data['msg'] = '可配送';
            $return_data['result'] = array(array('shipping_name'=>'包邮','freight'=>0));
            return $return_data;
        }
        $goodsLogic = new GoodsLogic();
        //商品没有配置物流，使用物流配置里的默认物流
        if(empty($goods['shipping_area_ids'])){
            //先去店铺物流配送地区查找
            $shipping_area_list = Db::name('shipping_area')->cache(true,10)->where(['store_id'=>$goods['store_id'],'is_close'=>1])->group('shipping_code')->select();
            if(!empty($shipping_area_list)){
                foreach($shipping_area_list as $k=>$v){
                    $shipping_area_list[$k]['freight'] = $goodsLogic->getFreight($shipping_area_list[$k]['shipping_code'], 0, 0, 0, $goods['weight'], $goods['store_id']);
                    $shipping_area_list[$k]['shipping_name'] = M('plugin')->cache(true,10)->where(array('type'=>'shipping','code'=>$shipping_area_list[$k]['shipping_code']))->getField('name');
                }
                $return_data['msg'] = '可配送';
                $return_data['result'] = $shipping_area_list;
                return $return_data;
            }else{
                //店铺物流配送地区没有，就去系统默认配置查找
                $goods_shipping = array();
                $plugin_goods_shipping = M('plugin')->cache(true,10)->where(array('type'=>'shipping'))->select();
                foreach($plugin_goods_shipping as $k=>$v){
                    $goods_shipping[$k]['freight'] = $goodsLogic->getFreight($plugin_goods_shipping[$k]['code'],0,0,0, $goods['weight'], $goods['store_id']);//默认全国
                    $goods_shipping[$k]['shipping_name'] = $plugin_goods_shipping[$k]['name'];
                }
                $return_data['msg'] = '可配送';
                $return_data['result'] = $goods_shipping;
                return $return_data;
            }
        }
        //查找地区$region_id的所有父类，与地区地址表进行匹配
        $goods_shipping_area_id_array = explode(',',$goods['shipping_area_ids']);
        $parent_region_id = $this->getParentRegionList($region_id);
        array_push($parent_region_id,(string)$region_id);//把region_id和它全部父级存起来
        $find_shipping_area_id = Db::name('area_region')->cache(true)->where(['region_id' => ['in', $parent_region_id], 'store_id' => $goods['store_id']])->group('shipping_area_id')->getField('shipping_area_id',true);
        $shipping_area_id_array =array();
        foreach($find_shipping_area_id as $key=>$val){
            if(in_array($find_shipping_area_id[$key],$goods_shipping_area_id_array)){
                array_push($shipping_area_id_array,$find_shipping_area_id[$key]);
            }
        }
        //没有匹配到，就使用商品配置的物流id去物流地址表去查找
        if(count($shipping_area_id_array) == 0){
            $goods_shipping = Db::name('shipping_area')->cache(true,10)->where(['shipping_area_id' => ['in', $goods_shipping_area_id_array], 'is_default' => 1, 'is_close' => 1])->where('store_id', $goods['store_id'])->select();
            //查询到就返回物流信息和运费，没有返回无货
            if(!empty($goods_shipping)){
                foreach($goods_shipping as $k=>$v){
                    $goods_shipping[$k]['freight'] = $goodsLogic->getFreight($goods_shipping[$k]['shipping_code'], 0, 0, 0, $goods['weight'], $goods['store_id']);
                    $goods_shipping[$k]['shipping_name'] = M('plugin')->cache(true)->where(array('type'=>'shipping','code'=>$goods_shipping[$k]['shipping_code']))->getField('name');
                }
                $return_data['msg'] = '可配送';
                $return_data['result'] = $goods_shipping;
                return $return_data;
            }else{
                $return_data['status'] = -1;
                $return_data['msg'] = '该地区不支持配送';
                return $return_data;
            }
        }
        //匹配到就返回物流信息和运费
        $goods_shipping = Db::name('area_region')
            ->cache(true,10)
            ->alias('ar')
            ->join('__SHIPPING_AREA__ sa','sa.shipping_area_id = ar.shipping_area_id', 'INNER')
            ->where(array('ar.shipping_area_id'=>array('in',$shipping_area_id_array)))
            ->group('sa.shipping_code')
            ->select();
        //没匹配到就返回无货
        if(empty($goods_shipping)){
            $return_data['status'] = -1;
            $return_data['msg'] = '该地区不支持配送';
            return $return_data;
        }
        foreach($goods_shipping as $k=>$v){
            $goods_shipping[$k]['freight'] = $goodsLogic->getFreight($goods_shipping[$k]['shipping_code'],0,0,$goods_shipping[$k]['region_id'],$goods['weight'],$goods['store_id']);
            $goods_shipping[$k]['shipping_name'] = M('plugin')->cache(true)->where(array('type'=>'shipping','code'=>$goods_shipping[$k]['shipping_code']))->getField('name');
        }
        $return_data = array(
            'status'=>1,
            'msg'=>'可配送',
            'result'=>$goods_shipping
        );
        return $return_data;
    }
    /**
     *网站自营,入驻商家,货到付款,仅看有货,促销商品
     * @return $sel 筛选条件
     * @return $cat_id 分类ID
     * @return $arrid 符合条件的ID
     */
    function getFilterSelected($sel ,$cat_id = 1){
        $goods_where = ['cat_id1|cat_id2|cat_id3'=>['in',implode(',', $cat_id)]];
        //促销商品
        if($sel == 'prom_type'){
            $goods_where['prom_type'] = 3;
        }
        //看有货
        if($sel == 'store_count'){
            $goods_where['store_count'] = ['gt',0];
        }
        //看包邮
        if($sel == 'free_post'){
            $goods_where['is_free_shipping'] = 1;
        }
        //网站自营
        if($sel == 'own_yes'){
            $store_id = Db::name('store')->where(['store_state'=>1,'is_own_shop'=>1])->getField('store_id',true);
            $goods_where['store_id'] = ['in',implode(',', $store_id)];
        }
        //入驻商家
        if($sel == 'own_no'){
            $store_id = Db::name('store')->where(['store_state'=>1,'is_own_shop'=>0])->getField('store_id',true);
            $goods_where['store_id'] = ['in',implode(',', $store_id)];
        }
        $arrid = Db::name('goods')->where($goods_where)->getField('goods_id', true);
        return $arrid;
    }
    
    /**
     * 找相似
     */
    public function getSimilar($goods_id, $p, $count)
    {
        $goods = M('goods')->field('cat_id3')->where('goods_id', $goods_id)->find();
        if (empty($goods)) {
            return [];
        }

        $where = ['goods_id' => ['<>', $goods_id], 'cat_id3' => $goods['cat_id3']];
    	$goods_list = M('goods')->field("goods_id,goods_name,shop_price")
                ->where($where)
                ->page($p, $count)
                ->cache(true, 3600)
                ->select();

    	return $goods_list;
    }
    
    /**
     * 积分商城
     */
    public function integralMall($rank, $user_id = 0, $p = 1)
    {
        $ranktype = '';
        if ($rank == 'num') {
            $ranktype = 'sales_sum';//以兑换量（购买量）排序
        } elseif ($rank == 'integral') {
            $ranktype = 'exchange_integral';//以需要积分排序
        }
        
        $point_rate = tpCache('shopping.point_rate');
        $goods_where['is_on_sale'] = 1;//是否上架
        $goods_where['exchange_integral'] = ['gt',0];//是否上架
        $goods_list_count = M('goods')->where($goods_where)->count();   //总数
        $goods_list = Db::name('goods')
                ->field('goods_id,goods_name,shop_price,market_price,exchange_integral')
                ->where($goods_where)
                ->order($ranktype ,'desc')
                ->cache(true, 3600)
                ->page($p, 10)
                ->select();
        $result = [
            'goods_list' => $goods_list,
            'goods_list_count' => $goods_list_count,
            'point_rate' => $point_rate,
        ];
        
        return $result;
    }
    /**
     * 获取促销商品数据
     * @return mixed
     */
    public function getPromotionGoods()
    {
        $goods_where = array('g.goods_state' => 1, 'g.is_on_sale' => 1 );
        $goods_where['g.goods_name'] = array("exp", " NOT REGEXP '华为|荣耀|小米|合约机|三星|魅族' ");//临时屏蔽,苹果APP审核过了之后注释
        $promotion_goods = Db::name('goods')->alias('g')
            ->field('g.goods_id,g.goods_name,f.price AS shop_price,f.end_time')
            ->join('__FLASH_SALE__ f','g.goods_id = f.goods_id')
            ->where($goods_where)
            ->limit(3)
            ->select();
        return $promotion_goods;
    }

    /**
     * 获取精品商品数据
     * @return mixed
     */
    public function getRecommendGoods($p = 1)
    {
        $goods_where = array('is_recommend' => 1, 'goods_state' => 1, 'is_on_sale' => 1);
        $goods_where['goods_name'] = array("exp", " NOT REGEXP '华为|荣耀|小米|合约机|三星|魅族' ");//临时屏蔽,苹果APP审核过了之后注释
        $promotion_goods = M('goods')
            ->field('goods_id,goods_name,shop_price,cat_id3')
            ->where($goods_where)
            ->order('sort DESC')
            ->page($p, 10)
            ->cache(true, 3600)
            ->select();
        return $promotion_goods;
    }

    /**
     * 获取新品商品数据
     * @return mixed
     */
    public function getNewGoods()
    {
        $goods_where = array('is_new' => 1, 'goods_state' => 1, 'is_on_sale' => 1);
        $goods_where['goods_name'] = array("exp", " NOT REGEXP '华为|荣耀|小米|合约机|三星|魅族' ");//临时屏蔽,苹果APP审核过了之后注释
        $orderBy = array('sort' => 'desc');
        $new_goods = M('goods')
            ->field('goods_id,goods_name,shop_price')
            ->where($goods_where)
            ->order($orderBy)
            ->limit(9)
            ->cache(true, 3600)
            ->select();
        return $new_goods;
    }

    /**
     * 获取热销商品数据
     * @return mixed
     */
    public function getHotGood()
    {
        $goods_where = array('is_hot' => 1, 'goods_state' => 1, 'is_on_sale' => 1);
        $orderBy = array('sort' => 'desc');
        $new_goods = M('goods')
            ->field('goods_id,goods_name,shop_price')
            ->where($goods_where)
            ->order($orderBy)
            ->limit(20)
            ->cache(true, 3600)
            ->select();
        return $new_goods;
    }

    /**
     * 获取首页轮播图片
     * @return mixed
     */
    public function getHomeAdv()
    {
        $start_time = strtotime(date('Y-m-d H:00:00'));
        $end_time = strtotime(date('Y-m-d H:00:00'));
        $adv = M("ad")->field(array('ad_link','ad_name','ad_code'))
                ->where("pid=2 and enabled=1 and start_time< $start_time and end_time > $end_time")
                ->order("orderby desc")->cache(true,3600)
                ->limit(5)->select();
        
        //广告地址转换
        foreach($adv as $k=>$v){
            if(!strstr($v['ad_link'],'http')){
                $adv[$k]['ad_link'] = SITE_URL.$v['ad_link'];
            }
            $adv[$k]['ad_code'] = SITE_URL.$v['ad_code'];
        }
        return $adv;
    }

    /**
     * 获取秒杀商品
     * @return mixed
     */
    public function getFlashSaleGoods($count, $page = 1, $start_time=0, $end_time=0)
    {
        $where['f.status'] = 1;
        $where['f.start_time'] = array('egt', $start_time ?: time());
        if ($end_time) {
            $where['f.end_time'] = array('elt',$end_time);
        }

        $flash_sale_goods = M('flash_sale')
            ->field('f.end_time,f.goods_name,f.price,f.goods_id,f.price,g.shop_price,100*(FORMAT(f.buy_num/f.goods_num,2)) as percent')
            ->alias('f')
            ->join('__GOODS__ g','g.goods_id = f.goods_id')
            ->where($where)
            //->order('f.start_time', 'asc')
            ->page($page, $count)
            ->cache(true, 120)
            ->select();
        return $flash_sale_goods;
    }
    /**
     *  获取排好序的品牌列表
     */
    function getSortBrands()
    {
        $brandList =  M("Brand")->select();
        $brandIdArr =  M("Brand")->where("name in (select `name` from `".C('database.prefix')."brand` group by name having COUNT(id) > 1)")->getField('id,cat_id2');
        $goodsCategoryArr = M('goodsCategory')->where("level = 1")->getField('id,name');
        $nameList = array();
        foreach($brandList as $k => $v)
        {

            $name = getFirstCharter($v['name']) .'  --   '. $v['name']; // 前面加上拼音首字母

            if(array_key_exists($v[id],$brandIdArr) && $v[cat_id]) // 如果有双重品牌的 则加上分类名称
                $name .= ' ( '. $goodsCategoryArr[$v[cat_id]] . ' ) ';

            $nameList[] = $v['name'] = $name;
            $brandList[$k] = $v;
        }
        array_multisort($nameList,SORT_STRING,SORT_ASC,$brandList);

        return $brandList;
    }

    /**
     *  获取排好序的分类列表
     */
    function getSortCategory()
    {
        $categoryList =  M("GoodsCategory")->getField('id,name,parent_id,level');
        $nameList = array();
        foreach($categoryList as $k => $v)
        {

            //$str_pad = str_pad('',($v[level] * 5),'-',STR_PAD_LEFT);
            $name = getFirstCharter($v['name']) .' '. $v['name']; // 前面加上拼音首字母
            //$name = getFirstCharter($v['name']) .' '. $v['name'].' '.$v['level']; // 前面加上拼音首字母
            /*
            // 找他老爸
            $parent_id = $v['parent_id'];
            if($parent_id)
                $name .= '--'.$categoryList[$parent_id]['name'];
            // 找他 爷爷
            $parent_id = $categoryList[$v['parent_id']]['parent_id'];
            if($parent_id)
                $name .= '--'.$categoryList[$parent_id]['name'];
            */
            $nameList[] = $v['name'] = $name;
            $categoryList[$k] = $v;
        }
        array_multisort($nameList,SORT_STRING,SORT_ASC,$categoryList);

        return $categoryList;
    }
    /**
     * cart_freight2
     * 计算商品的的运费
     * @param $shipping_code|物流 编号
     * @param $province|省份
     * @param $city|市
     * @param $district|区
     * @param $weight|商品重量
     * @param string $store_id|店铺id
     * @return int
     */
    function getFreight($shipping_code,$province,$city,$district,$weight,$store_id='0')
    {
        if($weight == 0) return 0; // 商品没有重量
        if($shipping_code == '') return 0;
        
        // 先根据 镇 县 区找 shipping_area_id
        if ($district != 0) {
            $shipping_area_id = M('AreaRegion')->cache(true,10)->where("store_id = $store_id and shipping_area_id in (select shipping_area_id from  ".C('database.prefix')."shipping_area where shipping_code = '$shipping_code') and region_id = {$district}")->getField('shipping_area_id');
        }
        
        // 先根据市区找 shipping_area_id
        if($shipping_area_id == false && $city != 0) {
            $shipping_area_id = M('AreaRegion')->cache(true,10)->where("store_id = $store_id and shipping_area_id in (select shipping_area_id from  ".C('database.prefix')."shipping_area where shipping_code = '$shipping_code') and region_id = {$city}")->getField('shipping_area_id');
        }
        
        // 市区找不到 根据省份找shipping_area_id
        if($shipping_area_id == false && $province != 0) {
            $shipping_area_id = M('AreaRegion')->cache(true,10)->where("store_id = $store_id and shipping_area_id in (select shipping_area_id from  ".C('database.prefix')."shipping_area where shipping_code = '$shipping_code') and region_id = {$province}")->getField('shipping_area_id');
        }
        
        // 省份找不到 找默认配置全国的物流费
        if($shipping_area_id == false) {
            // 如果市和省份都没查到, 就查询 tp_shipping_area 表 is_default = 1 的  表示全国的  select * from `tp_plugin`  select * from  `tp_shipping_area` select * from  `tp_area_region`
            $shipping_area_id = M("ShippingArea")->cache(true,10)->where(['store_id'=>$store_id,'shipping_code'=>$shipping_code,'is_default'=>1,'is_close'=>1])->getField('shipping_area_id');
        }
        
        if($shipping_area_id == false) {
            return 0;
        }
        
        /// 找到了 shipping_area_id  找config
        $shipping_config = M('ShippingArea')->cache(true,10)->where("shipping_area_id = $shipping_area_id")->getField('config');
        $shipping_config  = unserialize($shipping_config);
        $shipping_config['money'] = $shipping_config['money'] ? $shipping_config['money'] : 0;

        // 1000 克以内的 只算个首重费
        if($weight < $shipping_config['first_weight'])
        {
            return $shipping_config['money'];
        }
        // 超过 1000 克的计算方法
        $weight = $weight - $shipping_config['first_weight']; // 续重
        $weight = ceil($weight / $shipping_config['second_weight']); // 续重不够取整
        $freight = $shipping_config['money'] +  $weight * $shipping_config['add_money']; // 首重 + 续重 * 续重费

        return $freight;
    }
    
    /**
     * 获取活动简要信息
     * @param array $goods
     * @param FlashSaleLogic|GroupBuyLogic|PromGoodsLogic $goodsPromLogic
     * @return array
     */
    public function getActivitySimpleInfo($goods, $goodsPromLogic)
    {
        //1.商品促销
        $activity = $this->getGoodsPromSimpleInfo($goods, $goodsPromLogic);
        
        //2.订单促销
        $activity_order = $this->getOrderPromSimpleInfo($goods);
        
        //3.数据合并
        if ($activity['data'] || $activity_order) {
            empty($activity['data']) && $activity['data'] = [];
            $activity['data'] = array_merge($activity['data'], $activity_order);
        }
        
        $activity['server_current_time'] = time();//服务器时间
        return $activity;
    }
    
    /**
     * 获取商品促销简单信息
     * @param array $goods
     * @param FlashSaleLogic|GroupBuyLogic|PromGoodsLogic $goodsPromLogic
     * @return array
     */
    public function getGoodsPromSimpleInfo($goods, $goodsPromLogic)
    {
        //prom_type: 0默认 1抢购 2团购 3优惠促销 4预售(不考虑)
        $activity['prom_type'] = 0;
    
        $goodsPromFactory = new \app\common\logic\GoodsPromFactory;
        if (!$goodsPromFactory->checkPromType($goods['prom_type'])
                || !$goodsPromLogic || !$goodsPromLogic->checkActivityIsAble()) {
            return $activity;
        }

        // 1抢购 2团购
        $prom = $goodsPromLogic->getPromModel()->getData();
        if (in_array($goods['prom_type'], [1, 2])) {
            $info = $goodsPromLogic->getActivityGoodsInfo();
            $activity = [
                'prom_type' => $goods['prom_type'],
                'prom_price' => $prom['price'],
                'prom_start_time' => $prom['start_time'],
                'prom_end_time' => $prom['end_time'],
                'prom_store_count' => $info['store_count'],
                'virtual_num' => $info['virtual_num']
            ];
            return $activity;
        }
        
        // 3优惠促销
        // type:0直接打折,1减价优惠,2固定金额出售,3买就赠优惠券
        if ($prom['type'] == 0) {
            $activityData[] = ['title' => '折扣', 'content' => "指定商品立打{$prom['expression']}折"];
        } elseif ($prom['type'] == 1) {
            $activityData[] = ['title' => '直减', 'content' => "指定商品立减{$prom['expression']}元"];
        } elseif ($prom['type'] == 2) {
            $activityData[] = ['title' => '促销', 'content' => "促销价{$prom['expression']}元"];
        } elseif ($prom['type'] == 3) {
            $couponLogic = new \app\common\logic\CouponLogic;
            $money = $couponLogic->getSendValidCouponMoney($prom['expression'], $goods['goods_id'], $goods['store_id'], $goods['cat_id3']);
            if ($money !== false) {
                $activityData[] = ['title' => '送券', 'content' => "买就送代金券{$money}元"];
            }
        }
        if ($activityData) {
            $activityInfo = $goodsPromLogic->getActivityGoodsInfo();
            $activity = [
                'prom_type' => $goods['prom_type'],
                'prom_price' => $activityInfo['shop_price'],
                'prom_start_time' => $prom['start_time'],
                'prom_end_time' => $prom['end_time'],
                'data' => $activityData
            ];
        }

        return $activity;
    }
    
    /**
     * 获取
     * @param type $user_level
     * @param type $cur_time
     * @param type $goods
     * @return string|array
     */
    public function getOrderPromSimpleInfo($goods)
    {
        $cur_time = time();
//        $sql = "select * from __PREFIX__prom_order where start_time <= $cur_time AND end_time > $cur_time"
//                . " AND status = 1 AND store_id = {$goods['store_id']}";
//        $po = Db::query($sql);
        $data = [];
        $po = M('prom_order')->where(['start_time' => ['<=', $cur_time], 'end_time' => ['>', $cur_time], 'status' => 1, 'store_id' => $goods['store_id']])->select();
        if (!empty($po)) {
            foreach ($po as $p) {
                //type:0满额打折,1满额优惠金额,2满额送积分,3满额送优惠券
                if ($p['type'] == 0) {
                    $data[] = ['title' => '折扣', 'content' => "满{$p['money']}元打{$p['expression']}折"];
                } elseif ($p['type'] == 1) {
                    $data[] = ['title' => '优惠', 'content' => "满{$p['money']}元优惠{$p['expression']}元"];
                } elseif ($p['type'] == 2) {
                    //积分暂不支持?
                } elseif ($p['type'] == 3) {
                    $couponLogic = new \app\common\logic\CouponLogic;
                    $money = $couponLogic->getSendValidCouponMoney($p['expression'], $goods['goods_id'], $goods['store_id'], $goods['cat_id3']);
                    if ($money !== false) {
                        $data[] = ['title' => '送券', 'content' => "满{$p['money']}元送{$money}元优惠券"];
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 订单支付时显示的优惠显示
     * @param type $user
     * @param type $store_id
     * @return type
     */
    public function getOrderPayProm($store_id)
    {
        $cur_time = time();
        $sql = "select * from __PREFIX__prom_order where start_time <= $cur_time AND end_time > $cur_time"
                . " AND status = 1 AND store_id = {$store_id}";
        $data = '';
        $po = Db::query($sql);
        if (!empty($po)) {
            foreach ($po as $p) {
                //type:0满额打折,1满额优惠金额,2满额送积分,3满额送优惠券
                if ($p['type'] == 0) {
                    $data = "满{$p['money']}元打{$p['expression']}折";
                } elseif ($p['type'] == 1) {
                    $data = "满{$p['money']}元优惠{$p['expression']}元";
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 是否收藏商品
     * @param type $user_id
     * @param type $goods_id
     * @return type
     */
    public function isCollectGoods($user_id, $goods_id)
    {
        $collect = M('goods_collect')->where(['user_id' => $user_id, 'goods_id' => $goods_id])->find();
        return $collect ? 1 : 0;
    }
}

 