<?php
return array(	
	'goods' =>array('name' => '商品', 'icon'=>'ico-goods', 'child' => array(
			array('name' => '商品发布', 'act'=>'addStepOne', 'op'=>'Goods'),
			//array('name' => '淘宝导入', 'act'=>'import', 'op'=>'index'), //临时屏蔽淘宝商品导入
			array('name' => '出售中的商品', 'act'=>'goodsList', 'op'=>'Goods'),
			array('name' => '仓库中的商品', 'act'=>'goods_offline', 'op'=>'Goods'),
			array('name' => '库存日志', 'act'=>'stock_list', 'op'=>'Goods'),
			array('name' => '商品规格', 'act' => 'specList', 'op'=>'Goods'),
			array('name' => '品牌申请', 'act'=>'brandList', 'op'=>'Goods'),
	)),
	'order'=>array('name' => '订单物流', 'icon'=>'ico-order', 'child' => array(
			array('name' => '订单列表', 'act'=>'index', 'op'=>'Order'),
			array('name' => '虚拟订单', 'act'=>'virtual_list', 'op'=>'Order'),
			array('name' => '发货商品', 'act'=>'delivery_list', 'op'=>'Order'),
			array('name' => '发货设置', 'act'=>'index', 'op'=>'Plugin'),
			array('name' => '商品评论','act'=>'index','op'=>'Comment'),
			
	)),
	'promotion' => array('name' => '促销', 'icon'=>'ico-promotion', 'child' => array(
			array('name' => '抢购管理', 'act'=>'flash_sale', 'op'=>'Promotion'),
			array('name' => '团购管理', 'act'=>'group_buy_list', 'op'=>'Promotion'),
			array('name' => '商品促销', 'act'=>'prom_goods_list', 'op'=>'Promotion'),
			array('name' => '订单促销', 'act'=>'prom_order_list', 'op'=>'Promotion'),
			array('name' => '代金券管理','act'=>'index', 'op'=>'Coupon'),
	)),
	'store' => array('name' => '店铺', 'icon'=>'ico-store', 'child' => array(
			array('name' => '店铺设置', 'act'=>'store_setting', 'op'=>'Store'),
			array('name' => '店铺装修', 'act'=>'store_decoration', 'op'=>'Store'),
			array('name' => '店铺导航', 'act'=>'navigation_list', 'op'=>'Store'),
			array('name' => '经营类目', 'act'=>'bind_class_list', 'op'=>'Store'),
			array('name' => '店铺信息', 'act'=>'store_info', 'op'=>'Store'),
			array('name' => '店铺分类', 'act'=>'goods_class', 'op'=>'Store'),
			array('name' => '供货商', 'act'=>'suppliers_list', 'op'=>'Store'),
			array('name' => '店铺关注', 'act'=>'store_collect', 'op'=>'Store'),
	)),
	'consult' => array('name' => '售后服务', 'icon'=>'ico-consult', 'child' => array(
			array('name' => '咨询管理', 'act'=>'ask_list', 'op'=>'Service'),
			array('name' => '退货换货', 'act'=>'refund_list', 'op'=>'Service'),
			array('name' => '投诉管理', 'act'=>'complain_list', 'op'=>'Service'),
	)),
	'statistics' => array('name' => '统计结算', 'icon'=>'ico-statistics', 'child' => array(
			array('name' => '提现申请', 'act'=>'withdrawals', 'op'=>'Finance'),
			array('name' => '汇款列表', 'act'=>'remittance', 'op'=>'Finance'),
			array('name' => '店铺结算记录', 'act'=>'order_statis', 'op'=>'Finance'),
			array('name' => '未结算订单', 'act'=>'order_no_statis', 'op'=>'Finance'),
			array('name' => '店铺概况', 'act'=>'index', 'op'=>'Report'),
			array('name' => '运营报告', 'act'=>'finance', 'op'=>'Report'),
			array('name' => '销售排行', 'act'=>'saleTop', 'op'=>'Report'),
			//array('name' => '流量统计', 'act'=>'visit', 'op'=>'Report'),
	)),
	'message' => array('name' => '客服消息', 'icon'=>'ico-message', 'child' => array(
			array('name' => '客服设置', 'act'=>'store_service', 'op'=>'Index'),
			array('name' => '系统消息', 'act'=>'store_msg', 'op'=>'Index'),
			//array('name' => '聊天记录查询', 'act'=>'store_im', 'op'=>'store'),
	)),
	'account' => array('name' => '账号', 'icon'=>'ico-account', 'child' => array(
			array('name' => '账号列表', 'act'=>'index', 'op'=>'Admin'),
			array('name' => '账号组', 'act'=>'role', 'op'=>'Admin'),
			array('name' => '账号日志', 'act'=>'log', 'op'=>'Admin'),
			//array('name' => '店铺消费', 'act'=>'store_cost', 'op'=>'cost_list'),
            //array('name' => '门店账号', 'act'=>'store_account', 'op'=>'index'),
	)),
	'service' => array('name' => '分销', 'icon'=>'ico-live', 'child' => array(
			array('name' => '分销商品', 'act'=>'goods_list', 'op'=>'Distribut'),
			array('name' => '分销设置', 'act'=>'distribut', 'op'=>'Distribut'),
			array('name' => '分成记录', 'act'=>'rebate_log', 'op'=>'Distribut'),
	)),
);