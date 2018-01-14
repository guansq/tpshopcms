<?php
return array(
     'URL_HTML_SUFFIX'       =>  '',  // URL伪静态后缀设置
    // 'OUTPUT_ENCODE' =>  true, //页面压缩输出支持   配置了 没鸟用
    'PAYMENT_PLUGIN_PATH' =>  PLUGIN_PATH.'payment',
    'LOGIN_PLUGIN_PATH' =>  PLUGIN_PATH.'login',
    'SHIPPING_PLUGIN_PATH' => PLUGIN_PATH.'shipping',
    'FUNCTION_PLUGIN_PATH' => PLUGIN_PATH.'function',
	'SHOW_PAGE_TRACE' => false,
	'CFG_SQL_FILESIZE'=>5242880,
    //'URL_MODEL'=>1, // 
    //默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR' => 'public:dispatch_jump',
    //默认成功跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => 'public:dispatch_jump',
		
	'VIEW_PATH'       =>'./application/admin/', // 改变某个模块的模板文件目录
	'DEFAULT_THEME'   =>'View2', // 模板名称
	'DATA_BACKUP_PATH'	=> 'public/upload/sqldata/', //数据库备份根路径
	'DATA_BACKUP_PART_SIZE'	=> 20971520, //数据库备份卷大小
	'DATA_BACKUP_COMPRESS'	=> 0, //数据库备份文件是否启用压缩
	'DATA_BACKUP_COMPRESS_LEVEL' => 9 //数据库备份文件压缩级别
);