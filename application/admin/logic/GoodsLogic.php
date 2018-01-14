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

namespace app\admin\logic;
use think\Model;
use think\Db;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class GoodsLogic extends Model
{

    /**
     * 获得指定分类下的子分类的数组     
     * @access  public
     * @param   int     $cat_id     分类的ID
     * @param   int     $selected   当前选中分类的ID
     * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
     * @param   int     $level      限定返回的级数。为0时返回所有级数
     * @return  mix
     */
    public function goods_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
    {
                global $goods_category, $goods_category2;                
                $sql = "SELECT * FROM  __PREFIX__goods_category ORDER BY parent_id , sort_order ASC";
                $goods_category = DB::query($sql);
                $goods_category = convert_arr_key($goods_category, 'id');
                
                foreach ($goods_category AS $key => $value)
                {
                    if($value['level'] == 1)
                        $this->get_cat_tree($value['id']);                                
                }
                /*
                foreach ($goods_category2 AS $key => $value)
                {
                        $strpad_count = $value['level']*10;
                        echo str_pad('',$strpad_count,"-",STR_PAD_LEFT);             
                        echo $value['name'];
                        echo "<br/>";
                }*/
                return $goods_category2;               
    }
    
    /**
     * 获取指定id下的 所有分类      
     * @global type $goods_category 所有商品分类
     * @param type $id 当前显示的 菜单id
     * @return 返回数组 Description
     */
    public function get_cat_tree($id)
    {
        global $goods_category, $goods_category2;          
        $goods_category2[$id] = $goods_category[$id];    
        foreach ($goods_category AS $key => $value){
             if($value['parent_id'] == $id)
             {                 
                $this->get_cat_tree($value['id']);  
                $goods_category2[$id]['have_son'] = 1; // 还有下级
             }
        }               
    }
    /**
     * 改变或者添加分类时 需要修改他下面的 parent_id_path  和 level 
     * @global type $cat_list 所有商品分类
     * @param type $parent_id_path 指定的id
     * @return 返回数组 Description
     */
    public function refresh_cat($id)
    {            
        $GoodsCategory = M("GoodsCategory"); // 实例化User对象
        $cat = $GoodsCategory->where("id = $id")->find(); // 找出他自己
        // 刚新增的分类先把它的值重置一下
        if($cat['parent_id_path'] == '')
        {
            ($cat['parent_id'] == 0) && $GoodsCategory->execute("UPDATE __PREFIX__goods_category set  parent_id_path = '0_$id', level = 1 where id = $id"); // 如果是一级分类               
            $GoodsCategory->execute("UPDATE __PREFIX__goods_category AS a ,__PREFIX__goods_category AS b SET a.parent_id_path = CONCAT_WS('_',b.parent_id_path,'$id'),a.level = (b.level+1) WHERE a.parent_id=b.id AND a.id = $id");                
            $cat = $GoodsCategory->where("id = $id")->find(); // 从新找出他自己
        }        
        
        if($cat['parent_id'] == 0) //有可能是顶级分类 他没有老爸
        {
            $parent_cat['parent_id_path'] =   '0';   
            $parent_cat['level'] = 0;
        }
        else{
            $parent_cat = $GoodsCategory->where("id = {$cat['parent_id']}")->find(); // 找出他老爸的parent_id_path
        }        
        $replace_level = $cat['level'] - ($parent_cat['level'] + 1); // 看看他 相比原来的等级 升级了多少  ($parent_cat['level'] + 1) 他老爸等级加一 就是他现在要改的等级
        $replace_str = $parent_cat['parent_id_path'].'_'.$id;                
        $GoodsCategory->execute("UPDATE `__PREFIX__goods_category` SET parent_id_path = REPLACE(parent_id_path,'{$cat['parent_id_path']}','$replace_str'), level = (level - $replace_level) WHERE  parent_id_path LIKE '{$cat['parent_id_path']}%'");        
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        $GoodsAttr = D('GoodsAttr');        
        if($goods_attr_id > 0)
            return $GoodsAttr->where("goods_attr_id = $goods_attr_id")->select(); 
        if($goods_id > 0 && $attr_id > 0)
            return $GoodsAttr->where("goods_id = $goods_id and attr_id = $attr_id")->select();        
    }
   
    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type,$store_id)
    {  
        $GoodsAttr = D('GoodsAttr');
        $Goods = M("Goods");
               
         // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除        
        if($goods_type == 0)  
        {
            $GoodsAttr->where('goods_id = '.$goods_id)->delete(); 
            return;
        }
        
            $GoodsAttrList = $GoodsAttr->where('goods_id = '.$goods_id)->select();
            
            $old_goods_attr = array(); // 数据库中的的属性  以 attr_id _ 和值的 组合为键名
            foreach($GoodsAttrList as $k => $v)
            {                
                $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
            }            
                              
            // post 提交的属性  以 attr_id _ 和值的 组合为键名    
            $post_goods_attr = array();
            foreach($_POST as $k => $v)
            {
                $attr_id = str_replace('attr_','',$k);
                if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                   continue;                                 
               foreach ($v as $k2 => $v2)
               {                      
                   $v2 = str_replace('_', '', $v2); // 替换特殊字符
                   $v2 = str_replace('@', '', $v2); // 替换特殊字符
                   $v2 = trim($v2);
                   
                   if(empty($v2))
                       continue;
                   
                   
                   $tmp_key = $attr_id."_".$v2;
                   $attr_price = $_POST["attr_price_$attr_id"][$k2]; 
                   $attr_price = $attr_price ? $attr_price : 0;
                   if(array_key_exists($tmp_key , $old_goods_attr)) // 如果这个属性 原来就存在
                   {   
                       if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price) // 并且价格不一样 就做更新处理
                       {                       
                            $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];                         
                            $GoodsAttr->where("goods_attr_id = $goods_attr_id")->save(array('attr_price'=>$attr_price));                       
                       }
                   }
                   else // 否则这个属性 数据库中不存在 说明要做删除操作
                   {
                       $GoodsAttr->add(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));                       
                   }
                   unset($old_goods_attr[$tmp_key]);
               }
                
            }     
            //file_put_contents("b.html", print_r($post_goods_attr,true));
            // 没有被 unset($old_goods_attr[$tmp_key]); 掉是 说明 数据库中存在 表单中没有提交过来则要删除操作
            foreach($old_goods_attr as $k => $v)
            {                
               $GoodsAttr->where('goods_attr_id = '.$v['goods_attr_id'])->delete(); // 
            }                       

    }

    /**
     *  获取选中的下拉框
     * @param type $cat_id
     */
    function find_parent_cat($cat_id)
    {
        if($cat_id == null) 
            return array();
        
        $cat_list =  M('goods_category')->getField('id,parent_id,level');
        $cat_level_arr[$cat_list[$cat_id]['level']] = $cat_id;

        // 找出他老爸
        $parent_id = $cat_list[$cat_id]['parent_id'];
        if($parent_id > 0)
             $cat_level_arr[$cat_list[$parent_id]['level']] = $parent_id;
        // 找出他爷爷
        $grandpa_id = $cat_list[$parent_id]['parent_id'];
        if($grandpa_id > 0)
             $cat_level_arr[$cat_list[$grandpa_id]['level']] = $grandpa_id;
        
        // 建议最多分 3级, 不要继续往下分太多级
        // 找出他祖父
        $grandfather_id = $cat_list[$grandpa_id]['parent_id'];
        if($grandfather_id > 0)
             $cat_level_arr[$cat_list[$grandfather_id]['level']] = $grandfather_id;
        
        return $cat_level_arr;      
    }        
    
    /**
     *  获取排好序的品牌列表    
     */
    function getSortBrands()
    {
        $brandList = S('getSortBrands',$brandList);
        if(!empty($brandList))
            return $brandList;
        $brandList =  M("Brand")->select();
        $brandIdArr =  M("Brand")->where("name in (select `name` from `".C('database.prefix')."brand` group by name having COUNT(id) > 1)")->cache(true)->getField('id,cat_id2');
        $goodsCategoryArr = M('goodsCategory')->where("level = 1")->cache(true)->getField('id,name');
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
       
        S('getSortBrands',$brandList); 
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
     * 获取地址
     * @return array
     */
    function getRegionList()
    {
        $res = S('getRegionList');
        if(!empty($res))
            return $res;
        $parent_region = M('region')->field('id,name')->where(array('level'=>1))->cache(true)->select();
        $ip_location = array();
        $city_location = array();
        foreach($parent_region as $key=>$val){
            $c = M('region')->field('id,name')->where(array('parent_id'=>$parent_region[$key]['id']))->order('id asc')->cache(true)->select();
            $ip_location[$parent_region[$key]['name']] = array('id'=>$parent_region[$key]['id'],'root'=>0,'djd'=>1,'c'=>$c[0]['id']);
            $city_location[$parent_region[$key]['id']] = $c;
        }
        $res = array(
            'ip_location'=>$ip_location,
            'city_location'=>$city_location
        );
        S('getRegionList',$res);
        return $res;
    }
}