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
 */

namespace app\seller\model;
use think\model;
use think\Db;
class Goods extends model {

    /**
     * 一个商品对应多个规格
     * @return model\relation\HasMany
     */
    public function specGoodsPrice()
    {
        return $this->hasMany('SpecGoodsPrice','goods_id','goods_id');
    }
    public function getShippingAreaIdArrAttr($value,$data)
    {
        if($data['shipping_area_ids']){
            return explode(',', $data['shipping_area_ids']);
        }else{
            return [];
        }
    }
    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     * @param int $store_id 店铺id
     */
    public function afterSave($goods_id,$store_id)
    {
        // 商品货号
        $goods_sn = "TP".str_pad($goods_id,7,"0",STR_PAD_LEFT);
        $this->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录
        $goods_images = I('goods_images/a');
        $img_sorts = I('img_sorts/a');
        $original_img = I('original_img');
        $item_img = I('item_img/a');

        // 商品图片相册  图册
        if(count($goods_images) > 1)
        {
            array_pop($goods_images); // 弹出最后一个
            $goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片

            // 删除图片
            foreach($goodsImagesArr as $key => $val)
            {
                if(!in_array($val, $goods_images)){
                    M('GoodsImages')->where("img_id = {$key}")->delete();

                    //同时删除物理文件
                    $filename = $val;
                    $filename= str_replace('../','',$filename);
                    $filename= trim($filename,'.');
                    $filename= trim($filename,'/');
                    $is_exists = file_exists($filename);

                    //同时删除物理文件
                    if($is_exists){
                        //unlink($filename);
                    }
                }
            }
            $goodsImagesArrRever = array_flip($goodsImagesArr);
            // 添加图片
            foreach($goods_images as $key => $val)
            {
                $sort = $img_sorts[$key];
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    $data = array( 'goods_id' => $goods_id,'image_url' => $val , 'img_sort'=>$sort);
                    M("GoodsImages")->insert($data); // 实例化User对象
                }else{
                    $img_id = $goodsImagesArrRever[$val];
                    //修改图片顺序
                    M('GoodsImages')->where("img_id = {$img_id}")->save(array('img_sort' => $sort));
                }
            }
        }

        // 查看主图是否已经存在相册中
        $c = M('GoodsImages')->where("goods_id = $goods_id and image_url = '{$original_img}'")->count();

        //@modify by wangqh fix:修复删除商品详情的图片(相册图刚好是主图时)删除的图片仍然在相册中显示. 如果主图存物理图片存在才添加到相册 @{
        $deal_orignal_img = str_replace('../','',$original_img);
        $deal_orignal_img= trim($deal_orignal_img,'.');
        $deal_orignal_img= trim($deal_orignal_img,'/');
        if($c == 0 && $original_img && file_exists($deal_orignal_img))//@}
        {
            M("GoodsImages")->add(array('goods_id'=>$goods_id,'image_url'=>$original_img));
        }
        //delFile("./public/upload/goods/thumb/$goods_id"); // 删除缩略图
        //delFile("./runtime");
        \think\Cache::clear();
        // 商品规格价钱处理
        $item = I('item/a');
//        M("SpecGoodsPrice")->where('goods_id = '.$goods_id)->delete(); // 删除原有的价格规格对象

        if ($item) {
            $store_count = 0;
            $keyArr = [];//规格key数组
            foreach ($item as $k => $v) {
                //批量添加数据
                $v['price'] = trim($v['price']);
                $store_count += $v['store_count']; // 记录商品总库存
                $v['sku'] = trim($v['sku']);
                $data = array('goods_id' => $goods_id, 'key' => $k, 'key_name' => $v['key_name'], 'price' => $v['price'], 'store_count' => $v['store_count'], 'sku' => $v['sku'], 'store_id' => $store_id);
                $specGoodsPrice = Db::name('spec_goods_price')->where(['goods_id' => $data['goods_id'], 'key' => $data['key']])->find();
                if ($item_img) {
                    $spec_key_arr = explode('_', $k);
                    foreach ($item_img as $key => $val) {
                        if (in_array($key, $spec_key_arr)) {
                            $data['spec_img'] = $val;
                            break;
                        }
                    }
                }
                if($specGoodsPrice){
                    Db::name('spec_goods_price')->where(['goods_id' => $goods_id, 'key' => $k])->update($data);
                }else{
                    Db::name('spec_goods_price')->insert($data);
                }
                $dataList[] = $data;
                $keyArr[] = $k;
                // 修改商品后购物车的商品价格也修改一下
                M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
                    'market_price' => $v['price'], //市场价
                    'goods_price' => $v['price'], // 本店价
                    'member_goods_price' => $v['price'], // 会员折扣价
                ));
            }
            if($keyArr){
                Db::name('spec_goods_price')->where('goods_id',$goods_id)->where('key','not in',$keyArr)->delete();
            }
//            M("SpecGoodsPrice")->insertAll($dataList);
            //记录库存修改日志
            $goods_stock = $this->where(array('goods_id'=>$goods_id))->getField('store_count');
            if($store_count != $goods_stock){
                $stock = $store_count - $goods_stock;
                update_stock_log($store_id, $stock,array('goods_id'=>$goods_id,'goods_name'=>$_POST['goods_name'],'store_id'=>$store_id));
            }
        }

        // 商品规格图片处理
        if($item_img)
        {
            M('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach ($item_img as $key => $val)
            {
                M('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val,'store_id'=>$store_id));
            }
        }
        refresh_stock($goods_id); // 刷新商品库存
    }
}
