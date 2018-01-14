<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-23
 */

namespace app\admin\controller;
use app\admin\logic\WebLogic;
use think\Db;
use think\Page;

class Web extends Base{
	
	//首页楼层管理
	public function floorList(){
		$webObj = new WebLogic();
		$floor_list = $webObj->getWebList();
		$this->assign('floor_list',$floor_list);
		return $this->fetch();
	}
	
	public function settingFloor(){
		$web_id = I('web_id');
		if(IS_POST){
			$data = I('POST.');
			if($web_id > 0){
				M('web')->where(array('web_id'=>$data['web_id']))->save($data);
			}else{
				unset($data['web_id']);
				$data['update_time'] = time();
				$model_web_block = new WebLogic();
				$model_web_block->addWebBlock($data);
			}
			$this->success('保存成功',U('Web/floorList'));
		}
		if($web_id > 0){
			$web_config = M('web')->where(array('web_id'=>$web_id))->find();
			$this->assign('web_config',$web_config);
		}
		return $this->fetch();
	}
	
	public  function editFloor(){
		$web_id = I('web_id');
		$model_web_block = new WebLogic();
		$block_list = $model_web_block->getBlockList(array('web_id'=>$web_id));
		$parent_goods_class = M('goods_category')->where(array('level'=>array('lt',3)))->select();//商品分类父类列表，只取到第二级
		if (is_array($parent_goods_class) && !empty($parent_goods_class)){
			foreach ($parent_goods_class as $k => $v){
				$parent_goods_class[$k]['name'] = str_repeat("&nbsp;",$v['level']*2).$v['name'];
			}
		}
		$this->assign('parent_goods_class',$parent_goods_class);
		$goods_class = M('goods_category')->where(array('level'=>1))->select();
		$this->assign('goods_class',$goods_class);

		if(is_array($block_list) && !empty($block_list)) {
			foreach ($block_list as $key => $val) {//将变量输出到页面
				$val['block_info'] = $model_web_block->get_array($val["block_info"],$val["block_type"]);
				$this->assign('block_'.$val["var_name"],$val);
			}
			$style_array = $model_web_block->getStyleList();//样式数组
			$this->assign('style_array',$style_array);
			$web_list = $model_web_block->getWebList(array('web_id'=>$web_id));
			$this->assign('web_config',$web_list[0]);
		}else{
			//$model_web_block->addWebBlock();
		}
		return $this->fetch();
	}
	
	public function advSave(){
		$block_id = intval($_POST['block_id']);
		$web_id = intval($_POST['web_id']);
		$model_web_block = new WebLogic();
		$block = $model_web_block->getBlock($block_id,$web_id);
		$slide_id = intval($_POST['slide_id']);
		if (!empty($block)) {
			$adv_info = $_POST['adv'];
			$pic_info = $_POST['slide_pic'];
			if(isset($_POST['pic_id'])){
				$pic_id = $_POST['pic_id'];
				$pic_info['pic_id'] = $pic_id;
				if (!empty($adv_info[$pic_id]['pic_img'])) {//原图片
					$pic_info['pic_img'] = $adv_info[$pic_id]['pic_img'];
				}
			}else{
				if (!empty($adv_info[$slide_id]['pic_img'])) {//原图片
					$pic_info['pic_img'] = $adv_info[$slide_id]['pic_img'];
				}
			}
			$pic_name = $this->upload_img('pic');//上传图片
			if (!empty($pic_name)) {
				$pic_info['pic_img'] = $pic_name;
			}
			$this->assign('pic',$pic_info);
			$block_info = $model_web_block->get_array($block['block_info'],$block['block_type']);
			foreach ($block_info as $k=>$v){
				if($slide_id == $k){
					$block_info[$k]['adv_class'] = $_POST['adv_class'];
					$block_info[$k]['adv_type'] = 'upload_advmin';
					if(isset($pic_id)){
						$block_info[$k]['adv_type'] = 'upload_advbig';
						$block_info[$k]['adv_info'][$pic_id] = $pic_info;
					}else{
						$block_info[$k]['adv_type'] = 'upload_advmin';
						$block_info[$k]['adv_info'] = $pic_info;
					}
				}
			}
			$block_info = $model_web_block->format_block_info($block_info,$block['block_type']);
			$model_web_block->updateBlock($block_id,$block_info);
		}
		$script = '<script type="text/javascript">parent.update_adv('.$slide_id.',"'.$pic_info['pic_img'].'");</script>';
		if(isset($pic_id)){
			$script = '<script type="text/javascript">parent.slide_adv('.$pic_id.',"'.$pic_info['pic_img'].'");</script>';
		}
		exit($script);
	}
	
	public function uploadPic() {
		$block_id = intval($_POST['block_id']);
		$web_id = intval($_POST['web_id']);
		$model_web_block = new WebLogic();
		$data = I('POST.');
		$block = $model_web_block->getBlock($block_id,$web_id);
		if (!empty($block)) {
			$block_type = $block['block_type'];
			$var_name = $block['var_name'];
			$block_info = $_POST[$var_name];
			$pic_name = $this->upload_img('pic');//上传图片
			if (!empty($pic_name)) {
				$block_info['pic'] = $pic_name;
			}
			$block_info = $model_web_block->format_block_info($block_info,$block_type);
			$model_web_block->updateBlock($block_id,$block_info);
			$script = '<script type="text/javascript">parent.update_pic("'.$var_name.'","'.$pic_name.'");</script>';
			exit($script);
		}
	}
	
	public function blockUpdate() {
		$block_id = intval($_POST['block_id']);
		$web_id = intval($_POST['web_id']);
		$model_web_block = new WebLogic();
		$block = $model_web_block->getBlock($block_id,$web_id);
		if (!empty($block)) {
			$block_type = $block['block_type'];
			$var_name = $block['var_name'];
			$block_info = $_POST[$var_name];
			//halt($block_info);
			$block_info = $model_web_block->format_block_info($block_info,$block_type);
			$state = $model_web_block->updateBlock($block_id,$block_info);
		}
		if($state) {
			exit('1');
		} else {
			exit('0');
		}
	}
	
	public function getCateList(){
		$id = I('id/d');
		$child_list = M('goods_category')->where(array('parent_id'=>$id))->select();
		$this->assign('child_list',$child_list);
		$html = '';
		foreach($child_list as $v){
			$html .= '<li gc_id="'.$v['id'].'" gc_name="'.$v['name'].'" title="'.$v['name'].'" ondblclick="del_goods_class('.$v['id'].');">'; 
  			$html .= '<i onclick="del_goods_class('.$v['id'].');"></i>'.$v['name'].'    <input name="category_list[goods_class]['.$v['id'].'][gc_id]" value="'.$v['id'].'" type="hidden">';
    		$html .= '<input name="category_list[goods_class]['.$v['id'].'][gc_name]" value="'.$v['name'].'" type="hidden"></li>';
		}
		exit($html);
	}
	
	public function getBrandList(){
		$where = " status = 0 ";
		$name = I('brand_name');
		if(!empty($name)){
			$where .= " and name like '%".$_GET['brand_name']."%'";
		}
		$count = M('brand')->where($where)->count();
		$Page = new Page($count, 3);
		$brand_list = M('brand')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$show = $Page->show();
		$this->assign('show_page', $show);
		$this->assign('brand_list',$brand_list);
		echo $this->fetch();
	}
	
	/**
	 * 商品搜索
	 */
	public function getGoodsList()
	{
		$model_goods = M('goods');
		$where = "is_on_sale = 1 ";
		if (!empty($_GET['goods_name'])) {
			$where .= " and goods_name like '%".$_GET['goods_name']."%'";
		}
		if (!empty($_GET['id'])) {
			$id = $_GET['id'];
			$where .= " and cat_id1 = $id or cat_id2=$id or cat_id3=$id";
		}
		$count = $model_goods->where($where)->count();
		$Page = new Page($count, 6);
		$goods_list = $model_goods->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('goods_id desc')->select();
		$this->assign('goods_list', $goods_list);
		$show = $Page->show();
		$this->assign('show_page', $show);
		echo $this->fetch();
	}
	
	//首页焦点管理
	public function editFocus(){
		return $this->fetch();
	}
 
	//首页促销管理
	public function editPromotion(){
		return $this->fetch();
	}
	
	//频道管理
	public function channelList(){
		return $this->fetch();
	}
	
	//频道楼层管理
	public function channelFloorList(){
		return $this->fetch();
	}
	
	public function upload_img($file_name){
		$file = request()->file($file_name);
		if(empty($file)) return '';
		$validate = ['size'=>1024 * 1024 * 5,'ext'=>'jpg,png,gif,jpeg'];
		$dir = 'public/upload/adv/';
		if (!($_exists = file_exists($dir))) {
			mkdir($dir);
		}
		$parentDir = date('Ymd');
		$info = $file->validate($validate)->move($dir, true);
		if ($info) {
			return '/'.$dir.$parentDir.'/'.$info->getFilename();
		} else {
			return '';
		}	
	}
}