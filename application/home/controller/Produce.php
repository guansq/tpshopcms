<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 14:09
 */
namespace app\home\controller;
use app\admin\logic\ProduceCatLogic;
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
        if(!$cat_id){
            $sec_list = M('produce_cat')->where("parent_id = $cat_id")->select();
        }
        print_r($sec_list);
        return $this->fetch();
    }
    /*
     * 产品详情
     */
    public function produceDetail(){
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