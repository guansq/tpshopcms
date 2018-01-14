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

namespace app\seller\logic;

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
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     * @return string
     */
    public function getAttrInput($goods_id, $type_id)
    {
        header("Content-type: text/html; charset=utf-8");
        $GoodsAttribute = D('GoodsAttribute');
        $attributeList = $GoodsAttribute->where("type_id", $type_id)->select();

        foreach ($attributeList as $key => $val) {

            $curAttrVal = $this->getGoodsAttrVal(NULL, $goods_id, $val['attr_id']);
            //促使他 循环
            if (count($curAttrVal) == 0)
                $curAttrVal[] = array('goods_attr_id' => '', 'goods_id' => '', 'attr_id' => '', 'attr_value' => '', 'attr_price' => '');
            foreach ($curAttrVal as $k => $v) {
                $str .= "<tr class='attr_{$val['attr_id']}'>";
                $addDelAttr = ''; // 加减符号
                // 单选属性 或者 复选属性
                if ($val['attr_type'] == 1 || $val['attr_type'] == 2) {
                    if ($k == 0)
                        $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";
                    else
                        $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";
                }

                $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";

                // if($v['goods_attr_id'] > 0) //tp_goods_attr 表id
                //     $str .= "<input type='hidden' name='goods_attr_id[]' value='{$v['goods_attr_id']}'/>";

                // 手工录入
                if ($val['attr_input_type'] == 0) {
                    $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                }
                // 从下面的列表中选择（一行代表一个可选值）
                if ($val['attr_input_type'] == 1) {
                    $str .= "<select name='attr_{$val['attr_id']}[]'>";
                    $tmp_option_val = explode(',', $val['attr_values']);

                    $str .= "<option value=''>不限</option>";
                    foreach ($tmp_option_val as $k2 => $v2) {
                        // 编辑的时候 有选中值
                        $v2 = preg_replace("/\s/", "", $v2);
                        if ($v['attr_value'] == $v2)
                            $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                        else
                            $str .= "<option value='{$v2}'>{$v2}</option>";
                    }
                    $str .= "</select>";
                    //$str .= "属性价格<input type='text' maxlength='10' size='5' value='{$v['attr_price']}' name='attr_price_{$val['attr_id']}[]'>";
                }
                // 多行文本框
                if ($val['attr_input_type'] == 2) {
                    $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                    //$str .= "属性价格<input type='text' maxlength='10' size='5' value='{$v['attr_price']}' name='attr_price_{$val['attr_id']}[]'>";
                }
                $str .= "</td></tr>";
                //$str .= "<br/>";
            }

        }
        return $str;
    }

    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0, $goods_id = 0, $attr_id = 0)
    {
        $GoodsAttr = D('GoodsAttr');
        if ($goods_attr_id > 0)
            return $GoodsAttr->where("goods_attr_id", $goods_attr_id)->select();
        if ($goods_id > 0 && $attr_id > 0)
            return $GoodsAttr->where(['goods_id' => $goods_id, 'attr_id' => $attr_id])->select();
    }

    /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id 商品id
     * @param int $goods_type 商品类型id
     */
    public function saveGoodsAttr($goods_id, $goods_type)
    {
        $GoodsAttr = M('GoodsAttr');
        //$Goods = M("Goods");

        // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除
        if ($goods_type == 0) {
            $GoodsAttr->where('goods_id', $goods_id)->delete();
            return;
        }

        $GoodsAttrList = $GoodsAttr->where('goods_id', $goods_id)->select();

        $old_goods_attr = array(); // 数据库中的的属性  以 attr_id _ 和值的 组合为键名
        foreach ($GoodsAttrList as $k => $v) {
            $old_goods_attr[$v['attr_id'] . '_' . $v['attr_value']] = $v;
        }

        // post 提交的属性  以 attr_id _ 和值的 组合为键名
        $post_goods_attr = array();
        foreach ($_POST as $k => $v) {
            $attr_id = str_replace('attr_', '', $k);
            if (!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                continue;
            foreach ($v as $k2 => $v2) {
                $v2 = str_replace('_', '', $v2); // 替换特殊字符
                $v2 = str_replace('@', '', $v2); // 替换特殊字符
                $v2 = trim($v2);

                if (empty($v2))
                    continue;


                $tmp_key = $attr_id . "_" . $v2;
                $attr_price = $_POST["attr_price_$attr_id"][$k2];
                $attr_price = $attr_price ? $attr_price : 0;
                if (array_key_exists($tmp_key, $old_goods_attr)) // 如果这个属性 原来就存在
                {
                    if ($old_goods_attr[$tmp_key]['attr_price'] != $attr_price) // 并且价格不一样 就做更新处理
                    {
                        $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];
                        $GoodsAttr->where("goods_attr_id", $goods_attr_id)->save(array('attr_price' => $attr_price));
                    }
                } else // 否则这个属性 数据库中不存在 说明要做删除操作
                {
                    $GoodsAttr->add(array('goods_id' => $goods_id, 'attr_id' => $attr_id, 'attr_value' => $v2, 'attr_price' => $attr_price));
                }
                unset($old_goods_attr[$tmp_key]);
            }

        }
        //file_put_contents("b.html", print_r($post_goods_attr,true));
        // 没有被 unset($old_goods_attr[$tmp_key]); 掉是 说明 数据库中存在 表单中没有提交过来则要删除操作

        foreach ($old_goods_attr as $k => $v) {
            $GoodsAttr->where('goods_attr_id', $v['goods_attr_id'])->delete(); //
        }

    }

    /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr, $store_id = 0)
    {
        // <input name="item[2_4_7][price]" value="100" /><input name="item[2_4_7][name]" value="蓝色_S_长袖" />        
        /*$spec_arr = array(         
            20 => array('7','8','9'),
            10=>array('1','2'),
            1 => array('3','4'),
            
        );  */
        // 排序
        $spec_arr_sort = $spec_arr2 = array();
        foreach ($spec_arr as $k => $v) {
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key => $val) {
            $spec_arr2[$key] = $spec_arr[$key];
        }


        $clo_name = array_keys($spec_arr2);
        $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积

        $spec = M('Spec')->getField('id,name'); // 规格表
        $specItem = M('SpecItem')->where("store_id", $store_id)->getField('id,item,spec_id');//规格项
        $keySpecGoodsPrice = M('SpecGoodsPrice')->where(['store_id' => $store_id, 'goods_id' => $goods_id])->getField('key,key_name,price,store_count,sku');//规格项

        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .= "<tr>";
        // 显示第一行的数据
        foreach ($clo_name as $k => $v) {
            $str .= " <td><b>{$spec[$v]}</b></td>";
        }
        $str .= "<td><b>价格</b></td>
               <td><b>库存</b></td>
               <td><b>SKU</b></td>
             </tr>";
        // 显示第二行开始
        foreach ($spec_arr2 as $k => $v) {
            $str .= "<tr>";
            $item_key_name = array();
            $spec_img_class = '';
            foreach ($v as $k2 => $v2) {
                $str .= "<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']] . ':' . $specItem[$v2]['item'];
                $spec_img_class .= 'spec_img_'.$v2.' ';
            }
            ksort($item_key_name);
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);

            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .= "<td><input class='batch-fill-text1' name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .= "<td><input class='batch-fill-text2' name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .= "<td><input class='batch-fill-text3' name='item[$item_key][sku]' value='{$keySpecGoodsPrice[$item_key][sku]}' />";
            $str .= "<input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .= "</tr>";
        }
        $str .= "</table>";
        return $str;
    }
    /**
     *  获取排好序的品牌列表

     */
    function getSortBrands()
    {
        $brandList = S('getSortBrands');
        if(!empty($brandList))
            return $brandList;

        $brandList = M("Brand")->select();
//        $brandIdArr =  M("Brand")->where("name in (select `name` from `".C('database.prefix')."brand` group by name having COUNT(id) > 1)")->getField('id,cat_id2');
        $brandIdArr = Db::name('brand')->where('id', 'IN', function ($query) {
            $query->name('brand')->group('name')->having('COUNT(id) > 1')->field('name');
        })->getField('id,cat_id2');
        $goodsCategoryArr = M('goodsCategory')->where("level", 1)->getField('id,name');
        $nameList = array();
        foreach ($brandList as $k => $v) {

            $name = getFirstCharter($v['name']) . '  --   ' . $v['name']; // 前面加上拼音首字母

            if (array_key_exists($v[id], $brandIdArr) && $v[cat_id]) // 如果有双重品牌的 则加上分类名称
                $name .= ' ( ' . $goodsCategoryArr[$v[cat_id]] . ' ) ';

            $nameList[] = $v['name'] = $name;
            $brandList[$k] = $v;
        }
        array_multisort($nameList, SORT_STRING, SORT_ASC, $brandList);
        S('getSortBrands',$brandList); 
        return $brandList;
    }

    /**
     *  获取排好序的分类列表
     */
    function getSortCategory()
    {
        $categoryList = M("GoodsCategory")->getField('id,name,parent_id,level');
        $nameList = array();
        foreach ($categoryList as $k => $v) {

            //$str_pad = str_pad('',($v[level] * 5),'-',STR_PAD_LEFT);
            $name = getFirstCharter($v['name']) . ' ' . $v['name']; // 前面加上拼音首字母
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
        array_multisort($nameList, SORT_STRING, SORT_ASC, $categoryList);

        return $categoryList;
    }
}