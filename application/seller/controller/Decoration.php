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
 * Date: 2016-05-29
 */

namespace app\seller\controller;

use app\seller\model\StoreDecoration;
use think\Page;

class Decoration extends Base
{
    public function store_decoration()
    {
        return $this->fetch();
    }


    public function block_add()
    {
        $decoration_id = intval($_POST['decoration_id']);
        $block_layout = 'block_1';//$_POST['block_layout'];

        $data = array();

        $model_store_decoration = new StoreDecoration();
        //验证装修编号
        $condition = array();
        $condition['decoration_id'] = $decoration_id;
        $decoration_info = $model_store_decoration->getStoreDecorationInfo($condition, $_SESSION['store_id']);
        if (!$decoration_info) {

        }

        //验证装修块布局
        $block_layout_array = $model_store_decoration->getStoreDecorationBlockLayoutArray();
        if (!in_array($block_layout, $block_layout_array)) {
            $data['error'] = L('param_error');
            respose($data);
        }

        $param = array();
        $param['decoration_id'] = $decoration_id;
        $param['store_id'] = $_SESSION['store_id'];
        $param['block_layout'] = $block_layout;
        $block_id = $model_store_decoration->addStoreDecorationBlock($param);
        $control_flag = 1;
        $this->assign('control_flag', $control_flag);
        $block_title = empty($control_flag) ? '' : '上下拖拽布局块位置可改变排列顺序，无效的可删除。<br/>编辑布局块内容请点击“编辑模块”并选择操作。';
        $this->assign('block_title', $block_title);
        if ($block_id) {
            ob_start();
            $this->assign('block', array('block_id' => $block_id));
            echo $this->fetch('store_decoration_block');
            $temp = ob_get_contents();
            ob_end_clean();
            $data['message'] = '添加成功';
            $data['html'] = $temp;
        } else {
            $data['error'] = '添加失败';
        }
        respose($data);
    }

    /**
     * 装修块保存
     */
    public function block_save()
    {
        $block_id = intval($_POST['block_id']);
        $module_type = $_POST['module_type'];
        $data = array();
        $model_store_decoration = new StoreDecoration();
        //验证模块类型
        $block_type_array = $model_store_decoration->getStoreDecorationBlockTypeArray();
        if (!in_array($module_type, $block_type_array)) {
            $data['error'] = L('param_error');
            echo json_encode($data);
        }
        switch ($module_type) {
            case 'html':
                $content = htmlspecialchars($_POST['content']);
                break;
            default:
                $content = serialize($_POST['content']);
        }

        $condition = array();
        $condition['block_id'] = $block_id;
        $condition['store_id'] = $_SESSION['store_id'];

        $param = array();
        $param['block_content'] = $content;
        $param['block_full_width'] = intval($_POST['full_width']);
        $param['block_module_type'] = $module_type;

        $result = $model_store_decoration->editStoreDecorationBlock($param, $condition);
        if ($result) {
            $data['message'] = '保存成功';
            ob_start();
            $this->assign('block_content', $content);
//            return $this->fetch('decoration_module_' . $module_type);
            $data['html'] = ob_get_contents();
            ob_end_clean();
        } else {
            $data['error'] = '保存失败';
        }
        respose($data);
    }

    /**
     * 装修块删除
     */
    public function block_del()
    {
        $block_id = intval($_POST['block_id']);
        $data = array();
        $model_store_decoration = new StoreDecoration();
        $condition = array();
        $condition['block_id'] = $block_id;
        $condition['store_id'] = $_SESSION['store_id'];
        $result = $model_store_decoration->delStoreDecorationBlock($condition);
        if ($result) {
            $data['message'] = '删除成功';
        } else {
            $data['error'] = '删除失败';
        }
        respose($data);
    }

    /**
     * 店铺装修设置
     */
    public function decoration_setting()
    {
        $model_store_decoration = new StoreDecoration();

        $store_decoration_info = $model_store_decoration->getStoreDecorationInfo(array('store_id' => $_SESSION['store_id']));
        if (empty($store_decoration_info)) {
            //创建默认装修
            $param = array();
            $param['decoration_name'] = '默认装修';
            $param['store_id'] = $_SESSION['store_id'];
            $decoration_id = $model_store_decoration->addStoreDecoration($param);
        } else {
            $decoration_id = $store_decoration_info['decoration_id'];
        }

        $this->assign('store_decoration_switch', $this->store_info['store_decoration_switch']);
        $this->assign('store_decoration_only', $this->store_info['store_decoration_only']);
        $this->assign('decoration_id', $decoration_id);

        $this->profile_menu('decoration_setting');
        return $this->fetch('store_decoration.setting');
    }

    /**
     * 店铺装修设置保存
     */
    public function decoration_setting_save()
    {
        $model_store_decoration = new StoreDecoration();
        $model_store = M('store');

        $store_decoration_info = $model_store_decoration->getStoreDecorationInfo(array('store_id' => $_SESSION['store_id']));
        if (empty($store_decoration_info)) {
            $this->error('参数错误');
        }

        $update = array();
        if (empty($_POST['store_decoration_switch'])) {
            $update['store_decoration_switch'] = 0;
        } else {
            $update['store_decoration_switch'] = $store_decoration_info['decoration_id'];
        }
        $update['store_decoration_only'] = intval($_POST['store_decoration_only']);
        $result = $model_store->editStore($update, array('store_id' => $_SESSION['store_id']));
        if ($result) {
            $this->error(L('nc_common_save_succ'), '', 'succ');
        } else {
            $this->error(L('nc_common_save_fail'));
        }
    }

    /**
     * 装修图库列表
     */
    public function decoration_album()
    {
        /*
        $model_store_decoration_album = Model('store_decoration_album');

        $condition = array();
        $condition['store_id'] = $_SESSION['store_id'];

        $image_list = $model_store_decoration_album->getStoreDecorationAlbumList($condition, 24, 'upload_time desc');
        Tpl::output('image_list', $image_list);
        Tpl::output('show_page',$model_store_decoration_album->showpage());

        $this->profile_menu('decoration_album');
        Tpl::showpage('store_decoration.album');
        */
    }

    /**
     * 图片上传
     */
    public function album_upload()
    {
        $store_id = $_SESSION ['store_id'];
        $data = array();
        //判断装修相册数量限制，预设100
        if ($this->store_info['store_decoration_image_count'] > 100) {
            $data['error'] = '相册已满，请首先删除无用图片';
            echo json_encode($data);
            die;
        }
        //上传图片
//        $upload = new \Think\Upload();//实例化上传类
//        $upload->maxSize = 1024 * 1024 * 3;//设置附件上传大小 管理员10M  否则 3M
//        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
//        $upload->savePath = 'store/decoration/'; // 设置附件上传根目录
//        $upload->replace = true; //存在同名文件是否是覆盖，默认为false
//        $upinfo = $upload->upload($_FILES);

        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $path = ROOT_PATH . 'public/upload/store/decoration/';
        $upinfo = $file->rule('uniqid')->validate(['size'=>1024 * 1024 * 3,'ext'=>'jpg,gif,png,jpeg'])->move($path);
        /*
        if($upinfo) {
            $imgpath = '/public/upload/store/decoration/'.$upinfo['file']['savepath'].$upinfo['file']['savename'];
        } else {
            $data['error'] = $upload->getError();
            echo json_encode($data);die;
        }
        //图片尺寸
        list($width, $height) = getimagesize($imgpath);
        //图片原始名称
        $image_origin_name_array = explode('.', $_FILES["file"]["name"]);
        //插入相册表
        $param = array();
        $param['image_name'] = $imgpath;
        $param['image_origin_name'] = $image_origin_name_array['0'];
        $param['image_width'] = $width;
        $param['image_height'] = $height;
        $param['image_size'] = intval($_FILES['file']['size']);
        $param['store_id'] = $store_id;
        $param['upload_time'] = TIMESTAMP;
        $result = 1;//M('store_decoration_album')->addStoreDecorationAlbum($param);
        */
        if ($upinfo) {
            //装修相册计数加1
            //M('store')->editStore(array('store_decoration_image_count' => array('exp', 'store_decoration_image_count+1')),array('store_id' => $_SESSION['store_id']));
            $data['image_name'] = $upinfo->getFilename();
//            $data['image_url'] = '/public/upload/' . $upinfo['file']['savepath'] . $upinfo['file']['savename'];
            $data['image_url'] = '/public/upload/store/decoration/'.$upinfo->getFilename();
        } else {
            $data['error'] = '上传失败';
        }
        echo json_encode($data);
        die;
    }

    /**
     * 图片删除
     */
    public function decoration_album_delOp()
    {
        $image_id = intval($_POST['image_id']);
        $data = array();
        $model_store_decoration_album = new StoreDecoration();
        //验证图片权限
        $condition = array();
        $condition['image_id'] = $image_id;
        $condition['store_id'] = $_SESSION['store_id'];
        $result = $model_store_decoration_album->delStoreDecorationAlbum($condition);
        if ($result) {
            //装修相册计数减1
            if ($this->store_info['store_decoration_image_count'] > 0) {
                M('store')->where(array('store_id' => $_SESSION['store_id']))->save(
                    array('store_decoration_image_count' => array('exp', 'store_decoration_image_count-1'))
                );
            }

            $data['message'] = '删除成功';
        } else {
            $data['error'] = '删除失败';
        }
        echo json_encode($data);
        die;
    }

    /**
     * 店铺装修
     */
    public function decoration_edit()
    {
        $decoration_id = intval($_GET['decoration_id']);
        $model_store_decoration = new StoreDecoration();
        $decoration_info = $model_store_decoration->getStoreDecorationInfoDetail($decoration_id, $_SESSION['store_id']);
        if ($decoration_info) {
            $this->_output_decoration_info($decoration_info);
        } else {
            //showMessage(L('param_error'), '', 'error');
            $this->error('您还没有开启店铺装修', U('Index/index'));
        }
        $this->assign('control_flag', 1);
        $this->assign('store_id', $_SESSION['store_id']);
        $this->assign('decoration_id', $decoration_id);
        $this->assign('block_title', '上下拖拽布局块位置可改变排列顺序，无效的可删除。<br/>编辑布局块内容请点击“编辑模块”并选择操作。');
        //设定模板为完成宽度
        $this->assign('seller_layout_no_menu', true);
        return $this->fetch();
    }

    /**
     * 输出装修设置
     */
    private function _output_decoration_info($decoration_info)
    {
        //dump($decoration_info);
        $model_store_decoration = new StoreDecoration();
        $decoration_background_style = $model_store_decoration->getDecorationBackgroundStyle($decoration_info['decoration_setting']);
        $this->assign('decoration_background_style', $decoration_background_style);
        $this->assign('decoration_nav', $decoration_info['decoration_nav']);
        $this->assign('decoration_banner', $decoration_info['decoration_banner']);
        //dump($decoration_info['decoration_setting']);
        $this->assign('decoration_setting', $decoration_info['decoration_setting']);
        $this->assign('block_list', $decoration_info['block_list']);
    }

    /**
     * 保存店铺装修背景设置
     */
    public function background_setting_save()
    {
        $decoration_id = intval($_POST['decoration_id']);

        //验证参数
        if ($decoration_id <= 0) {
            $data['error'] = L('param_error');
            echo json_encode($data);
            die;
        }

        $setting = array();
        $setting['background_color'] = $_POST['background_color'];
        $setting['background_image'] = $_POST['background_image'];
        $setting['background_image_url'] = $_POST['background_image_url'];
        $setting['background_image_repeat'] = $_POST['background_image_repeat'];
        $setting['background_position_x'] = $_POST['background_position_x'];
        $setting['background_position_y'] = $_POST['background_position_y'];
        $setting['background_attachment'] = $_POST['background_attachment'];
        //背景设置保存验证
        $validate_setting = $this->_validate_background_setting_input($decoration_id, $setting);
        if (isset($validate_setting['error'])) {
            $data['error'] = $validate_setting['error'];
            echo json_encode($data);
            die;
        }

        $data = array();

        $model_store_decoration = new StoreDecoration();

        $condition = array();
        $condition['decoration_id'] = $decoration_id;
        $condition['store_id'] = $_SESSION['store_id'];

        $update = array();
        $update['decoration_setting'] = serialize($setting);
        //dump($setting);
        $result = $model_store_decoration->editStoreDecoration($update, $condition);
        if ($result) {
            $data['message'] = '保存成功';
            $data['decoration_background_style'] = $model_store_decoration->getDecorationBackgroundStyle($validate_setting);
        } else {
            $data['error'] = '保存失败';
        }
        echo json_encode($data);
        die;
    }

    /**
     * 背景设置保存验证
     */
    private function _validate_background_setting_input($decoration_id, $setting)
    {
        //验证输入
        if ($decoration_id <= 0) {
            return array('error', L('param_error'));
        }
        if (!empty($setting['background_color'])) {
            if (strlen($setting['background_color']) > 7) {
                return array('error', '请输入正确的背景颜色');
            }
        } else {
            $setting['background_color'] = '';
        }
        if (!in_array($setting['background_image_repeat'], array('no-repeat', 'repeat', 'repeat-x', 'repeat-y'))) {
            $setting['background_image_repeat'] = '';
        }
        if (strlen($setting['background_position_x']) > 8) {
            $setting['background_position_x'] = '';
        }
        if (strlen($setting['background_position_y']) > 8) {
            $setting['background_position_y'] = '';
        }
        if (strlen($setting['background_attachment']) > 8) {
            $setting['background_attachment'] = '';
        }
        return $setting;
    }

    /**
     * 装修导航保存
     */
    public function nav_save()
    {
        $decoration_id = intval($_POST['decoration_id']);
        $nav = array();
        $nav['display'] = $_POST['nav_display'];
        $nav['style'] = $_POST['content'];

        $data = array();
        //验证参数
        if ($decoration_id <= 0) {
            $data['error'] = '参数错误';
            echo json_encode($data);
            die;
        }

        $model_store_decoration = new StoreDecoration();
        $condition = array();
        $condition['decoration_id'] = $decoration_id;
        $condition['store_id'] = $_SESSION['store_id'];

        $update = array();
        $update['decoration_nav'] = serialize($nav);

        $result = $model_store_decoration->editStoreDecoration($update, $condition);
        if ($result) {
            $data['message'] = '保存成功';
        } else {
            $data['error'] = '保存失败';
        }
        echo json_encode($data);
        die;
    }

    /**
     * 装修banner保存
     */
    public function banner_save()
    {
        $decoration_id = intval($_POST['decoration_id']);
        $banner = array();
        $banner['display'] = $_POST['banner_display'];
        $banner['image'] = $_POST['content'];
        $data = array();

        //验证参数
        if ($decoration_id <= 0) {
            $data['error'] = L('param_error');
            echo json_encode($data);
            die;
        }

        $model_store_decoration = new StoreDecoration();
        $condition = array();
        $condition['decoration_id'] = $decoration_id;
        $condition['store_id'] = $_SESSION['store_id'];
        $update = array();
        $update['decoration_banner'] = serialize($banner);

        $result = $model_store_decoration->editStoreDecoration($update, $condition);
        if ($result) {
            $data['message'] = '保存成功';
            $data['image_url'] = $banner['image'];
        } else {
            $data['error'] = '没有修改数据，保存失败';
        }
        echo json_encode($data);
        die;
    }

    /**
     * 装修块排序
     */
    public function block_sort()
    {
        $sort_array = explode(',', rtrim($_POST['sort_string'], ','));
        $model_store_decoration = new StoreDecoration();
        $condition = array();
        $condition['store_id'] = $_SESSION['store_id'];
        $sort = 1;
        foreach ($sort_array as $value) {
            $condition['block_id'] = $value;
            $model_store_decoration->editStoreDecorationBlock(array('block_sort' => $sort), $condition);
            $sort = $sort + 1;
        }
        $data = array();
        $data['message'] = '保存成功';
        respose($data);
    }

    /**
     * 商品搜索
     */
    public function goods_search()
    {
        $model_goods = M('goods');
        $condition = array();
        $condition['store_id'] = $_SESSION['store_id'];
        $condition['is_on_sale'] = 1;
        if (!empty($_GET['keyword'])) {
            $condition['goods_name'] = array('like', '%' . $_GET['keyword'] . '%');
        }
        $count = $model_goods->where($condition)->count();
        $Page = new Page($count, 10);
        $goods_list = $model_goods->where($condition)->limit($Page->firstRow . ',' . $Page->listRows)->order('goods_id desc')->select();
        $this->assign('goods_list', $goods_list);
        $show = $Page->show();
        $this->assign('show_page', $show);
        echo $this->fetch();
    }

    /**
     * 装修预览
     */
    public function decoration_preview()
    {
        $decoration_id = I('decoration_id/d');

        $model_store_decoration = new StoreDecoration();

        $decoration_info = $model_store_decoration->getStoreDecorationInfoDetail($decoration_id, $_SESSION['store_id']);
        if ($decoration_info) {
            $this->_output_decoration_info($decoration_info);
        } else {
            //showMessage(L('param_error'), '', 'error');
        }
        //店铺信息
        $store_info = M('store')->where(array('store_id' => $_SESSION['store_id']))->find();
        $this->assign('store_info', $store_info);
        $this->assign('store_theme', 'default');
        //Tpl::setLayout('store_layout');
        return $this->fetch();
    }

    /**
     * 装修静态文件生成
     */
    public function decoration_build()
    {
        //静态文件路径
        $html_path = BASE_UPLOAD_PATH . DS . ATTACH_STORE . DS . 'decoration' . DS . 'html' . DS;
        if (!is_dir($html_path)) {
            if (!@mkdir($html_path, 0755)) {
                $data = array();
                $data['error'] = '页面生成失败';
                echo json_encode($data);
                die;
            }
        }
        $decoration_id = intval($_GET['decoration_id']);
        //更新商品数据
        $this->_update_module_goods_info($decoration_id, $_SESSION['store_id']);

        $model_store_decoration = new StoreDecoration();

        $decoration_info = $model_store_decoration->getStoreDecorationInfoDetail($decoration_id, $_SESSION['store_id']);
        if ($decoration_info) {
            $this->_output_decoration_info($decoration_info);
        } else {
            //showMessage(L('param_error'), '', 'error');
        }

        $file_name = md5($_SESSION['store_id']);

        ob_start();
        return $this->fetch('decoration_preview', 'null_layout');
        $result = file_put_contents($html_path . $file_name . '.html', ob_get_clean());
        if ($result) {
            $data['message'] = '页面生成成功';
        } else {
            $data['error'] = '页面生成失败';
        }
        echo json_encode($data);
        die;
    }

}