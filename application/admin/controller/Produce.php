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
    public function produceList(){
        $produce = M('Produce');
        $res = $list = array();
        $p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
        $size = empty($_REQUEST['size']) ? 20 : $_REQUEST['size'];
        $where = " 1 = 1 ";
        $keywords = trim(I('keywords'));
        $keywords && $where.=" and produce_title like '%$keywords%' ";
        $cat_id = I('cat_id',0);
        $cat_id && $where.=" and cat_id = $cat_id ";
        $res = $produce->where($where)->order('produce_id desc')->page("$p,$size")->select();
        $count = $produce->where($where)->count();// 查询满足要求的总记录数
        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数

        $ProduceCat = new ProduceCatLogic();
        $cats = $ProduceCat->produce_cat_list(0, 0, false);
        if($res){
            foreach ($res as $val){
                $val['category'] = $cats[$val['cat_id']]['name'];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }
        //print_r($cat_list);
        $this->assign('cats',$cats);
        $this->assign('cat_id',$cat_id);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch('produceList');
    }

    public function produce(){
        $ProduceCat = new ProduceCatLogic();
        $act = I('GET.act','add');
        $info = array();
        $img_info = array();
        $i = 0;
        if(I('GET.produce_id')){
            $produce_id = I('GET.produce_id');
            $info = D('produce')->where('produce_id='.$produce_id)->find();
            $img_info = D('produce_images')->where('produce_id='.$produce_id)->select();
            $i = count($img_info);//-1 > 0 ?  count($img_info)-1 : 0
            //echo $i;
        }
        //print_r($img_info);
        $cats = $ProduceCat->produce_cat_list(0,$info['cat_id']);
        $this->assign('i',$i);
        $this->assign('img_info',$img_info);
        $this->assign('cats',$cats);
        $this->assign('cat_select',$cats);
        $this->assign('act',$act);
        $this->assign('info',$info);
        $this->initEditor();
        return $this->fetch();
    }

    /**
     * 初始化编辑器链接
     * @param $post_id post_id
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'produce')));
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'produce')));
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'produce')));
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'produce')));
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'produce')));
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'produce')));
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'produce')));
        $this->assign("URL_Home", "");
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

        $result = $this->validate($data, 'Produce.'.$data['act'], [], true);
        if ($result !== true) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => $result]);
        }

        if ($data['act'] == 'add') {
            //$data['click'] = mt_rand(1000,1300);
            $data['add_time'] = time();
            $r = D('produce')->add($data);
            $id = D('produce')->getLastInsID();
            if(!empty($data['img_title'])){
                $temp =[];
                foreach($data['img_title'] as $key=>$val){
                    $temp['produce_id'] = $id;
                    $temp['img_url'] = $data['thumb'][$key];
                    $temp['img_sort'] = $key;
                    $temp['img_desc'] = $val;
                    D('produce_images')->add($temp);
                    //$temp['produce_id'] = $id;
                }
            }
        } elseif ($data['act'] == 'edit') {
            //print_r($data);
            $r = D('produce')->where('produce_id='.$data['produce_id'])->save($data);
            //print_r($r);die;
            if(!empty($data['img_title'])){
                $temp =[];
                foreach($data['img_title'] as $key=>$val){
                    $temp['produce_id'] = $data['produce_id'];
                    $temp['img_url'] = $data['thumb'][$key];
                    $temp['img_sort'] = $key;
                    $temp['img_desc'] = $val;
                    if(isset($data['img_id'][$key])){
                        D('produce_images')->where('produce_id', $data['img_id'][$key])->update($temp);
                    }else{
                        D('produce_images')->add($temp);
                    }
                    //$temp['produce_id'] = $id;
                }
            }
        } elseif ($data['act'] == 'del') {
            $r = D('produce')->where('produce_id='.$data['produce_id'])->delete();
        }

        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }

        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }
}