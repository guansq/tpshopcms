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
    public function index(){
        $cat_id = input('cat_id',0);
        $ProduceCat = new ProduceCatLogic();
        $cat_list = $ProduceCat->produce_cat_list(0, 0, false);
        $sec_list = [];
        //echo $cat_id;die;
        if(!empty($cat_id)){
            //echo $cat_id;die;
            $sec_list = $ProduceCat->produce_cat_list($cat_id, 0, false);
            //$sec_list = M('produce_cat')->where("parent_id = $cat_id")->select();
        }
        //print_r($sec_list);

        //得到当前分类下的所有商品
        $produce = [];
        if(!empty($cat_id)){
            //得到当前下的商品
            $cat = [];
            foreach($sec_list as $item){
                $cat[] = $item['cat_id'];
            }
            if(!empty($cat)){
                $catinfo = implode(',',$cat);
                $produce = Db::name("produce")->field('produce_id,produce_title,produce_cover')
                    ->where("cat_id in ($catinfo) and is_show = 1")->select();
            }
            //print_r($produce);
        }else{
            //得到全部商品
            $produce = Db::name("produce")->field('produce_id,produce_title,produce_cover')
                ->where("is_show = 1")->select();
        }
        $this->assign('cat_list',$cat_list);
        $this->assign('sec_list',$sec_list);
        $this->assign('produce',$produce);
        //return $this->fetch('index_test');
        return $this->fetch();
    }
    /*
     * 产品详情
     */
    public function produceDetail(){
        $produce_id = input('produce_id');

        return $this->fetch();
    }
    /*
     * 产品列表
     */
    public function produceList()
    {
        return $this->fetch();
    }


}