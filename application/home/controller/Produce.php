<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24
 * Time: 14:09
 */
namespace app\home\controller;

class Produce extends Base
{
    /*
     * 首页
     */
    public function index(){
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
     * 产品详情
     */
    public function produceDetail(){
        return $this->fetch();
    }
}