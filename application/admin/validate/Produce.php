<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18
 * Time: 16:34
 */
namespace app\admin\validate;

use think\Validate;
class Produce extends Validate
{
    //验证规则
    protected $rule = [
        'produce_title'     => 'require|checkEmpty',
        'cat_id'    => 'require|checkEmpty',
        'content'   => 'require|checkContent',
        'produce_desc' => 'require',
        'produce_cover' => 'require',
    ];

    //错误消息
    protected $message = [
        'produce_title'    => '产品标题不能为空',
        'content'  => '内容不能为空',
        'cat_id.require'   => '所属分类缺少参数',
        'cat_id.checkEmpty' => '所属分类必须选择',
        'produce_desc.require' => '产品描述不能为空',
        'produce_cover.require' => '产品封面图不能为空'
    ];

    //验证场景
    protected $scene = [
        'add'  => ['produce_title', 'cat_id', 'produce_content','produce_desc','produce_cover'],
        'edit' => ['produce_title', 'cat_id', 'produce_content','produce_desc','produce_cover'],
        'del'  => ['produce_id']
    ];

    protected function checkEmpty($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value)) {
            return false;
        }
        return true;
    }

    protected function checkContent($value)
    {
        $value = strip_tags($value);
        $value = str_replace('&nbsp;', '', $value);
        $value = trim($value);
        if (empty($value)) {
            return false;
        }
        return true;
    }

    protected function checkCatId($value)
    {
        $article_main_system_id = \app\admin\controller\Article::$article_main_system_id;
        if (array_key_exists($value, $article_main_system_id)) {
            return false;
        }
        return true;
    }

    protected function checkArticleId($value)
    {
        $article_able_id = \app\admin\controller\Article::$article_able_id;
        if (array_key_exists($value, $article_able_id)) {
            return false;
        }
        return true;
    }
}