<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace app\home\controller;

use think\Db;
class Article extends Base
{

    public function index()
    {
        $years = array();
        $currentYear = date('Y');
        for ($i=0; $i<5; $i++)
        {
            $years[$i] = $currentYear - $i;
        }
        array_shift($years);
        $curArticle = Db::name('article')->where("cat_id != 1 and cat_id != 7 and YEAR(FROM_UNIXTIME(publish_time)) = $currentYear")->select();
        foreach($curArticle as &$item){
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        $history = [];
        foreach($years as $val){
            $info = Db::name('article')->where("cat_id != 1 and cat_id != 7 and YEAR(FROM_UNIXTIME(publish_time)) = $val")->select();
            foreach($info as &$item){
                $item['content'] = htmlspecialchars_decode($item['content']);
            }
            $history[$val] = $info;
        }
        $this->assign('curArticle',$curArticle);
        $this->assign('history',$history);
        //print_r($curArticle);
        //print_r($history);
        //$this->assign('article', $article);
        return $this->fetch();
    }

    /**
     * 文章内列表页
     */
    public function articleList()
    {
        $article_cat = M('ArticleCat')->where("parent_id  = 0")->select();
        $this->assign('article_cat', $article_cat);
        return $this->fetch();
    }

    /**
     * 文章内容页
     */
    public function detail()
    {
        $article_id = I('article_id/d', 1);
        $article = D('article')->where("article_id", $article_id)->find();
        if ($article) {
            $parent = D('article_cat')->where("cat_id", $article['cat_id'])->find();
            $this->assign('cat_name', $parent['cat_name']);
            $article['content'] = htmlspecialchars_decode($article['content']);
            $this->assign('article', $article);
        }
        return $this->fetch();
    }

}