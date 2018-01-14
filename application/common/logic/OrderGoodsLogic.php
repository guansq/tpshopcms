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
use think\Db;
/**
 * Class OrderGoodsLogic
 * @package common\Logic
 */
class OrderGoodsLogic extends Model
{
    /**
     * 查找订单下的所有未评价的商品
     * @param $order_id
     * @return mixed
     */
    public function get_no_comment_goods_list($order_id){
        $no_comment_goods_where['is_comment'] = 0;
        $no_comment_goods_where['order_id'] = $order_id;
        $no_comment_goods_where['deleted'] = 0;
        $no_comment_goods_list = Db::name('order_goods')
            ->field('rec_id,order_id,goods_id,goods_name,spec_key_name,goods_price')
            ->where($no_comment_goods_where)
            ->select();
        return $no_comment_goods_list;
    }

    /**
     * 获取订单里没有被评价的商品（单条）
     * @param $order_id
     * @param $rec_id
     * @return array|false|\PDOStatement|string|Model
     */
    public function get_no_comment_goods($order_id,$rec_id){
        $no_comment_goods_where['is_comment'] = 0;
        $no_comment_goods_where['order_id'] = $order_id;
        $no_comment_goods_where['deleted'] = 0;
        $no_comment_goods_where['rec_id'] = $rec_id;
        $no_comment_goods_list = DB::name('order_goods')
            ->field('rec_id,order_id,goods_id,goods_name,spec_key_name,goods_price')
            ->where($no_comment_goods_where)
            ->find();
        return $no_comment_goods_list;
    }

}

 