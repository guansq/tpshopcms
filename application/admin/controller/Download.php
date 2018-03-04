<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/4
 * Time: 12:47
 */

namespace app\admin\controller;

use think\Page;
use think\Request;
use app\admin\model\Download as Down;

class Download extends Base
{

    public function downList(Request $request)
    {
        $data = $request->request();
        $is_json = $data['is_json'];
        $where = [];
        $keyword = $data['keyword'];
        //$p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
        //$size = empty($_REQUEST['size']) ? 20 : $_REQUEST['size'];
        if (!empty($keyword)) {
            $where['title'] = ['like', "%$keyword%"];
        }
        //->page("$p,$size")
        $list = Down::where($where)->order('sort asc')->select();

        if ($is_json) {
            $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'result' => $list]);
        }
        //$this->assign('pager', $pager);// 赋值分页输出
        $this->assign('keyword', $keyword);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function pwdSet(Request $request)
    {
        $data = $request->request();
        $config_id = 87;
        $pwd = D('config')->where('id', $config_id)->value('value');
        if ($request->isPost()) {
            //echo $data['pwd'];die;
            D('config')->where('id',$config_id)->update(['value'=>$data['pwd']]);
            $this->success("操作成功", U('Admin/Download/downList'));
        } else {
            $this->assign('pwd', $pwd);
            return $this->fetch();
        }
    }

    public function addFile()
    {
        return $this->fetch();
    }

    public function downHandle()
    {
        $data = I('post.');

        if ($data['act'] == 'add') {
            $file = request()->file('file_url');
            // 移动到框架应用根目录/public/uploads/ 目录下
            if ($file) {
                $info = $file->move(ROOT_PATH . 'public' . DS . 'upload' . DS . 'allfile');
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    $data['file_url'] = '/public/upload/allfile/' . $info->getSaveName();
                } else {
                    // 上传失败获取错误信息
                    echo $file->getError();
                    die;
                }
            }
            $r = D('download')->add($data);
        }
        if ($data['act'] == 'edit') {
            $r = D('ad')->where('ad_id', $data['ad_id'])->save($data);
        }

        if ($data['act'] == 'del') {
            $r = D('download')->where('id', $data['del_id'])->delete();
            if ($r) $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/adList');
        // 不管是添加还是修改广告 都清除一下缓存
        //delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
        \think\Cache::clear();

        if ($r) {
            $this->success("操作成功", U('Admin/Download/downList'));
        } else {
            $this->error("操作失败");
        }
    }
}