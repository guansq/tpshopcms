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
 * Author: 当燃
 * Date: 2016-05-09
 */
namespace app\seller\controller;

use think\Page;
use think\Verify;
use think\Db;
use think\Session;

use app\admin\logic\StoreLogic;

class Admin extends Base
{

    public function index()
    {
        $list = array();
        $keywords = I('keywords');
        if (empty($keywords)) {
            $res = D('seller')->where("store_id", STORE_ID)->select();
        } else {
            $seller_where = array(
                'store_id' => STORE_ID,
                'seller_name' => ['like', '%' . $keywords . '%']
            );
            $res = Db::name('seller')->where($seller_where)->order('seller_id')->select();
        }
        $group = D('seller_group')->where(array('store_id' => STORE_ID))->getField('group_id,group_name');

        if ($res && $group) {
            foreach ($res as $val) {
                $val['role'] = $group[$val['group_id']];
                $val['enabled'] = $val['enabled'] == 0 ? '启用' : '停用';
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function modify_pwd()
    {
        $seller_id = I('get.seller_id/d');
        if ($seller_id > 0) {
            $info = D('seller')->where(array('seller_id' => $seller_id, 'store_id' => STORE_ID))->find();
            if ($info) {
                $user = M('users')->where("user_id", $info['user_id'])->find();
            } else {
                $this->error('找不到该管理员', U('Seller/admin/index'));
            }
            $info['user_name'] = empty($user['mobile']) ? $user['email'] : $user['mobile'];
            $this->assign('info', $info);
        } 
        $data = I('post.');
        if(IS_POST){
            if ($data['seller_id'] > 0) {
                $seller = session('seller');//修改密码
                if ($data['seller_id'] == $seller['seller_id'] || $seller['is_admin']==1) {
                    if ($data['password'] == $data['password2']) {
                        $this->ajaxReturn(['status' =>-1,'msg'=>"两次密码一致",]);
                    } else {
                        if (M('users')->where(array('user_id' => $seller['user_id'], 'password' => encrypt($data['password'])))->count() > 0) {
                            M('users')->where(array('user_id' => $seller['user_id']))->save(array('password' => encrypt($data['password2'])));
                            $r = M('seller')->where(['seller_id' => $data['seller_id']])->save(array('enabled' => $data['enabled']));
                            if ($r !== false) {
                                $this->ajaxReturn(['status' =>1, 'msg'=>"修改成功", 'url'=>U('Admin/index')]);
                            } else {
                                $this->ajaxReturn(['status' =>-1, 'msg'=>"修改失败",]);
                            }
                        } else {
                            $this->ajaxReturn(['status' =>-1,'msg'=>"原密码错误",]);
                        }
                    }
                } else {
                    $this->ajaxReturn(['status' =>-1,'msg'=>"非法操作,只能修改自己的密码",]);
                }
            }
        }
         
        return $this->fetch();
    }
    
    public function admin_info()
    {
        $seller_id = I('get.seller_id/d');
        if ($seller_id > 0) {
            $info = D('seller')->where(array('seller_id' => $seller_id, 'store_id' => STORE_ID))->find();
            if ($info) {
                $user = M('users')->where("user_id", $info['user_id'])->find();
            } else {
                $this->error('找不到该管理员', U('Seller/admin/index'));
            }
            $info['user_name'] = empty($user['mobile']) ? $user['email'] : $user['mobile'];
            $this->assign('info', $info);
        }
        $role = D('seller_group')->where(array('store_id' => STORE_ID))->select();
        if(!$role){
            $this->error('需先添加账号组', U('Seller/Admin/role'));
            exit();
        }
        $this->assign('role', $role);
        return $this->fetch();
    }

    public function adminHandle()
    {
        $data = I('post.');

        if ($data['act'] == 'del' && $data['seller_id'] > 0) {
            //删除店铺管理员
            $manage = M('seller')->where(array('seller_id' => $data['seller_id']))->find();
            if ($manage['store_id'] == STORE_ID) {
                M('seller')->where('seller_id', $data['seller_id'])->delete();
                sellerLog('删除店铺管理员' . $manage['seller_name']);
            } else {
                exit(json_encode(0));//只能删除本店的管理员
            }
            exit(json_encode(1));
        }

        if ($data['seller_id'] > 0) {
            $seller = session('seller');//修改密码
            if ($data['seller_id'] == $seller['seller_id'] || $seller['is_admin']==1) {
                if ($data['password'] == $data['password2']) {
                    $this->ajaxReturn(['status' =>-1,'msg'=>"两次密码一致",]);
                } else {
                    if (M('users')->where(array('user_id' => $seller['user_id'], 'password' => encrypt($data['password'])))->count() > 0) {
                        M('users')->where(array('user_id' => $seller['user_id']))->save(array('password' => encrypt($data['password2'])));
                        $r = M('seller')->where(['seller_id' => $data['seller_id']])->save(array('enabled' => $data['enabled']));
                        if ($r !== false) {
                            $this->ajaxReturn(['status' =>1, 'msg'=>"修改成功", 'url'=>U('Admin/index')]);
                        } else {
                            $this->ajaxReturn(['status' =>-1, 'msg'=>"修改失败",]);
                        }
                    } else {
                        $this->ajaxReturn(['status' =>-1,'msg'=>"原密码错误",]);
                    }
                }
            } else {
                $this->ajaxReturn(['status' =>-1,'msg'=>"非法操作,只能修改自己的密码",]);
            }
        } else {
            if(empty($data['seller_name']) || empty($data['user_name']) || empty($data['password']) ){
                $this->ajaxReturn(['status' =>-1,'msg'=>"所有信息为必填",]);
            }
            //验证商家后台登陆账号是否有同名
            if (M('seller')->where("seller_name", $data['seller_name'])->count()) {
                $this->ajaxReturn(['status' =>-1,'msg'=>"此登陆账号名已被注册，请更换"]);
            }
            $uname = check_email($data['user_name']) ? 'email' : 'mobile';

            //查找验证绑定用户
            $userinfo = M('users')->field('password,user_id')->where([$uname=>$data['user_name']])->find();
            if (empty($userinfo)) {
                $this->ajaxReturn(['status' =>-1,'msg'=>"请先注册前台会员",]);
            } elseif ($userinfo['password'] != encrypt($data['password'])) {
                $this->ajaxReturn(['status' =>-1,'msg'=>"登陆密码错误",]);
            } else {
                if (M('seller')->where("user_id", $userinfo['user_id'])->count()) {
                    $this->ajaxReturn(['status' =>-1,'msg'=>"该用户已经添加过店铺管理员",]);
                }
                $data['password'] = encrypt($data['password']);
                $data['user_id'] = $userinfo['user_id'];
                $data['store_id'] = STORE_ID;
                $data['add_time'] = time();
                unset($data['seller_id']);
                $r = M('seller')->add($data);
                if ($r !== false) {
                    $this->ajaxReturn(['status' =>1, 'msg'=>"添加成功", 'url'=>U('Admin/index')]);
                } else {
                    $this->ajaxReturn(['status' =>-1, 'msg'=>"添加失败",]);
                }
            }
        }
    }


    /*
     * 管理员登陆
     */
    public function login()
    {
        if (session('?seller_id') && session('seller_id') > 0) {
            $this->error("您已登录", U('Index/index'));
        }

        if (IS_POST) {
            $verify = new Verify();
            if (!$verify->check(I('post.vertify'), "seller_login")) {
                exit(json_encode(array('status' => 0, 'msg' => '验证码错误')));
            }
            $seller_name = I('post.username');
            $password = I('post.password');
            if (!empty($seller_name) && !empty($password)) {
                $seller = M('seller')->where(array('seller_name' => $seller_name))->find();
                if ($seller) {
                	$store = M('store')->where(array('store_id'=>$seller['store_id'],'store_state'=>1))->find();
                	if(!$store) exit(json_encode(array('status' => 0, 'msg' => '店铺已关闭，请联系平台客服')));
                    $user_where = array(
                        'user_id' => $seller['user_id'],
                        'password' => encrypt($password)
                    );
                    $user = M('users')->where($user_where)->find();
                    if ($user) {
                        if ($seller['is_admin'] == 0 && $seller['enabled'] == 1) {
                            exit(json_encode(array('status' => 0, 'msg' => '该账户还没启用激活')));
                        }
                        if ($seller['group_id'] > 0) {
                            $group = M('seller_group')->where(array('group_id' => $seller['group_id']))->find();
                            $seller['act_limits'] = $group['act_limits'];
                            $seller['smt_limits'] = $group['smt_limits'];
                        } else {
                            $seller['act_limits'] = 'all';
                            $seller['smt_limits'] = 'all';
                        }
                        session('seller', $seller);
                        session('seller_id', $seller['seller_id']);
                        session('store_id', $seller['store_id']);
                        M('seller')->where(array('seller_id' => $seller['seller_id']))->save(array('last_login_time' => time()));
                        sellerLog('商家管理中心登录');
                        $url = session('from_url') ? session('from_url') : U('Index/index');
                        exit(json_encode(array('status' => 1, 'url' => $url)));
                    } else {
                        exit(json_encode(array('status' => 0, 'msg' => '账号密码不正确')));
                    }
                } else {
                    exit(json_encode(array('status' => 0, 'msg' => '账号不存在')));
                }
            } else {
                exit(json_encode(array('status' => 0, 'msg' => '请填写账号密码')));
            }
        }
        return $this->fetch();
    }

    /**
     * 退出登陆
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        $this->success("退出成功", U('Seller/Admin/login'));
    }

    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
            'reset' => false
        );
        $Verify = new Verify($config);
        $Verify->entry("seller_login");
		exit();
    }

    public function role()
    {
        $list = D('seller_group')->where(array('store_id' => STORE_ID))->order('group_id desc')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function role_info()
    {
        $role_id = I('get.group_id/d');
        if ($role_id) {
            $detail = M('seller_group')->where(array('store_id' => STORE_ID, 'group_id' => $role_id))->find();
            if ($detail) {
                $detail['act_limits'] = explode(',', $detail['act_limits']);
                $this->assign('detail', $detail);
            } else {
                $this->error('找不到该账号组', U('Seller/Admin/role'));
            }
        }

        $right = M('system_menu')->where(array('type' => 1))->order('id')->select();
        foreach ($right as $k => $val) {
            if (!empty($detail)) {
                $val['enable'] = in_array($val['id'], $detail['act_limits']);
            }
            $modules[$val['group']][] = $val;
        }
        //权限组
        $group = array('goods' => '商品管理', 'order' => '订单物流', 'promtion' => '促销管理', 'store' => '店铺管理', 'service' => '售后服务',
            'charts' => '统计报表', 'mesaage' => '客服消息', 'seller' => '账号管理', 'finance' => '财务管理', 'distribut' => '分销管理');
        $this->assign('group', $group);
        $this->assign('modules', $modules);
        $this->assign('smt_list', M('store_msg_tpl')->select());
        return $this->fetch();
    }

    public function roleSave()
    {
        $data = I('post.');
        $data['act_limits'] = is_array($data['act_limits']) ? implode(',', $data['act_limits']) : '';
        $data['smt_limits'] = is_array($data['smt_limits']) ? implode(',', $data['smt_limits']) : '';
        if (empty($data['group_id'])) {
            $data['store_id'] = STORE_ID;
            $r = M('seller_group')->add($data);
        } else {
            $r = M('seller_group')->where('group_id', $data['group_id'])->save($data);
        }
        if ($r) {
            sellerLog('管理角色');
            $this->success("操作成功!", U('Admin/role'));
        } else {
            $this->error("操作失败!");
        }
    }

    /**
     * 商家角色删除
     */
    public function roleDel()
    {
        $group_id = I('post.group_id/d');
        $seller = D('seller')->where(array('group_id' => $group_id, 'store_id' => STORE_ID))->find();
        if ($seller) {
            exit(json_encode("请先清空所属该角色的管理员"));
        } else {
            $d = M('seller_group')->where(array('group_id' => $group_id, 'store_id' => STORE_ID))->delete();
            if ($d) {
                exit(json_encode(1));
            } else {
                exit(json_encode("删除失败"));
            }
        }
    }

    public function log()
    {
        $Log = M('seller_log');
        $p = I('p', 1);
        $seller_id = session('seller_id');
        $logs = Db::name('seller_log')->alias('sl')
            ->join('__SELLER__ s', 's.seller_id = sl.log_seller_id')
            ->where('s.seller_id', $seller_id)->order('log_time DESC')
            ->page($p . ',20')
            ->select();
        $this->assign('list', $logs);
        $count = $Log->alias('sl')
            ->join('__SELLER__ s', 's.seller_id = sl.log_seller_id')
            ->where('s.seller_id', $seller_id)
            ->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     *  商家登录后 处理相关操作
     */
    public function login_task()
    {

        // 多少天后自动分销记录自动分成
		if(file_exists(APP_PATH.'common/logic/DistributLogic.php')){
			$distributLogic = new \app\common\logic\DistributLogic();
            $distributLogic->autoConfirm(STORE_ID); // 自动确认分成
        }

        // 商家结算 
        $storeLogic = new StoreLogic();
        $storeLogic->auto_transfer(STORE_ID); // 自动结算

    }

    /**
     * 清空系统缓存
     */
    public function cleanCache()
    {
        delFile('./public/upload/goods/thumb');// 删除缩略图
		\think\Cache::clear(); 
        //$html_arr = glob("./Application/Runtime/Html/*.html");
        //foreach ($html_arr as $key => $val) {
            // 删除详情页
        //    if (strstr($val, 'Home_Goods_goodsInfo') || strstr($val, 'Home_Goods_ajaxComment') || strstr($val, 'Home_Goods_ajax_consult'))
        //        unlink($val);
        //}
        $this->success("清除成功!!!", U('Index/index'));
    }

    /**
     * 商品静态页面缓存清理
     */
    public function ClearGoodsThumb()
    {
        $goods_id = I('goods_id/d');
        delFile("./public/upload/goods/thumb/$goods_id"); // 删除缩略图
        $json_arr = array('status' => 1, 'msg' => '清除成功,请清除对应的缩略图', 'result' => '');
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * 清空静态商品页面缓存
     */
    public function ClearGoodsHtml()
    {
        $goods_id = I('goods_id/d');	    
        \think\Cache::clear();           
        $json_arr = array('status' => 1, 'msg' => '清除成功', 'result' => '');       
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

}