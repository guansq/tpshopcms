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
 * Author: 当燃
 * Date: 2016-06-09
 */


namespace app\admin\logic;
use think\Model;
use think\Db;

class WebLogic extends Model
{    
    
    public function addWebBlock($data)
    {
		$web_id = M('web')->add($data);
		$block['web_id'] = $web_id;
		$block['block_type'] = 'array';
		
		$block['var_name'] = 'tit';
		$block['block_info'] = serialize(array('pic'=>'','url'=>'','type'=>'txt','floor'=>$web_id.'F','title'=>''));
		$block['show_name'] = '标题图片';
		M('web_block')->add($block);
		
		$block['var_name'] = 'category_list';
		$block['block_info'] = serialize(array('goods_class'=>array()));
		$block['show_name'] = '推荐分类';
		M('web_block')->add($block);
		
		$block['var_name'] = 'act';
		$block['block_info'] = serialize(array('pic'=>'','type'=>'pic','title'=>'','url'=>'','titlea'=>'','urla'=>'','titleb'=>'','urlb'=>''));
		$block['show_name'] = '活动图片';
		M('web_block')->add($block);
		
		$block['var_name'] = 'adv';
		if($data['style'] == 1){
			$adv = array(
				array('adv_type'=>'upload_advbig','adv_class'=>'left-c','adv_info'=>array(
					1=>array('pic_id'=>1,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
					2=>array('pic_id'=>2,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
					3=>array('pic_id'=>3,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
					4=>array('pic_id'=>4,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
					5=>array('pic_id'=>5,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
				)),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-d','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-d','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-d','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-e','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-f','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-f','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
				array('adv_type'=>'upload_advmin','adv_class'=>'left-f','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
			);
		}else{
			$adv = array(
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advbig','adv_class'=>'left-b','adv_info'=>array(
							1=>array('pic_id'=>1,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
							2=>array('pic_id'=>2,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
							3=>array('pic_id'=>3,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
							4=>array('pic_id'=>4,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
							5=>array('pic_id'=>5,'pic_name'=>'','pic_url'=>'','pic_img'=>''),
					)),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
					array('adv_type'=>'upload_advmin','adv_class'=>'left-a','adv_info'=>array('pic_name'=>'','pic_url'=>'','pic_img'=>'')),
			);
		}
		$block['block_info'] = serialize($adv);
		$block['show_name'] = '广告推荐';
		M('web_block')->add($block);
		
		$block['var_name'] = 'brand_list';
		$block['block_info'] = serialize(array());
		$block['show_name'] = '品牌推荐';
		M('web_block')->add($block);
		
		$block['var_name'] = 'recommend_list';
		$block['block_info'] = serialize(array());
		$block['show_name'] = '商品推荐';
		M('web_block')->add($block);
    }
    
    public function getWebList($condition = array('web_page' => 'index'),$page = '',$order = 'web_sort'){
    	$result = M('web')->where($condition)->order($order)->page($page)->select();
    	return $result;
    }
    
    /**
     * 读取模块内容记录列表
     * @param
     * @return array 数组格式的返回结果
     */
    public function getBlockList($condition = array()){
    	$result = M('web_block')->where($condition)->order('web_id')->select();
    	return $result;
    }
    
    /**
     * 主题样式名称
     *
     */
    public function getStyleList($style_id = 'index'){
    	$style_data = array(
    			'red' => '红色',
    			'pink' => '粉色',
    			'orange' => '橘色',
    			'green' => '绿色',
    			'blue' => '蓝色',
    			'purple' => '紫色',
    			'brown' => '褐色',
    			'default' => '默认',
    	);
    	$result['index'] = $style_data;
    	return $result[$style_id];
    }
    
    
    public function getBlock($block_id,$web_id){
    	return M('web_block')->where(array('block_id'=>$block_id,'web_id'=>$web_id))->find();
    }
    
    public function  updateBlock($block_id,$block_info){
    	return M('web_block')->where(array('block_id'=>$block_id))->save(array('block_info'=>$block_info));
    }
    
    /**
     * 转换字符串
     */
    public function get_array($block_info,$block_type){
    	$data = '';
    	switch ($block_type) {
    		case "array":
    			if(is_string($block_info)) $block_info = unserialize($block_info);
    			if(!is_array($block_info)) $block_info = array();
    			$data = $block_info;
    			break;
    		case "html":
    			if(!is_string($block_info)) $code_info = '';
    			$data = $code_info;
    			break;
    		default:
    			$data = '';
    			break;
    	}
    	return $data;
    }
    
    /**
     * 转换数组
     */
    public function format_block_info($block_info,$block_type){
    	$str = '';
    	switch ($block_type) {
    		case "array":
    			if(!is_array($block_info)) $block_info = array();
    			$block_info = $this->stripslashes_deep($block_info);
    			$str = serialize($block_info);
    			//$str = addslashes($str);
    			break;
    		case "html":
    			if(!is_string($block_info)) $block_info = '';
    			$str = $block_info;
    			break;
    		default:
    			$str = '';
    			break;
    	}
    	return $str;
    }
    
    /**
     * 递归去斜线
     */
    public function stripslashes_deep($value){
		$value = is_array($value) ? array_map(array($this,'stripslashes_deep'), $value) : stripslashes($value);
    	return $value;
    }
}