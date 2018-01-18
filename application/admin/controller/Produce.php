<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/15
 * Time: 16:57
 */
namespace app\admin\controller;
use app\admin\logic\ProduceCatLogic;
use think\AjaxPage;
use think\Page;
use think\Db;

class Produce extends Base{

    /*
     * 产品列表
     */
    public function prList(){
        $produce = M('Produce');
        return $this->fetch('produceList');
    }

    public function produce(){
        $ProduceCat = new ProduceCatLogic();
        $act = I('GET.act','add');
        $info = array();
        if(I('GET.produce_id')){
            $produce_id = I('GET.produce_id');
            $info = D('produce')->where('produce_id='.$produce_id)->find();
        }
        $cats = $ProduceCat->produce_cat_list(0,$info['cat_id']);
        $this->assign('cat_select',$cats);
        $this->assign('act',$act);
        $this->assign('info',$info);
        return $this->fetch();
    }


    public function categoryList(){
        $ProduceCat = new ProduceCatLogic();
        $cat_list = $ProduceCat->produce_cat_list(0, 0, false);
        $this->assign('cat_list',$cat_list);
        return $this->fetch('categoryList');
    }

    public function category()
    {
        $ProduceCat = new ProduceCatLogic();
        $act = I('get.act', 'add');
        $cat_id = I('get.cat_id/d');
        $parent_id = I('get.parent_id/d');
        if ($cat_id) {
            $cat_info = D('article_cat')->where('cat_id=' . $cat_id)->find();
            $parent_id = $cat_info['parent_id'];
            $this->assign('cat_info', $cat_info);
        }
        $cats = $ProduceCat->produce_cat_list(0, $parent_id, true);
        $this->assign('act', $act);
        $this->assign('cat_select', $cats);
        return $this->fetch();
    }

    public function categoryHandle()
    {
        $data = I('post.');

        $result = $this->validate($data, 'ProduceCategory.'.$data['act'], [], true);
        if ($result !== true) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => $result]);
        }

        if ($data['act'] == 'add') {
            $r = D('produce_cat')->add($data);
        } elseif ($data['act'] == 'edit') {
            $r = D('produce_cat')->where("cat_id",$data['cat_id'])->save($data);
        } elseif ($data['act'] == 'del') {
            $r = D('produce_cat')->where('cat_id', $data['cat_id'])->delete();
        }

        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }


    public function produceHandle()
    {
        $data = I('post.');
        $data['publish_time'] = strtotime($data['publish_time']);
        //$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Article/articleList');

        $result = $this->validate($data, 'Article.'.$data['act'], [], true);
        if ($result !== true) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => $result]);
        }

        if ($data['act'] == 'add') {
            $data['click'] = mt_rand(1000,1300);
            $data['add_time'] = time();
            $r = D('article')->add($data);
        } elseif ($data['act'] == 'edit') {
            $r = D('article')->where('article_id='.$data['article_id'])->save($data);
        } elseif ($data['act'] == 'del') {
            $r = D('article')->where('article_id='.$data['article_id'])->delete();
        }

        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }

        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }
}