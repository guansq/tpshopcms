<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 14:09
 */

namespace app\home\controller;

use app\admin\logic\ProduceCatLogic;
use think\Db;

class Produce extends Base
{
    /*
     * 首页
     */
    public function index()
    {
        $cat_id     = input('cat_id', 0);
        $ProduceCat = new ProduceCatLogic();
        //$cat_list   = $ProduceCat->produce_cat_list(0, 0, false);
        $cat_list = $this->getNavList();
        $sec_list = [];
        //echo $cat_id;die;
        if (!empty($cat_id)) {
            //echo $cat_id;die;
            $sec_list = $ProduceCat->produce_cat_list($cat_id, 0, false);
            //$sec_list = M('produce_cat')->where("parent_id = $cat_id")->select();
        }
        //print_r($sec_list);

        //得到当前分类下的所有商品
        $produce = [];
        if (!empty($cat_id)) {
            //得到当前下的商品
            $cat = [];
            foreach ($sec_list as $item) {
                $cat[] = $item['cat_id'];
            }
            if (!empty($cat)) {
                $catinfo = implode(',', $cat);
                $produce = Db::name("produce")->field('produce_id,produce_title,produce_cover')->where("cat_id in ($catinfo) and is_show = 1")->select();
            }

        } else {
            //得到全部商品
            $produce = Db::name("produce")->field('produce_id,produce_title,produce_cover')->where("is_show = 1")->select();
        }
        //print_r($cat_list);
        //die;
        $this->assign('cat_list', $cat_list);
        $this->assign('sec_list', $sec_list);
        $this->assign('produce', $produce);
        $this->assign('cat_id', $cat_id);
        //var_dump($cat_list);
        //die;

        //return $this->fetch('index_test');
        return $this->fetch();
    }

    /*
     * 产品详情
     */
    public function produceDetail()
    {
        $ProduceCat = new ProduceCatLogic();
        $cat_list   = $ProduceCat->produce_cat_list(0, 0, false);

        $produce_id = input('produce_id');
        //echo $produce_id;
        $produce_info = Db::name('produce')->where("produce_id = $produce_id")->find();
        $cur_id       = $cat_id = $produce_info['cat_id'];
        $sec_list     = [];
        //echo $cat_id;die;
        if (!empty($cat_id)) {
            //echo $cat_id;die;
            $sec_list = $ProduceCat->produce_cat_list($cat_id, 0, false);
            //$sec_list = M('produce_cat')->where("parent_id = $cat_id")->select();
        }

        $parent = [];
        while ($cat_id != 0) {
            $info     = DB::name('produce_cat')->where("cat_id = $cat_id")->find();
            $parent[] = $info;
            $cat_id   = $info['parent_id'];
            //echo $cat_id.'<br>';
        }
        $produce_info['content'] = htmlspecialchars_decode($produce_info['content']);
        $cat_info                = Db::name('produce_cat')->where("cat_id = $cur_id")->find();
        $produce_img             = [];
        $produce_img             = Db::name('produce_images')->where("produce_id = $produce_id")->select();
        $this->assign('cat_info', $cat_info);
        $this->assign('cat_list', $cat_list);
        $this->assign('sec_list', $sec_list);
        $this->assign('produce_img', json_encode($produce_img));
        $this->assign('produce_info', $produce_info);
        $this->assign('parent', $parent);
        $this->assign('cat_id', $cur_id);
        //print_r($produce_img);die;
        //Db::name('produce_')
        return $this->fetch();
    }

    /*
     * 产品列表
     */
    public function produceList()
    {
        return $this->fetch();
    }

    /*
     * 一级列表
     */
    private function getNavList()
    {
        $list = Db::name('produce_cat')->where('parent_id', 0)->order('cat_id', 'asc')->select();
        foreach ($list as &$val) {
            $val['level'] = 0;
        }

        return $list;
    }
}