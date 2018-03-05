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
        return $this->fetch();
    }

    public function getPwd(){
        $score = '111111';
        $this->ajaxReturn(['status'=>1,'msg'=>'成功','result'=>$score]);
    }

    public function getDownload(Request $request){
        $data = $request->request();
        $keyword = $data['keyword'];
        $list = [
            ['id'=>1,'title'=>'下载文件名','file_url'=>'/public/upload/all/file/20180304/39fb97400dcbeb837a9ba74810688077.docx']
        ];
        $this->ajaxReturn(['status'=>1,'msg'=>'成功','result'=>$list]);
    }

}