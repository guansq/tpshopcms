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
 *
 * Date: 2016-03-09
 */

namespace app\seller\controller;

use think\Db;
use think\Page;
use app\admin\logic\GoodsLogic;

class Distribut extends Base
{

    /*
     * 初始化操作
     */
    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 三级分销设置
     */
    public function distribut()
    {
        // 每个店铺有一个分销 记录
        $store_distribut = M('store_distribut')->where("store_id", STORE_ID)->find();
        if (IS_POST) {
            $Model = M('store_distribut');
            $data = input('post.');
            $data['store_id'] = STORE_ID;
            if ($store_distribut)
                $Model->save($data);
            else
                $Model->add($data);
            $this->success("操作成功", U('Store/distribut'));
            exit;
        }
        $distribut_set_by = M('config')->where("name", "distribut_set_by")->getField('value');
        $this->assign('distribut_set_by', $distribut_set_by);
        $this->assign('config', $store_distribut);
        return $this->fetch();
    }

    /**
     * 分成记录
     */
    public function rebate_log()
    {
        $log_model = Db::name("rebate_log");
        $status = I('status');
        $user_id = I('user_id/d');
        $order_sn = I('order_sn');
        $create_time = I('create_time');
        $create_time = $create_time ? $create_time : date('Y-m-d', strtotime('-1 year')) . ' - ' . date('Y-m-d', strtotime('+1 day'));

        $create_time2 = explode(' - ', $create_time);
        $this->assign('start_time', $create_time2[0]);
        $this->assign('end_time', $create_time2[1]);
        $log_where = array(
            'store_id' => STORE_ID,
            'create_time' => ['between', [strtotime($create_time2[0]), strtotime($create_time2[1])]]
        );
        if ($status === '0' || $status > 0) {
            $log_where['status'] = $status;
        }
        $user_id && $log_where['user_id'] = $user_id;
        $order_sn && $log_where['order_sn'] = ['like', '%' . $order_sn . '%'];

        $count = $log_model->where($log_where)->count();
        $Page = new Page($count, 16);
        $list = $log_model->where($log_where)->order("`id` desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();

        //nickname
        $get_user_id = get_arr_column($list, 'user_id'); // 获佣用户
        $buy_user_id = get_arr_column($list, 'user_id'); // 购买用户
        $user_id_arr = array_merge($get_user_id, $buy_user_id);
        if (!empty($user_id_arr))
            $user_arr = M('users')->where("user_id", "in", implode(',', $user_id_arr))->select();
        $this->assign('user_arr', $user_arr);

        $this->assign('create_time', $create_time);
        $show = $Page->show();
        $this->assign('show', $show);
        $this->assign('list', $list);
        C('TOKEN_ON', false);
        return $this->fetch();
    }

    public function goods_list()
    {
        $cat_id = I('cat_id/d');
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        $brand_id = I('brand_id/d');
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList', $categoryList);
        $this->assign('brandList', $brandList);
        $goods_where = array(
            'distribut' => ['gt', 0],
            'store_id' => STORE_ID
        );
        if ($cat_id > 0) {
            $goods_where['cat_id1|cat_id2|cat_id3'] = $cat_id;
        }
        if ($key_word) {
            $goods_where['goods_name|goods_sn'] = ['like', '%' . $key_word . '%'];
        }
        if ($brand_id) {
            $goods_where['brand_id'] = $brand_id;
        }
        $goods_model = Db::name('goods');
        $count = $goods_model->where($goods_where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $goodsList = $goods_model->where($goods_where)->order('sales_sum desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('goodsList', $goodsList);
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     * 修改编辑 分成
     */
    public function editRebate()
    {
        $id = I('id/d', 0);
        $log_model = Db::name('rebate_log');
        $rebate_log = $log_model->where(array('id' => $id, 'store_id' => STORE_ID))->find();
        if (empty($rebate_log)) {
            $this->error("参数错误!!!");
        }
        if (IS_POST) {
            $data = input('post.');
            // 如果是确定分成 将金额打入分佣用户余额
            if ($data['status'] == 3 && $rebate_log['status'] != 3) {
                accountLog($data['user_id'], $rebate_log['money'], 0, "订单:{$rebate_log['order_sn']}分佣", $rebate_log['money']);
            }
            Db::name('rebate_log')->update($data);
            $this->success("操作成功!!!", U('Distribut/rebate_log'));
            exit;
        }

        $user = M('users')->where("user_id", $rebate_log['user_id'])->find();
        if ($user['nickname']) {
            $rebate_log['user_name'] = $user['nickname'];
        } elseif ($user['email']) {
            $rebate_log['user_name'] = $user['email'];
        } elseif ($user['mobile']) {
            $rebate_log['user_name'] = $user['mobile'];
        }
        $this->assign('user', $user);
        $this->assign('rebate_log', $rebate_log);
        return $this->fetch();
    }


}