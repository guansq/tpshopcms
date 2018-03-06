<?php
/**
 * Created by PhpStorm.
 * User: HaloBear
 * Date: 2018/3/2
 * Time: 11:41
 */
namespace app\home\controller;

use think\Db;
use think\Request;

class Download extends Base
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
        $curArticle = Db::name('article')->where("cat_id = 7 and YEAR(FROM_UNIXTIME(add_time)) = $currentYear")->select();
        foreach($curArticle as &$item){
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        $history = [];
        foreach($years as $val){
            $info = Db::name('article')->where("cat_id = 7 and YEAR(FROM_UNIXTIME(add_time)) = $val")->select();
            foreach($info as &$item){
                $item['content'] = htmlspecialchars_decode($item['content']);
            }
            $history[$val] = $info;
        }
        $this->assign('curArticle',$curArticle);
        $this->assign('history',$history);

        return $this->fetch();
    }

    public function getPwd(){
        $config_id = 87;
        $pwd = D('config')->where('id', $config_id)->value('value');
        $this->ajaxReturn(['status'=>1,'msg'=>'成功','result'=>$pwd]);
    }

    public function getDownload(Request $request){
        $data = $request->request();
        $keyword = $data['keyword'];
        if (!empty($keyword)) {
            $where['title'] = ['like', "%$keyword%"];
        }
        $list = D('download')::where($where)->order('sort asc')->select();
        /*$list = [
            ['id'=>1,'title'=>'下载文件名','file_url'=>'/public/upload/allfile/20180304/39fb97400dcbeb837a9ba74810688077.docx']
        ];*/
        $this->ajaxReturn(['status'=>1,'msg'=>'成功','result'=>$list]);
    }

}