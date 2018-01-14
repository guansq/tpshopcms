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
 * Date: 2015-09-09
 */

namespace app\admin\controller;
use think\Db;
use think\Page;
use app\common\util\WechatUtil;

class Wechat extends Base 
{
    private $wx_user;
    
    function __construct()
    {
        parent::__construct();
        $this->wx_user = M('wx_user')->find();
    }

    public function index()
    {
        $wx_user = $this->wx_user;
        header("Location:".U('Wechat/setting',['id'=>$wx_user['id']]));
        exit;  
    }

    public function add()
    {
        $exist = $this->wx_user;
        if($exist[0]['id'] > 0) {
            $this->error('只能添加一个公众号噢');
            exit;
        }
        if(IS_POST){
            $data = I('post.');
            $data['create_time'] = time();
            $data['token'] = get_rand_str(6,1,0);
            $row = DB::name('wx_user')->insertGetId($data);
            if($row){
                $this->success('添加成功',U('Admin/Wechat/setting',array('id'=>$row)));
            }else{
                $this->error('操作失败');
            }
            exit;
        }
        return $this->fetch();
    }

    public function del()
    {
        $id = I('get.id');
        $row = M('wx_user')->where(array('id'=>$id))->delete();
        if($row){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    
    public function setting()
    {
        $id = I('get.id');
        if(!empty($id)){
            $wechat = M('wx_user')->where(array('id'=>$id))->find();
            if(!$wechat){
                $this->error("公众号不存在");
                exit;
            }
            if(IS_POST){
                $post_data = input('post.');
                $post_data['web_expires'] = 0;
                $row = M('wx_user')->where(array('id'=>$id))->update($post_data);
                $row && exit($this->success("修改成功"));
                exit($this->error("修改失败"));
            }
            $apiurl = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?m=Home&c=Weixin&a=index';
            
            $this->assign('wechat',$wechat);
            $this->assign('apiurl',$apiurl);
        }else{
            //不存在ID则添加
            $exist = $this->wx_user;
            if($exist[0]['id'] > 0){
                $this->error('只能添加一个公众号噢');
                exit;
            }
            if(IS_POST){
                $data = input('post.');
                $data['token'] = get_rand_str(6,1,0);
                $data['create_time'] = time();
                $row = Db::name('wx_user')->insertGetId($data);
                if($row){
                    $this->success('添加成功',U('Admin/Wechat/setting',array('id'=>$row)));
                }else{
                    $this->error('操作失败');
                }
                exit;
            }
        }
        return $this->fetch();
    }
    
    public function menu()
    {
        $wechat = $this->wx_user;
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能进行微信菜单管理', U('Admin/Wechat/index'));
        }
        if(IS_POST){
            $post_menu = input('post.menu/a');
            //查询数据库是否存在
            $menu_list = M('wx_menu')->where(array('token'=>$wechat['token']))->getField('id',true);
            foreach($post_menu as $k=>$v){
                $v['token'] = $wechat['token'];
                if(in_array($k,$menu_list)){
                    //更新
                    M('wx_menu')->where(array('id'=>$k))->save($v);
                }else{
                    //插入
                    M('wx_menu')->where(array('id'=>$k))->add($v);
                }
            }
            $this->success('操作成功,进入发布步骤',U('Admin/Wechat/pub_menu'));
            exit;
        }
        //获取最大ID
        //$max_id = M('wx_menu')->where(array('token'=>$wechat['token']))->field('max(id) as id')->find();
        $max_id = DB::query("SHOW TABLE STATUS WHERE NAME = '__PREFIX__wx_menu'");
        $max_id = $max_id[0]['auto_increment'] ? $max_id[0]['auto_increment'] : $max_id[0]['Auto_increment'];

        //获取父级菜单
        $p_menus = M('wx_menu')->where(array('token'=>$wechat['token'],'pid'=>0))->order('id ASC')->select();
        $p_menus = convert_arr_key($p_menus,'id');
        //获取二级菜单
        $c_menus = M('wx_menu')->where(array('token'=>$wechat['token'],'pid'=>array('gt',0)))->order('id ASC')->select();
        $c_menus = convert_arr_key($c_menus,'id');
        $this->assign('p_lists',$p_menus);
        $this->assign('c_lists',$c_menus);
        $this->assign('max_id',$max_id ? $max_id-1 : 0);
        return $this->fetch();
    }


    /*
     * 删除菜单
     */
    public function del_menu()
    {
        $id = I('get.id');
        if(!$id){
            exit('fail');
        }
        $row = M('wx_menu')->where(array('id'=>$id))->delete();
        $row && M('wx_menu')->where(array('pid'=>$id))->delete(); //删除子类
        if($row){
            exit('success');
        }else{
            exit('fail');
        }
    }

    /*
     * 生成微信菜单
     */
    public function pub_menu()
    {
        $menu = array();
        $menu['button'][] = array(
            'name'=>'测试',
            'type'=>'view',
            'url'=>'http://wwwtp-shhop.cn'
        );
        $menu['button'][] = array(
            'name'=>'测试',
            'sub_button'=>array(
                array(
                    "type"=> "scancode_waitmsg",
                    "name"=> "系统拍照发图",
                    "key"=> "rselfmenu_1_0",
                    "sub_button"=> array()
                )
            )
        );

        //获取菜单
        $wechat = $this->wx_user;
        //获取父级菜单
        $p_menus = M('wx_menu')->where(array('token'=>$wechat['token'],'pid'=>0))->order('id ASC')->select();
        $p_menus = convert_arr_key($p_menus,'id');

        $post_str = $this->convert_menu($p_menus,$wechat['token']);
        // http post请求
        if(!count($p_menus) > 0){
            $this->error('没有菜单可发布',U('Wechat/menu'));
            exit;
        }
        
        $wechatObj = new WechatUtil($wechat);
        $access_token = $wechatObj->getAccessToken();
        if ($access_token === false) {
            return $this->error($wechatObj->getError());
        }
        $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
//        exit($post_str);
        $return = httpRequest($url,'POST',$post_str);
        $return = json_decode($return,1);
        if($return['errcode'] == 0){
            $this->success('菜单已成功生成',U('Wechat/menu'));
        }else{
            $this->error("微信错误代码:{$return['errcode']}, 错误信息:{$return['errmsg']}", U('Wechat/menu'));
        }
    }

    //菜单转换
    private function convert_menu($p_menus,$token)
    {
        $key_map = array(
            'scancode_waitmsg'=>'rselfmenu_0_0',
            'scancode_push'=>'rselfmenu_0_1',
            'pic_sysphoto'=>'rselfmenu_1_0',
            'pic_photo_or_album'=>'rselfmenu_1_1',
            'pic_weixin'=>'rselfmenu_1_2',
            'location_select'=>'rselfmenu_2_0',
        );
        $new_arr = array();
        $count = 0;
        foreach($p_menus as $k => $v){
            $new_arr[$count]['name'] = $v['name'];

            //获取子菜单
            $c_menus = M('wx_menu')->where(array('token'=>$token,'pid'=>$k))->select();

            if($c_menus){
                foreach($c_menus as $kk=>$vv){
                    $add = array();
                    $add['name'] = $vv['name'];
                    $add['type'] = $vv['type'];
                    // click类型
                    if($add['type'] == 'click'){
                        $add['key'] = $vv['value'];
                    }elseif($add['type'] == 'view'){
                        $add['url'] = $vv['value'];
                    }else{
                        //$add['key'] = $key_map[$add['type']];
                        $add['key'] = $vv['value'];       //2016年9月29日01:28:37
                    }
                    $add['sub_button'] = array();
                    if($add['name']){
                        $new_arr[$count]['sub_button'][] = $add;
                    }
                }
            }else{
                $new_arr[$count]['type'] = $v['type'];
                // click类型
                if($new_arr[$count]['type'] == 'click'){
                    $new_arr[$count]['key'] = $v['value'];
                }elseif($new_arr[$count]['type'] == 'view'){
                    //跳转URL类型
                    $new_arr[$count]['url'] = $v['value'];
                }else{
                    //其他事件类型
                    //$new_arr[$count]['key'] = $key_map[$v['type']];
                    $new_arr[$count]['key'] = $v['value'];        //2016年9月29日01:40:13
                }
            }
            $count++;
        }
       // return json_encode(array('button'=>$new_arr));
        return json_encode(array('button'=>$new_arr),JSON_UNESCAPED_UNICODE);
    }

    /*
     * 文本回复
     */
    public function text()
    {
        $wechat = $this->wx_user;
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能进行文本回复管理', U('Admin/Wechat/index'));
        }
        $count = M('wx_keyword')->where(array('token'=>$wechat['token'],'type'=>'TEXT'))->count();
        $pager = new Page($count,10);
        $sql = "SELECT k.id,k.keyword,t.text FROM __PREFIX__wx_keyword k LEFT JOIN __PREFIX__wx_text AS t ON t.id = k.pid WHERE k.token = '{$wechat['token']}' AND type = 'TEXT' ORDER BY t.createtime DESC LIMIT {$pager->firstRow},{$pager->listRows}";
        $show = $pager->show();
        $lists = DB::query($sql);

        $this->assign('page',$show);
        $this->assign('lists',$lists);
        $this->assign('wechat',$wechat);

        return $this->fetch();
    }
    /*
     * 添加文本回复
     */
    public function add_text()
    {
        $wechat = $this->wx_user;
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能添加文本回复', U('Admin/Wechat/index'));
        }
        if(IS_POST){
            $edit = I('get.edit');
            $add['keyword'] =  I('post.keyword');
            $add['token'] =  $wechat['token'];
            $add['text'] = I('post.text');
            if(!$edit){
                //添加模式
                $add['createtime'] = time();
                $add['pid'] = DB::name('wx_text')->insertGetId($add);
                unset($add['text']);
                unset($add['createtime']);
                $add['type'] = 'TEXT';
                $row = M('wx_keyword')->add($add);
            }else{
                //编辑模式
                $id = I('post.kid');
                $model = M('wx_keyword')->where(array('id'=>$id));

                $data = $model->find();
                if($data){
                    $update = I('post.');
                    $update['type'] = 'TEXT';
                    M('wx_keyword')->where(array('id'=>$id))->save($update);
                    $row = M('wx_text')->where(array('id'=>$data['pid']))->save($add);

                }
            }
            $row ? $this->success("添加成功",U('Admin/Wechat/text')) : $this->error("添加失败",U('Admin/Wechat/text'));
            exit;
        }

        $id = I('get.id');
        if($id){
            $sql = "SELECT k.id,k.keyword,t.text FROM __PREFIX__wx_keyword k LEFT JOIN __PREFIX__wx_text AS t ON t.id = k.pid WHERE k.token = '{$wechat['token']}' AND k.id = {$id} AND k.type = 'TEXT'";
            $data = DB::query($sql);
            $this->assign('keyword',$data[0]);
        }

        return $this->fetch();
    }

    /*
     * 删除文本回复
     */
    public function del_text()
    {
        $id = I('get.id');
        $row = M('wx_keyword')->where(array('id'=>$id))->find();
        if($row){
            M('wx_keyword')->where(array('id'=>$id))->delete();
            M('wx_text')->where(array('id'=>$row['pid']))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }
    /*
     * 图文列表
     */
    public function img()
    {
        $wechat = $this->wx_user;
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能进行图文回复管理', U('Admin/Wechat/index'));
        }
        $count = M('wx_keyword')->where(array('token'=>$wechat['token'],'type'=>'IMG'))->count();
        $pager = new Page($count,10);
        $sql = "SELECT k.id,k.keyword,i.title,i.url,i.pic,i.desc FROM __PREFIX__wx_keyword k LEFT JOIN __PREFIX__wx_img i ON i.id = k.pid WHERE k.token = '{$wechat['token']}' AND type = 'IMG' ORDER BY i.createtime DESC LIMIT {$pager->firstRow},{$pager->listRows}";
        $show = $pager->show();
        $lists = DB::query($sql);

        $this->assign('page',$show);
        $this->assign('lists',$lists);
        $this->assign('wechat',$wechat);
        return $this->fetch();
    }
    
    /*
     * 添加图文回复
     */
    public function add_img()
    {
        $wechat = $this->wx_user;
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能添加图文回复', U('Admin/Wechat/index'));
        }
        if(IS_POST){

            $add['keyword'] =  I('post.keyword');
            $add['token'] =  $wechat['token'];
            $add['title'] = I('post.title');
            $add['desc'] = I('post.desc');

            $add['pic'] = I('post.pic'); //封面图片
            if(!strstr($add['pic'],'http'))
                $add['pic'] = SITE_URL.$add['pic'];

            $add['url'] = I('post.url');  // 商品地址 或 其他
            $add['goods_id'] = I('post.goods_id');
            $add['goods_name'] = I('post.goods_name'); //商品名字

            empty($add['keyword']) && $this->error("关键词不得为空");
            empty($add['title'])   && $this->error("标题不得为空");
            empty($add['url'])     && $this->error("url不得为空");
            empty($add['pic'])     && $this->error("封面图片不得为空");
            empty($add['desc'])    && $this->error("简介不得为空");

            $edit = I('get.edit');
            if(!$edit){
                //添加模式
                $add['createtime'] = time();                
                
            if(!strstr($add['pic'],'http'))
                $add['pic'] = SITE_URL.$add['pic'];                
                
                $wx_img_last_ins_id =  DB::name('wx_img')->insertGetId($add);
                $add['pid'] = $wx_img_last_ins_id;
                $add['type'] = 'IMG';
                $row = M('wx_keyword')->add($add);
            }else{
                //编辑模式
                $id = I('post.kid');
                $model = M('wx_keyword')->where(array('id'=>$id,'type'=>'IMG'));

                $data = $model->find();
                if($data){
                    $update = input('post.');
                    $update['type'] = 'IMG';
                    M('wx_keyword')->where(array('id'=>$id))->save($update);
                    $add['uptatetime'] = time();
                    $row = M('wx_img')->where(array('id'=>$data['pid']))->save($add);

                }
            }
            $row ? $this->success("添加成功",U('Admin/Wechat/img')) : $this->error("添加失败",U('Admin/Wechat/img'));
            exit;
        }

        $id = I('get.id');
        if($id){
            $sql = "SELECT k.id,k.keyword,i.title,i.url,i.pic,i.desc FROM __PREFIX__wx_keyword k LEFT JOIN __PREFIX__wx_img i ON i.id = k.pid WHERE k.token = '{$wechat['token']}' AND type = 'IMG' AND k.id = {$id}";
            $data = DB::query($sql);
            $this->assign('keyword',$data[0]);
        }
        return $this->fetch();


    }

    /*
     * 选择商品
     * //todo
     * //与wap端一起做
     */
    public function select_goods()
    {
        $url = 'http://'.$_SERVER['HTTP_HOST'];
        //http://www.tp-shop.cn/index.php?m=Home&c=Goods&a=info&id=

        $count = M('goods')->count();
        $pager = new Page($count,10);
        //$sql = "SELECT k.id,k.keyword,t.text FROM tp_wx_keyword k LEFT JOIN tp_wx_text AS t ON t.id = k.pid WHERE k.token = '{$wechat['token']}' AND type = 'TEXT' ORDER BY t.createtime DESC LIMIT {$pager->firstRow},{$pager->listRows}";
        $show = $pager->show();
        $sql = "SELECT goods_name,shop_price,
                CONCAT('{$url}/index.php?m=Home&c=Goods&a=info&id=',goods_id) AS goods_url,
                CONCAT('{$url}/',original_img) AS original_img
                 FROM __PREFIX__goods ORDER BY goods_id DESC LIMIT {$pager->firstRow},{$pager->listRows}";
        $lists = DB::query($sql);
        $this->assign('page',$show);
        $this->assign('lists',$lists);
        return $this->fetch();
    }
    /*
      * 删除图文回复
      */
    public function del_img()
    {
        $id = I('get.id');
        $row = M('wx_keyword')->where(array('id'=>$id))->find();
        if($row){
            M('wx_keyword')->where(array('id'=>$id))->delete();
            M('wx_img')->where(array('id'=>$row['pid']))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }

    /*
     * 多图文消息列表
     */
    public function news()
    {
        $wechat = $this->wx_user;
        $count = M('wx_keyword')->where(array('token'=>$wechat['token'],'type'=>'NEWS'))->count();
        $pager = new Page($count,10);
        $sql = "SELECT k.id,k.keyword,k.pid,i.img_id FROM __PREFIX__wx_keyword k LEFT JOIN __PREFIX__wx_news i ON i.id = k.pid WHERE k.token = '{$wechat['token']}' AND type = 'NEWS' ORDER BY i.createtime DESC LIMIT {$pager->firstRow},{$pager->listRows}";
        $show = $pager->show();
        $lists = DB::query($sql);

        $this->assign('page',$show);
        $this->assign('lists',$lists);
        $this->assign('wechat',$wechat);
        return $this->fetch();
    }

    /*
     * 添加多图文
     */
    public function add_news()
    {
        $wechat = $this->wx_user;
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能进行微信菜单管理', U('Admin/Wechat/index'));
        }
        if(IS_POST){
            $arr = explode(',',I('post.img_id/s'));
            if($arr)
                array_pop($arr);
            if(count($arr) <= 1){
                $this->error("单图文请到图文回复设置",U('Admin/Wechat/news'));
                exit;
            }
            $add['keyword'] =  I('post.keyword');
            $add['token'] =  $wechat['token'];
            $add['img_id'] =  implode(',',$arr);

            //添加模式
            $add['createtime'] = time();
            $wx_news_last_ins_id = DB::name('wx_news')->insertGetId($add);
            $add['pid'] = $wx_news_last_ins_id;
            $add['type'] = 'NEWS';
            $row = M('wx_keyword')->add($add);
            $row ? $this->success("添加成功",U('Admin/Wechat/news')) : $this->error("添加失败",U('Admin/Wechat/news'));
            exit;
        }
        return $this->fetch();
    }
    /*
     * 删除多图文
     */
    public function del_news()
    {
        $id = I('get.id');
        $row = M('wx_keyword')->where(array('id'=>$id))->find();
        if($row){
            M('wx_keyword')->where(array('id'=>$id))->delete();
            M('wx_news')->where(array('id'=>$row['pid']))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }
    /*
     * 预览多图文
     */
    public function preview()
    {
        $id = I('get.id');
        $news = M('wx_news')->where(array('id'=>$id))->find();
        $lists = M('wx_img')->where(array('id'=>array('in',$news['img_id'])))->select();
        $first = $lists[0];
        unset($lists[0]);
        $this->assign('first',$first);
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    public function select()
    {
        $wechat = $this->wx_user;
        $count = M('wx_keyword')->where(array('token'=>$wechat['token'],'type'=>'IMG'))->count();
        $pager = new Page($count,10);
        $sql = "SELECT k.id,k.pid,k.keyword,i.title,i.url,i.pic,i.desc FROM __PREFIX__wx_keyword k LEFT JOIN __PREFIX__wx_img i ON i.id = k.pid WHERE k.token = '{$wechat['token']}' AND type = 'IMG' ORDER BY i.createtime DESC LIMIT {$pager->firstRow},{$pager->listRows}";
        $show = $pager->show();
        $lists = DB::query($sql);

        $this->assign('page',$show);
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    /**
     * 粉丝详细列表
     */
    public function fans_list()
    {
        $wechatObj = new WechatUtil($this->wx_user);
        $access_token = $wechatObj->getAccessToken();
        if (!$access_token) {
            return $this->error($wechatObj->getError());
        }
        
        $next_openid = '';
        $p = intval(I('get.p')) ?: 1;
        for ($i = 1; $i <= $p; $i++) {
            $id_list = $wechatObj->getFanIdList($next_openid);
            if ($id_list === false) {
                return $this->error($wechatObj->getError());
            }
            $next_openid = $id_list['next_openid'];
        }
        
        $user_list = [];
        foreach ($id_list['data']['openid'] as $openid) {
            $user_list[$openid] = $wechatObj->getFanInfo($openid, $access_token);
            if ($user_list[$openid] === false) {
                return $this->error($wechatObj->getError());
            }
            $user_list[$openid]['tags'] = $wechatObj->getFanTagNames($user_list[$openid]['tagid_list']);
            if ($user_list[$openid]['tags'] === false) {
                return $this->error($wechatObj->getError());
            }
        }
        
        //TODO: 考虑黑名单！
        $page  = new Page($id_list['total'], 10000);
    	$show = $page->show();
        $this->assign('pager',$page);
        $this->assign('page',$show);
        $this->assign('user_list', $user_list);
        return $this->fetch();
    }
    
    public function fan_info()
    {
        $openid = I('get.id');
        $wechatObj = new WechatUtil($this->wx_user);
        $list = $wechatObj->getFanInfo($openid);
        if ($list === false) {
            return $this->error($wechatObj->getError());
        }
        
        $list['tags'] = $wechatObj->getFanTagNames($list['tagid_list']);
        if ($list['tags'] === false) {
            return $this->error($wechatObj->getError());
        }
        
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 处理发送的消息
     */
    public function send_msg() 
    {
        $msg = I('post.msg');//内容
        $type = I('post.type', 0);//个体or全体
        $openids = I('post.openids');//个体id
        
        $wechatObj = new WechatUtil($this->wx_user);
        if ($type == 1) {
            $result = $wechatObj->sendMsgToAll($msg);
        } else {
            $result = $wechatObj->sendMsg($openids, $msg);
        }

        if ($result === false) {
            return $this->ajaxReturn(['status'=>0,'msg'=>$wechatObj->getError()]);
        }

        return $this->ajaxReturn(['status'=>1,'msg'=>'已发送！']);
    }
    
    /**
     * 素材管理
     */
    public function material()
    {
        $wechatObj = new WechatUtil($this->wx_user);
        $news = $wechatObj->getMaterialList('news', 0, 20);
        if ($news === false) {
            return $this->error($wechatObj->getError());
        }

        $list = [];
        foreach ($news['item'] as $v) {
            $list[$v['media_id']] = $v['content']['news_item'][0];// 目前暂支持单消息
        }
        
        $page  = new Page($news['total_count'], 20);
        $this->assign('page',$page);
        $this->assign('list', $list);
        return $this->fetch();
    }
    
    /**
     * 初始化编辑器链接
     */
    private function init_editor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'wechat')));
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'wechat')));
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'wechat')));
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'wechat')));
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'wechat')));
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'wechat')));
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'wechat')));
        $this->assign("URL_Home", "");
    }
    
    /**
     * 素材编辑
     */
    public function material_editer()
    {
        $wechatObj = new WechatUtil($this->wx_user);
        $media_id = I('get.media_id', '');
        if ($media_id) {
            $news = $wechatObj->getNews($media_id);
            if ($news === false) {
                return $this->error($wechatObj->getError());
            }
            
            $news = $news[0];// 目前暂支持单消息
            $thumb = $wechatObj->getMaterial($news['thumb_media_id']);//获取封面
            if ($thumb === false) {
                return $this->error($wechatObj->getError());
            }
            $news['thumb'] = $thumb['down_url'];
            $news['media_id'] = $media_id;
        }

        $this->init_editor();
        $this->assign('news', $news);
        return $this->fetch();
    }
    
    /**
     * 提交素材（包括新建和更新素材）
     */
    public function submit_material()
    {
        $wechatObj = new WechatUtil($this->wx_user);
        
        // 目前暂支持单消息
        $media_id = I('post.media_id', '');
        $articles[0] = [
            "title" => I('post.title'),
            "thumb_media_id" => $media_id,
            "author" => I('post.author'),
            "digest" => I('post.digest'),
            "show_cover_pic" => I('post.show_cover_pic'),
            "content" => I('post.content'),
            "content_source_url" => I('post.content_source_url')
        ];
        
        /* 上传图片素材并替换掉原来的路径 */
        if (preg_match_all('#<img .*?src="(.*?)".*?/>#i', $articles[0]['content'], $matches)) {
            $imgs = array_unique($matches[1]);
            foreach ($imgs as $img) {
                $img_path = realpath(ltrim($img, '/'));
                $result = $wechatObj->uploadNewsImage($img_path);
                if ($result === false) {
                    return $this->error($wechatObj->getError());
                }
                str_replace($img, $result, $articles[0]['content']);
            }
        }
        
        if ($media_id) {
            $result = $wechatObj->updateNews($media_id, $articles[0], 0);
        } else {  
            $result = $wechatObj->uploadNews($articles);
        }
        
        if ($result === false) {
            return $this->error($wechatObj->getError());
        }
        return $this->success('素材提交成功！');
    }
}