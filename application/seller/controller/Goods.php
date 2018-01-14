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
namespace app\seller\controller;

use app\seller\logic\GoodsCategoryLogic;
use app\seller\model\ShippingArea;
use think\Db;
use think\Page;
use think\AjaxPage;
use app\seller\logic\GoodsLogic;
use think\Loader;

class Goods extends Base
{

    /**
     * 删除分类
     */
    public function delGoodsCategory()
    {
        // 判断子分类
        $GoodsCategory = M("GoodsCategory");
        $count = $GoodsCategory->where("parent_id", $_GET['id'])->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!', U('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id", $_GET['id'])->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!', U('Admin/Goods/categoryList'));
        // 删除分类
        $GoodsCategory->where("id", $_GET['id'])->delete();
        $this->success("操作成功!!!", U('Admin/Goods/categoryList'));
    }

    /**
     *  商品列表
     */
    public function goodsList()
    {
        checkIsBack();
        $store_goods_class_list = M('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select();
        $this->assign('store_goods_class_list', $store_goods_class_list);
        $suppliers_list = M('suppliers')->where(array('store_id'=>STORE_ID))->select();
        $this->assign('suppliers_list', $suppliers_list);
        return $this->fetch('goodsList');
    }

    /**
     *  商品列表
     */
    public function ajaxGoodsList()
    {
        $where['store_id'] = STORE_ID;
        $intro = I('intro', 0);
        $store_cat_id1 = I('store_cat_id1', '');
        $key_word = trim(I('key_word', ''));
        $orderby1 = I('post.orderby1', '');
        $orderby2 = I('post.orderby2', '');
        $suppliers_id = input('suppliers_id','');
        if($suppliers_id !== ''){
            $where['suppliers_id'] = $suppliers_id;
        }
        if (!empty($intro)) {
            $where[$intro] = 1;
        }
        if ($store_cat_id1 !== '') {
            $where['store_cat_id1'] = $store_cat_id1;
        }
        $where['is_on_sale'] = 1;
        $where['goods_state'] = 1;
        if ($key_word !== '') {
            $where['goods_name|goods_sn'] = array('like', '%' . $key_word . '%');
        }
        $order_str = array();
        if ($orderby1 !== '') {
            $order_str[$orderby1] = $orderby2;
        }
        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page = new AjaxPage($count, 10);

        //是否从缓存中获取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            //重置获取条件
            delIsBack();
        }
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        cachePage($Page);
        $show = $Page->show();

        $catList =  M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');
        $store_warning_storage = M('store')->where('store_id', STORE_ID)->getField('store_warning_storage');
        $this->assign('store_warning_storage', $store_warning_storage);
        $this->assign('catList', $catList);
        $this->assign('goodsList', $goodsList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }
    
    public function goods_offline(){
    	$where['store_id'] = STORE_ID;
    	$model = M('Goods');
        $suppliers_id = input('suppliers_id');
        if($suppliers_id){
            $where['suppliers_id'] = $suppliers_id;
        }
    	if(I('is_on_sale') == 2){
    		$where['is_on_sale'] = 2;
    	}else{
  			$where['is_on_sale'] = 0;
    	}
    	$goods_state = I('goods_state', '', 'string'); // 商品状态  0待审核 1审核通过 2审核失败
    	if($goods_state != ''){
    		$where['goods_state'] = intval($goods_state);
    	}
    	$store_cat_id1 = I('store_cat_id1', '');
    	if ($store_cat_id1 !== '') {
    		$where['store_cat_id1'] = $store_cat_id1;
    	}
    	$key_word = trim(I('key_word', ''));
    	if ($key_word !== '') {
    		$where['goods_name|goods_sn'] = array('like', '%' . $key_word . '%');
    	}
    	$count = $model->where($where)->count();
    	$Page = new Page($count, 10);
    	$goodsList = $model->where($where)->order('goods_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	$show = $Page->show();
    	$store_goods_class_list = M('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select();
    	$this->assign('store_goods_class_list', $store_goods_class_list);
    	$suppliers_list = M('suppliers')->where(array('store_id'=>STORE_ID))->select();
    	$this->assign('suppliers_list', $suppliers_list);
		$this->assign('state',C('goods_state'));
    	$this->assign('goodsList', $goodsList);
    	$this->assign('page', $show);// 赋值分页输出
    	return $this->fetch();
    } 

    public function stock_list()
    {
        $model = M('stock_log');
        $map['store_id'] = STORE_ID;
        $mtype = I('mtype');
        if ($mtype == 1) {
            $map['stock'] = array('gt', 0);
        }
        if ($mtype == -1) {
            $map['stock'] = array('lt', 0);
        }
        $goods_name = I('goods_name');
        if ($goods_name) {
            $map['goods_name'] = array('like', "%$goods_name%");
        }
        $ctime = I('ctime');
        if ($ctime) {
            $gap = explode(' - ', $ctime);
            $this->assign('ctime', $gap[0] . ' - ' . $gap[1]);
            $this->assign('start_time', $gap[0]);
            $this->assign('end_time', $gap[1]);
            $map['ctime'] = array(array('gt', strtotime($gap[0])), array('lt', strtotime($gap[1])));
        }
        $count = $model->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $this->assign('page', $show);// 赋值分页输出
        $stock_list = $model->where($map)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('stock_list', $stock_list);
        return $this->fetch();
    }

    /**
     *
     */
    public function addStepOne(){
        //限制发布商品数量，0为不限制
        $alreadyPushNum = Db::name('goods')->where(['store_id' => STORE_ID])->count();
        $sgGoodsLimit = Db::name('store_grade')->where(['sg_id' => $this->storeInfo['grade_id']])->getField('sg_goods_limit');
        if($alreadyPushNum >= $sgGoodsLimit && $sgGoodsLimit > 0){
            $this->error("可发布商品数量已达到上限", U('Goods/goodsList'));
        }
        $goods_id = input('goods_id');
        if($goods_id){
            $goods = Db::name('goods')->where('goods_id',$goods_id)->find();
            $this->assign('goods',$goods);
        }
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('goodsCategoryLevelOne',$goodsCategoryLevelOne);
        return $this->fetch();
    }

    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {
        $goods_id = I('goods_id/d', 0);
        $goods_cat_id3 = I('cat_id3/d', 0);
        if(empty($goods_id)){
            if(empty($goods_cat_id3)){
                $this->error("您选择的分类不存在，或没有选择到最后一级，请重新选择分类。", U('Goods/addStepOne'));
            }
            $goods_cat[0] = Db::name('goods_category')->where('id', $goods_cat_id3)->find();
            $goods_cat[1] = Db::name('goods_category')->where('id', $goods_cat[0]['parent_id'])->find();
            $goods_cat[2] = Db::name('goods_category')->where('id', $goods_cat[1]['parent_id'])->find();
        }else{
            $Goods = new \app\seller\model\Goods();
            $goods = $Goods->where(['goods_id' => $goods_id, 'store_id' => STORE_ID])->find();
            if(empty($goods)){
                $this->error("非法操作", U('Goods/goodsList'));
            }else{
                $this->assign('goodsInfo', $goods);  // 商品详情
            }
            $goods_cat = Db::name('goods_category')->where('id','IN',[$goods['cat_id1'],$goods['cat_id2'],$goods['cat_id3']])->order('level desc')->select();
        }
        $GoodsLogic = new GoodsLogic();
        $ShippingArea = new ShippingArea();
        $store_goods_class_list = Db::name('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select(); //店铺内部分类
        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = Db::name("GoodsType")->select();
        $suppliersList = Db::name("suppliers")->select();
        $plugin_shipping = Db::name('plugin')->where(['type' => ['eq', 'shipping'],'status'=>1])->select();//插件物流
        $shipping_area = $ShippingArea->getShippingArea(STORE_ID);//配送区域
        $goodsImages = Db::name("GoodsImages")->where('goods_id', $goods_id)->select();
        $this->assign('goods_cat', $goods_cat);
        $this->assign('store_id', STORE_ID);
        $this->assign('shipping_area', $shipping_area);
        $this->assign('plugin_shipping', $plugin_shipping);
        $this->assign('store_goods_class_list', $store_goods_class_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('suppliersList', $suppliersList);
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        return $this->fetch('_goods');
    }

    /**
     * 商品保存
     */
    public function save(){
        // 数据验证
        $data =input('post.');
        $shipping_area_ids = I('post.shipping_area_ids/a', []);
        $goods_id = input('post.goods_id');
        $goods_cat_id3 = input('post.cat_id3');
        $spec_goods_item = input('post.item/a',[]);
        $store_count = input('post.store_count');
        $virtual_indate = I('post.virtual_indate');//虚拟商品有效期
        $validate = Loader::validate('Goods');
        $data['store_id'] = STORE_ID;
        if (!$validate->batch()->check($data)) {
            $error = $validate->getError();
            $error_msg = array_values($error);
            $return_arr = array(
                'status' => -1,
                'msg' => $error_msg[0],
                'data' => $error,
            );
            $this->ajaxReturn($return_arr);
        }
        $data['on_time'] = time(); // 上架时间
        $data['shipping_area_ids'] = implode(',', $shipping_area_ids);
        $data['shipping_area_ids'] = $data['shipping_area_ids'] ? $data['shipping_area_ids'] : '';

        $type_id = M('goods_category')->where("id", $goods_cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        $stores = M('store')->where(array('store_id' => STORE_ID))->getField('store_id , goods_examine,is_own_shop' , 1);
        $store_goods_examine = $stores[STORE_ID]['goods_examine'];
        if ($store_goods_examine) {
            $data['goods_state'] = 0; // 待审核
            $data['is_on_sale'] = 0; // 下架
        } else {
            $data['goods_state'] = 1; // 出售中
        }
        //总平台自营标识为2 , 第三方自营店标识为1
        $is_own_shop = (STORE_ID == 1) ? 2 : ($stores[STORE_ID]['is_own_shop']);
        $data['is_own_shop'] = $is_own_shop;
        $data['goods_type'] = $type_id ? $type_id : 0;
        $data['virtual_indate'] = !empty($virtual_indate) ? strtotime($virtual_indate) : 0;
        if ($goods_id > 0) {
            $Goods = \app\seller\model\Goods::get(['goods_id' => $goods_id, 'store_id' => STORE_ID]);
            if(empty($Goods)){
                $this->ajaxReturn(array('status' => 0, 'msg' => '非法操作','result'=>''));
            }
            if (empty($spec_goods_item) && $store_count != $Goods['store_count']) {
                $real_store_count = $store_count - $Goods['store_count'];
                update_stock_log(session('admin_id'), $real_store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            } else {
                unset($data['store_count']);
            }
            $Goods->data($data, true); // 收集数据
            $update = $Goods->save(); // 写入数据到数据库
            // 更新成功后删除缩略图
            if($update !== false){
                // 修改商品后购物车的商品价格也修改一下
                Db::name('cart')->where("goods_id", $goods_id)->where("spec_key = ''")->save(array(
                    'market_price' => $Goods['market_price'], //市场价
                    'goods_price' => $Goods['shop_price'], // 本店价
                    'member_goods_price' => $Goods['shop_price'], // 会员折扣价
                ));
                delFile("./public/upload/goods/thumb/$goods_id", true);
            }
        } else {
            $Goods = new \app\seller\model\Goods();
            $Goods->data($data, true); // 收集数据
            $Goods->save(); // 新增数据到数据库
            $goods_id = $Goods->getLastInsID();
            //商品进出库记录日志
            if (empty($spec_goods_item)) {
                update_stock_log(session('admin_id'), $store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            }
        }
        $Goods->afterSave($goods_id, STORE_ID);
        $GoodsLogic = new GoodsLogic();
        $GoodsLogic->saveGoodsAttr($goods_id, $type_id); // 处理商品 属性
        $this->ajaxReturn([ 'status' => 1, 'msg' => '操作成功', 'result' => ['goods_id'=>$Goods->goods_id]]);
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField()
    {
        $primary = array(
            'goods' => 'goods_id',
            'goods_attribute' => 'attr_id',
            'ad' => 'ad_id',
        );
        $id = I('id/d', 0);
        $field = I('field');
        $value = I('value');
        Db::name($_POST['table'])->where($primary[$_POST['table']], $id)->where('store_id', STORE_ID)->save(array($field => $value));
        $return_arr = array(
            'status' => 1,
            'msg' => '操作成功',
            'data' => array('url' => U('Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput()
    {
        $cat_id3 = I('cat_id3/d', 0);
        $goods_id = I('goods_id/d', 0);
        empty($cat_id3) && exit('');
        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($goods_id, $type_id);
        exit($str);
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        $goods_id = I('id/d');
        $error = '';
        
        // 判断此商品是否有订单
        $c1 = M('OrderGoods')->where("goods_id", $goods_id)->count('1');
        $c1 && $error .= '此商品有订单,不得删除! <br/>';

        // 商品团购
        $c1 = M('group_buy')->where("goods_id", goods_id)->count('1');
        $c1 && $error .= '此商品有团购,不得删除! <br/>';

        // 商品退货记录
        $c1 = M('return_goods')->where("goods_id", $goods_id)->count('1');
        $c1 && $error .= '此商品有退货记录,不得删除! <br/>';

        if ($error) {
            $return_arr = array('status' => -1, 'msg' => $error, 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            $this->ajaxReturn($return_arr);
        }

        // 删除此商品        
        $result = M("Goods")->where(['goods_id' => $goods_id, 'store_id' => STORE_ID])->delete();  //商品表
        if ($result) {
            M("cart")->where('goods_id', $goods_id)->delete();  // 购物车
            M("comment")->where('goods_id', $goods_id)->delete();  //商品评论
            M("goods_consult")->where('goods_id', $goods_id)->delete();  //商品咨询
            M("goods_images")->where('goods_id', $goods_id)->delete();  //商品相册
            M("spec_goods_price")->where('goods_id', $goods_id)->delete();  //商品规格
            M("spec_image")->where('goods_id', $goods_id)->delete();  //商品规格图片
            M("goods_attr")->where('goods_id', $goods_id)->delete();  //商品属性
            M("goods_collect")->where('goods_id', $goods_id)->delete();  //商品收藏
        }
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn($return_arr);
    }

    /**
     * ajax 获取 品牌列表
     */
    public function getBrandByCat()
    {
        $db_prefix = C('database.prefix');
        $type_id = I('type_id/d');
        if ($type_id) {
//            $list = M('brand')->join("left join {$db_prefix}brand_type on {$db_prefix}brand.id = {$db_prefix}brand_type.brand_id and  type_id = $type_id")->order('id')->select();
            $list = Db::name('brand')->alias('b')->join('__BRAND_TYPE__ t', 't.brand_id = b.id', 'LEFT')->where(['t.type_id' => $type_id])->order('b.id')->select();
        } else {
            $list = M('brand')->order('id')->select();
        }
//        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}brand) ")->getField("id,name,parent_id");
        $goods_category_list = Db::name('goods_category')
            ->where('id', 'IN', function ($query) {
                $query->name('brand')->where('')->field('cat_id1');
            })
            ->getField("id,name,parent_id");
        $goods_category_list[0] = array('id' => 0, 'name' => '默认');
        asort($goods_category_list);
        $this->assign('goods_category_list', $goods_category_list);
        $this->assign('type_id', $type_id);
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * ajax 获取 规格列表
     */
    public function getSpecByCat()
    {

        $db_prefix = C('database.prefix');
        $type_id = I('type_id/d');
        if ($type_id) {
//            $list = M('spec')->join("left join {$db_prefix}spec_type on {$db_prefix}spec.id = {$db_prefix}spec_type.spec_id  and  type_id = $type_id")->order('id')->select();
            $list = Db::name('spec')->alias('s')->join('__SPEC_TYPE__ t', 't.spec_id = s.id', 'LEFT')->where(['t.type_id' => $type_id])->order('s.id')->select();
        } else {
            $list = M('spec')->order('id')->select();
        }
//        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}spec) ")->getField("id,name,parent_id");
        $goods_category_list = Db::name('goods_category')
            ->where('id', 'IN', function ($query) {
                $query->name('spec')->where('')->field('cat_id1');
            })
            ->getField("id,name,parent_id");
        $goods_category_list[0] = array('id' => 0, 'name' => '默认');
        asort($goods_category_list);
        $this->assign('goods_category_list', $goods_category_list);
        $this->assign('type_id', $type_id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = I('goods_id/d', 0);
        $cat_id3 = I('cat_id3/d', 0);
        empty($cat_id3) && exit('');
        $goods_id = $goods_id ? $goods_id : 0;

        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $spec_id_arr = M('spec_type')->where("type_id", $type_id)->getField('spec_id', true); // 找出这个类型的 所有 规格id
        empty($spec_id_arr) && exit('');

        $specList = D('Spec')->where("id", "in", implode(',', $spec_id_arr))->order('`order` desc')->select(); // 找出这个类型的所有规格
        if ($specList) {
            foreach ($specList as $k => $v) {
                $specList[$k]['spec_item'] = D('SpecItem')->where(['store_id' => STORE_ID, 'spec_id' => $v['id']])->getField('id,item'); // 获取规格项
            }
        }

        $items_id = M('SpecGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片                
        if ($goods_id) {
            $specImageList = M('SpecImage')->where("goods_id", $goods_id)->getField('spec_image_id,src');
        }
        $this->assign('specImageList', $specImageList);

        $this->assign('items_ids', $items_ids);
        $this->assign('specList', $specList);
        return $this->fetch('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {
        $GoodsLogic = new GoodsLogic();
        $goods_id = I('get.goods_id/d', 0);
        $spec_arr = I('spec_arr/a', []);
        $str = $GoodsLogic->getSpecInput($goods_id, $spec_arr, STORE_ID);
        $this->ajaxReturn(['status'=>1,'msg'=>'','result'=>$str]);
    }

    /**
     * 商家发布商品时添加的规格
     */
    public function addSpecItem()
    {
        $spec_id = I('spec_id/d', 0); // 规格id
        $spec_item = I('spec_item', '', 'trim');// 规格项

        $c = M('spec_item')->where(['store_id' => STORE_ID, 'item' => $spec_item, 'spec_id' => $spec_id])->count();
        if ($c > 0) {
            $return_arr = array(
                'status' => -1,
                'msg' => '规格已经存在',
                'data' => '',
            );
            exit(json_encode($return_arr));
        }
        $data = array(
            'spec_id' => $spec_id,
            'item' => $spec_item,
            'store_id' => STORE_ID,
        );
        M('spec_item')->add($data);

        $return_arr = array(
            'status' => 1,
            'msg' => '添加成功!',
            'data' => '',
        );
        exit(json_encode($return_arr));
    }

    /**
     * 商家发布商品时删除的规格
     */
    public function delSpecItem()
    {
        $spec_id = I('spec_id/d', 0); // 规格id
        $spec_item = I('spec_item', '', 'trim');// 规格项
        $spec_item_id = I('spec_item_id/d', 0); //规格项 id

        if (!empty($spec_item_id)) {
            $id = $spec_item_id;
        } else {
            $id = M('spec_item')->where(['store_id' => STORE_ID, 'item' => $spec_item, 'spec_id' => $spec_id])->getField('id');
        }

        if (empty($id)) {
            $return_arr = array('status' => -1, 'msg' => '规格不存在');
            exit(json_encode($return_arr));
        }
        $c = M("SpecGoodsPrice")->where("store_id", STORE_ID)->where(" `key` REGEXP :id1 OR `key` REGEXP :id2 OR `key` REGEXP :id3 or `key` = :id4")->bind(['id1' => '^' . $id . '_', 'id2' => '_' . $id . '_', 'id3' => '_' . $id . '$', 'id4' => $id])->count(); // 其他商品用到这个规格不得删除
        if ($c) {
            $return_arr = array('status' => -1, 'msg' => '此规格其他商品使用中,不得删除');
            exit(json_encode($return_arr));
        }
        M('spec_item')->where(['id' => $id, 'store_id' => STORE_ID])->delete(); // 删除规格项
        M('spec_image')->where(['spec_image_id' => $id, 'store_id' => STORE_ID])->delete(); // 删除规格图片选项
        $return_arr = array('status' => 1, 'msg' => '删除成功!');
        exit(json_encode($return_arr));
    }

    /**
     * 商品规格列表
     */
    public function specList()
    {
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name,parent_id'); // 已经改成联动菜单                
        $this->assign('cat_list', $cat_list);
        return $this->fetch();
    }

    /**
     *  商品规格列表
     */
    public function ajaxSpecList()
    {
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $cat_id3 = I('cat_id3/d', 0);
        $spec_id = I('spec_id/d', 0);
        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 获取这个分类对应的类型
        if (empty($cat_id3) || empty($type_id)) exit('');

        $spec_id_arr = M('spec_type')->where("type_id", $type_id)->getField('spec_id', true); // 获取这个类型所拥有的规格
        if (empty($spec_id_arr)) exit('');

        $spec_id = $spec_id ? $spec_id : $spec_id_arr[0]; //没有传值则使用第一个

        $specList = M('spec')->where("id", "in", implode(',', $spec_id_arr))->getField('id,name,cat_id1,cat_id2,cat_id3');
        $specItemList = M('spec_item')->where(['store_id' => STORE_ID, 'spec_id' => $spec_id])->order('id')->select(); // 获取这个类型所拥有的规格
        //I('cat_id1')   && $where = "$where and cat_id1 = ".I('cat_id1') ;                       
        $this->assign('spec_id', $spec_id);
        $this->assign('specList', $specList);
        $this->assign('specItemList', $specItemList);
        return $this->fetch();
    }

    /**
     *  批量添加修改规格
     */
    public function batchAddSpecItem()
    {
        $spec_id = I('spec_id/d', 0);
        $item = I('item/a');
        $spec_item = M('spec_item')->where(['store_id' => STORE_ID, 'spec_id' => $spec_id])->getField('id,item');
        foreach ($item as $k => $v) {
            $v = trim($v);
            if (empty($v)) continue; // 值不存在 则跳过不处理
            // 如果spec_id 存在 并且 值不相等 说明值被改动过
            if (array_key_exists($k, $spec_item) && $v != $spec_item[$k]) {
                M('spec_item')->where(['id' => $k, 'store_id' => STORE_ID])->save(array('item' => $v));
                // 如果这个key不存在 并且规格项也不存在 说明 需要插入
            } elseif (!array_key_exists($k, $spec_item) && !in_array($v, $spec_item)) {
                M('spec_item')->add(array('spec_id' => $spec_id, 'item' => $v, 'store_id' => STORE_ID));
            }
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'保存成功','result'=>'']);
    }

    /**
     * 品牌列表
     */
    public function brandList()
    {
        $keyword = I('keyword');
        $brand_model = Db::name('brand');
        $brand_where['store_id'] = STORE_ID;
        if ($keyword) {
            $brand_where['name'] = ['like', '%' . $keyword . '%'];
        }
        $count = $brand_model->where($brand_where)->count();
        $Page = new Page($count, 16);
        $brandList = $brand_model->where($brand_where)->order("`sort` asc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list', $cat_list);
        $this->assign('show', $show);
        $this->assign('brandList', $brandList);
        return $this->fetch('brandList');
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public function addEditBrand()
    {
        $id = I('id/d', 0);
        if (IS_POST) {
            $data = input('post.');
            if ($id) {
                Db::name('brand')->update($data);
            } else {
                $data['store_id'] = STORE_ID;
                M("Brand")->insert($data);
            }

            $this->success("操作成功!!!", U('Seller/Goods/brandList', array('p' => input('p'))));
            exit;
        }
        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $this->assign('cat_list', $cat_list);
        $brand = Db::name('brand')->where(array('id' => $id, 'store_id' => STORE_ID))->find();
        $this->assign('brand', $brand);
        return $this->fetch('_brand');
    }

    /**
     * 删除品牌
     */
    public function delBrand()
    {
        $model = M("Brand");
        $id = I('id/d');
        $model->where(['id' => $id, 'store_id' => STORE_ID])->delete();
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn($return_arr);
    }

    public function brand_save()
    {
        $data = I('post.');
        if ($data['act'] == 'del') {
            $goods_count = M('Goods')->where("brand_id", $data['id'])->count('1');
            if ($goods_count) respose(array('status' => -1, 'msg' => '此品牌有商品在用不得删除!'));
            $r = M('brand')->where('id', $data['id'])->delete();
            if ($r) {
                respose(array('status' => 1));
            } else {
                respose(array('status' => -1, 'msg' => '操作失败'));
            }
        } else {
            if (empty($data['id'])) {
                $data['store_id'] = STORE_ID;
                $r = M('brand')->add($data);
            } else {
                $r = M('brand')->where('id', $data['id'])->save($data);
            }
        }
        if ($r) {
            $this->success("操作成功", U('Store/brand_list'));
        } else {
            $this->error("操作失败", U('Store/brand_list'));
        }
    }

    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename', '');
        $goods_images = M('goods_images')->where(array('image_url' => $path))->select();
        foreach ($goods_images as $key => $val) {
            $goods = M('goods')->where(array('goods_id' => $goods_images[$key]['goods_id']))->find();
            if ($goods['store_id'] == STORE_ID) {
                M('goods_images')->where(array('img_id' => $goods_images[$key]['img_id']))->delete();
            }
        }
    }

    /**
     * 重新申请商品审核
     */
    public function goodsUpLine()
    {
        $goods_ids = input('goods_ids');
        $res = Db::name('goods')->where('goods_id', 'in', $goods_ids)->where('store_id', STORE_ID)->update(['is_on_sale' => 0, 'goods_state' => 0]);
        if($res !== false){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }

    }
}