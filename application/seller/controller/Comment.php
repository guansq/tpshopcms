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
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2016-06-20
 */

namespace app\seller\controller;

use think\AjaxPage;
use think\Db;
  
class Comment extends Base
{
    public function index()
    {
        checkIsBack();
        return $this->fetch();
    }

    public function detail()
    {
        $id = I('get.id/d');
        $res = M('comment')->where(array('comment_id' => $id, 'store_id' => STORE_ID))->find();
        $user = Db::name('users')->where('user_id', $res['user_id'])->find();
        if (!$res) {
            exit($this->error('不存在该评论'));
        }
        if (IS_POST) {
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['add_time'] = time();
            $add['username'] = '卖家';
            $add['is_show'] = 1;
            $add['store_id'] = STORE_ID;
            $row = M('comment')->add($add);
            if ($row) {
                $this->success('添加成功');
                exit();
            } else {
                $this->error('添加失败');
                exit();
            }
        }
        $reply = M('comment')->where(array('parent_id' => $id))->select(); // 评论回复列表
        $reply_list = Db::name('reply')->where(['comment_id'=>$id,'deleted'=>0])->select(); // 评论回复列表
        $this->assign('comment', $res);
        $this->assign('reply_list', $reply_list);
        $this->assign('user', $user);
        $this->assign('reply', $reply);
        return $this->fetch();
    }

    public function del()
    {
        $id = I('get.id/d');
        $row = M('comment')->where(array('comment_id' => $id, 'store_id' => STORE_ID))->delete();
        if ($row) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 软删除回复
     * @throws \think\Exception
     */
    public function delReply()
    {
        $reply_id = input('reply_id/d');
        $comment_id = input('comment_id/d');
        $reply = Db::name('reply')->where(array('reply_id' => $reply_id))->update(['deleted' => 1]);
        $comment = Db::name('comment')->where(array('comment_id' => $comment_id))->setDec('reply_num');
        if($reply){
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '删除失败', 'result' => '']);
    }

    public function op()
    {
        $type = I('post.type');
        $selected_id = I('post.selected');
        $row = false;
        if (!in_array($type, array('del', 'show', 'hide')) || !$selected_id) {
            $this->error('非法操作');
        }
        if ($type == 'del') {
            //删除回复
            $row = Db::name('comment')->where('comment_id', 'in', $selected_id)->whereOr('parent_id', 'in', $selected_id)->delete();
        }
        if ($type == 'show') {
            $row = Db::name('comment')->where('comment_id', 'in', $selected_id)->save(array('is_show' => 1));
        }
        if ($type == 'hide') {
            $row = Db::name('comment')->where('comment_id', 'in', $selected_id)->save(array('is_show' => 0));
        }
        if (!$row) {
            $this->error('操作失败');
        } else {
            $this->success('操作成功');
        }
    }

    public function ajaxindex()
    {
        $username = I('nickname', '', 'trim');
        $content = I('content', '', 'trim');
        $where['c.parent_id'] = 0;
        $where['c.store_id'] = STORE_ID;
        if ($username) {
            $where['u.nickname'] = $username;
        }
        if ($content) {
            $where['c.content'] = array('like', '%' . $content . '%');
        }
        $count = Db::name('comment')->alias('c')->join('__USERS__ u', 'u.user_id = c.user_id', 'LEFT')->where($where)->count();
        $Page = new AjaxPage($count, 16);
        //是否从缓存中读取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            delIsBack();
        }

        $comment_list = Db::name('comment')
            ->alias('c')
            ->field('c.*,u.nickname as nickname')
            ->join('__USERS__ u', 'u.user_id = c.user_id', 'LEFT')
            ->where($where)->order('add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if (!empty($comment_list)) {
            $goods_id_arr = get_arr_column($comment_list, 'goods_id');
            $goods_list = M('Goods')->where("goods_id", "in", implode(',', $goods_id_arr))->getField("goods_id,goods_name");
            $this->assign('goods_list', $goods_list);
        }
        cachePage($Page);
        $show = $Page->show();
        $this->assign('comment_list', $comment_list);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }


    public function consult_info()
    {
        $id = I('get.id/d');
        $res = M('goods_consult')->where(array('id' => $id))->find();
        if (!$res) {
            $this->error('不存在该咨询');
            exit;
        }
        if (IS_POST) {
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['consult_type'] = $res['consult_type'];
            $add['add_time'] = time();
            $add['store_id'] = STORE_ID;
            $add['is_show'] = 1;
            $row = M('goods_consult')->add($add);
            if ($row) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
            exit;
        }
        $reply = M('goods_consult')->where(array('parent_id' => $id))->select(); // 咨询回复列表
        $this->assign('comment', $res);
        $this->assign('reply', $reply);
        return $this->fetch();
    }
}