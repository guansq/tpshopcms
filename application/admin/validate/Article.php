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
 */

namespace app\admin\validate;

use think\Validate;

/**
 * Description of Article
 *
 * @author Administrator
 */
class Article extends Validate
{
    //验证规则
    protected $rule = [
        'title'     => 'require|checkEmpty',
        'cat_id'    => 'require|checkEmpty|checkCatId',
        'content'   => 'require|checkContent',
        'article_id' => 'checkArticleId'
    ];
    
    //错误消息
    protected $message = [
        'title'    => '标题不能为空',
        'content'  => '内容不能为空',
        'cat_id.require'   => '所属分类缺少参数',
        'cat_id.checkEmpty' => '所属分类必须选择',
        'cat_id.checkCatId' => '不能在系统保留分类下添加文章',
        'article_id.checkArtcileId' => '系统预定义的文章不能删除'
    ];
    
    //验证场景
    protected $scene = [
        'add'  => ['title', 'cat_id', 'content'],
        'edit' => ['title', 'cat_id', 'content'],
        'del'  => ['article_id']
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
