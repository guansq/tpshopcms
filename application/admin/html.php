<?php
return [
            'template'               => [
            // 模板引擎类型 支持 php think 支持扩展
            'type'         => 'Think',
            // 模板路径
            'view_path'    => './application/admin/view2/',
            // 模板后缀
            'view_suffix'  => 'html',
            // 模板文件名分隔符
            'view_depr'    => DS,
            // 模板引擎普通标签开始标记
            'tpl_begin'    => '{',
            // 模板引擎普通标签结束标记
            'tpl_end'      => '}',
            // 标签库标签开始标记
            'taglib_begin' => '<',
            // 标签库标签结束标记
            'taglib_end'   => '>',
            //模板文件名
            'default_theme'     => 'view2',
        ],
        'view_replace_str'  =>  [
            '__PUBLIC__'=>'/public',
            '__STATIC__' => '/application/admin/view2/static',
            '__ROOT__'=>''
        ],
        'DATA_BACKUP_PATH'	=> 'public/upload/sqldata/', //数据库备份根路径
        'DATA_BACKUP_PART_SIZE'	=> 20971520, //数据库备份卷大小
        'DATA_BACKUP_COMPRESS'	=> 0, //数据库备份文件是否启用压缩
        'DATA_BACKUP_COMPRESS_LEVEL' => 9 //数据库备份文件压缩级别
    ];
?>