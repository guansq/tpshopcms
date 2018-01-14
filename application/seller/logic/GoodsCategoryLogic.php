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
class GoodsCategoryLogic extends Model
{
    protected $store;

    public function setStore($store){
        $this->store = $store;
    }

    /**
     * 获取店铺的商品分类
     * @param int $parent_id
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function getStoreGoodsCategory($parent_id = 0){
        $goods_category_list = Db::name('goods_category')->where(array('parent_id' => $parent_id))->order('sort_order desc')->select();
        if($this->store['bind_all_gc'] == 0){
            $bind_class_where = ['store_id' => $this->store['store_id'], 'state' => 1];
            if($goods_category_list[0]['level'] == 1){
                $class_id = Db::name('store_bind_class')->where($bind_class_where)->getField('class_1', true);
            }elseif($goods_category_list[0]['level'] == 2){
                $class_id = Db::name('store_bind_class')->where($bind_class_where)->getField('class_2', true);
            }else{
                $class_id = Db::name('store_bind_class')->where($bind_class_where)->getField('class_3', true);
            }
            if($class_id){
                $store_category_list = [];
                foreach ($goods_category_list as $categoryKey => $categoryItem) {
                    // 如果是某个店铺登录的, 那么这个店铺只能看到自己申请的分类,其余的看不到
                    if (in_array($categoryItem['id'], $class_id)){
                        $store_category_list[] = $goods_category_list[$categoryKey];
                    }
                }
                return $store_category_list;
            }
        }
        return $goods_category_list;
    }
}