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
 
use think\Verify;
use think\Db;
use app\common\logic\StoreLogic;
use think\Cookie;
use think\Image;

class Index extends Base
{
    public function index()
    {        
//         $cateList = array();
//         foreach ($this->cateTrre as $k => $v) {
//         	$cat_path = explode('_', $v['parent_id_path']);
//         	$v['hot_goods'] = M('goods')->field('goods_id,goods_name,shop_price')->where(array('cat_id1'=>$cat_path[1],'is_on_sale'=>1,'is_hot'=>1))->order('sort')->limit(10)->cache(true)->select();  
//             if($v['is_hot']){
//             	$cateList[] = $v;
//             }
//         }
        trace('测试一下我的');
        $web_list = S('web_index_data');
        if(!$web_list){
        	$web_list = M('web')->where(array('web_show'=>1))->order('web_sort')->select();
        	if($web_list){
        		foreach ($web_list as $kb=>$vb){
        			$block_list =  M('web_block')->where(array('web_id'=>$vb['web_id']))->order('web_id')->select();
        			if(is_array($block_list) && !empty($block_list)) {
        				foreach ($block_list as $key => $val) {//将变量输出到页面
        					$val['block_info'] = unserialize($val['block_info']);
        					$web_list[$kb][$val['var_name']] = $val['block_info'];
        				}
        			}
        		}
        		S('web_index_data',$web_list);
        	}
        }
        //halt($web_list);
        $this->assign('web_list', $web_list);
        return $this->fetch();
    }

    /**
     *  公告详情页
     */
    public function notice()
    {
        return $this->fetch();
    }
    
    public function qr_code_raw()
    {
        ob_end_clean();
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.tp-shop.cn/Home/Index/erweima/data/www.99soubao.com
        vendor('phpqrcode.phpqrcode');
        //import('Vendor.phpqrcode.phpqrcode');
        error_reporting(E_ERROR);
        $url = urldecode($_GET["data"]);
        \QRcode::png($url);
        exit;
    }
    
    // 二维码
    public function qr_code()
    {
        ob_end_clean();
        vendor('topthink.think-image.src.Image');
        vendor('phpqrcode.phpqrcode');

        error_reporting(E_ERROR);
        $url = isset($_GET['data']) ? $_GET['data'] : '';
        $url = urldecode($url);
        $head_pic = input('get.head_pic', '');
        $back_img = input('get.back_img', '');
        $valid_date = input('get.valid_date', 0);
        
        $qr_code_path = './public/upload/qr_code/';
        if (!file_exists($qr_code_path)) {
            mkdir($qr_code_path);
        }
        
        /* 生成二维码 */
        $qr_code_file = $qr_code_path.time().rand(1, 10000).'.png';
        \QRcode::png($url, $qr_code_file, QR_ECLEVEL_M);
        
        /* 二维码叠加水印 */
        $QR = Image::open($qr_code_file);
        $QR_width = $QR->width();
        $QR_height = $QR->height();

        /* 添加背景图 */
        if ($back_img && file_exists($back_img)) {
            $back =Image::open($back_img);
            $back->thumb($QR_width, $QR_height, \think\Image::THUMB_CENTER)
             ->water($qr_code_file, \think\Image::WATER_NORTHWEST, 60);//->save($qr_code_file);
            $QR = $back;
        }
        
        /* 添加头像 */
        if ($head_pic) {
            //如果是网络头像
            if (strpos($head_pic, 'http') === 0) {
                //下载头像
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $head_pic); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
                $file_content = curl_exec($ch);
                curl_close($ch);
                //保存头像
                if ($file_content) {
                    $head_pic_path = $qr_code_path.time().rand(1, 10000).'.png';
                    file_put_contents($head_pic_path, $file_content);
                    $head_pic = $head_pic_path;
                }
            }
            //如果是本地头像
            if (file_exists($head_pic)) {
                $logo = Image::open($head_pic);
                $logo_width = $logo->height();
                $logo_height = $logo->width();
                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = $logo_height / $scale;
                $logo_file = $qr_code_path.time().rand(1, 10000);
                $logo->thumb($logo_qr_width, $logo_qr_height)->save($logo_file, null, 100);
                $QR = $QR->thumb($QR_width, $QR_height)->water($logo_file, \think\Image::WATER_CENTER);     
                unlink($logo_file);
            }
            if ($head_pic_path) {
                unlink($head_pic_path);
            }
        }
        
        if ($valid_date && strpos($url, 'weixin.qq.com') !== false) {
            $QR = $QR->text('有效时间 '.$valid_date, "./vendor/topthink/think-captcha/assets/zhttfs/1.ttf", 7, '#00000000', Image::WATER_SOUTH);
        }
        $QR->save($qr_code_file, null, 100);
        
        $qrHandle = imagecreatefromstring(file_get_contents($qr_code_file));
        unlink($qr_code_file); //删除二维码文件
        header("Content-type: image/png");
        imagepng($qrHandle);
        imagedestroy($qrHandle);
        exit;
    }

    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';

        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
		exit();
    }


    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/26
     */
    public function street()
    {
        $sc_id = I('get.sc_id/d');
        $province = I('get.province', 0);
        $city = I('get.city', 0);
        $order = I('order', 0);
        $area = I('area');
        if (empty($province) && empty($city) && $area != 'all') {
            $province = Cookie::get('province_id');
            $city =  Cookie::get('city_id');
            $location_array = array('province' => $province, 'city' => $city);
            if(!empty($sc_id)){
                $location_array['sc_id'] = $sc_id;
            }
            $location = U('street', $location_array);
            $this->redirect($location);// 根据城市来帅选
        }
        $store_class = M('store_class')->cache(true)->field('sc_id,sc_name')->where('')->select();
        $store_logic = new StoreLogic();
        $store_list = $store_logic->getStoreList($sc_id, $province, $city, $order, 10);
        $region = M('region')->cache(true)->where("`level` = 1")->getField("id,name");
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('region', $region);
        $this->assign('page', $store_list['show']);// 赋值分页输出
        $this->assign('pages', $store_list['pages']);
        $this->assign('store_list', $store_list['result']);
        $this->assign('store_class', $store_class);//店铺分类
        return $this->fetch();
    }

    public function store_qrcode()
    {
        require_once 'vendor/phpqrcode/phpqrcode.php';

        error_reporting(E_ERROR);
        $store_id = I('store_id/d', 1);
        \QRcode::png(U('Mobile/Store/index', array('store_id' => $store_id), true, true));
    }

    /**
     * 使用步骤:
     * 1.将该函数的注释放开
     * 2.浏览器请求该函数, 将打印输出的SQL语句在MYSQL中执行即可清理数据
     *    以下变量: $database 是你的数据库名 
     * 3.执行完成之后将该函数注释起来
     * 注意: 如果执行该函数, 没有输出表名, 请检查你的数据库名是否正确
     */
    /* function truncate_tables()
    {
        $tables = DB::query("show tables");
        $database = "tpshopbbc2.0";   //你的数据库名
        $k_name = "Tables_in_$database";
        $table = array('tp_admin', 'tp_config', 'tp_region', 'tp_system_module', 'tp_admin_role', 'tp_system_menu', 'tp_store_grade', 'tp_article_cat','tp_wx_user');
        foreach ($tables as $key => $val) {
           if(!in_array($val[$k_name], $table)){
               echo 'truncate table'.$val[$k_name].";";
               echo "<br/>";         
           }
        }
        // 清空完之后 执行下面两个sql 插入自营店 和 用户
        echo "insert  into `tp_store`(`store_id`,`store_name`,`grade_id`,`user_id`,`user_name`,`seller_name`,`sc_id`,`company_name`,`province_id`,`city_id`,`district`,`store_address`,`store_zip`,`store_state`,`store_close_info`,`store_sort`,`store_rebate_paytime`,`store_time`,`store_end_time`,`store_logo`,`store_banner`,`store_avatar`,`seo_keywords`,`seo_description`,`store_aliwangwang`,`store_qq`,`store_phone`,`store_zy`,`store_domain`,`store_recommend`,`store_theme`,`store_credit`,`store_desccredit`,`store_servicecredit`,`store_deliverycredit`,`store_collect`,`store_slide`,`store_slide_url`,`store_printdesc`,`store_sales`,`store_presales`,`store_aftersales`,`store_workingtime`,`store_free_price`,`store_warning_storage`,`store_decoration_switch`,`store_decoration_only`,`is_own_shop`,`bind_all_gc`,`qitian`,`certified`,`returned`,`store_free_time`,`mb_slide`,`mb_slide_url`,`deliver_region`,`cod`,`two_hour`,`ensure`,`deposit`,`deposit_icon`,`store_money`,`pending_money`,`deleted`,`goods_examine`,`service_phone`) values (1,'TPSHP旗舰店',1,1,'wyp001','wyp002',2,'深圳搜豹网络有限公司',28240,28626,28646,'上梅林中康创业园7楼735','',1,NULL,0,'1463979921',0,NULL,'/public/upload/seller/2017/02-16/58a54113dcda5.jpg','/public/upload/seller/2017/02-16/58a547417999b.png','/public/upload/seller/2017/02-16/58a54a810aa75.png','','','','','','女装,桑德菲杰',NULL,0,'style3',0,4.83,4.67,4.83,1,'/public/upload/seller/2017/01-07/58707ff4e3c3c.jpg,/public/upload/seller/2016/10-28/5812acda24797.png,/public/upload/seller/2016/10-28/5812acdee9132.png,,','http://,http://,http://,http://,http://',NULL,0,NULL,NULL,'工作时间: 周一到周日10:00~23:00',1000.00,100,0,0,1,1,0,0,0,NULL,'/public/upload/seller/2017/01-04/586c92a1d7ff6.jpg,/public/upload/seller/2017/01-04/586c930207fdd.png,,,','http://,http://,http://,http://,http://','8 131 1700|黑龙江 齐齐哈尔市 富拉尔基区',0,0,0,0.00,0,31220.68,-532.35,0,0,'15889560679');";
        echo "insert into `tp_users` (`user_id`, `email`, `password`, `sex`, `birthday`, `user_money`, `frozen_money`, `distribut_money`, `pay_points`, `paypwd`, `reg_time`, `last_login`, `last_ip`, `qq`, `mobile`, `mobile_validated`, `oauth`, `openid`, `unionid`, `head_pic`, `bank_name`, `bank_card`, `realname`, `idcard`, `email_validated`, `nickname`, `level`, `discount`, `total_amount`, `is_lock`, `is_distribut`, `first_leader`, `second_leader`, `third_leader`, `token`) values(62, '511482696@qq.com', '519475228fe35ad067744465c42a19b2', 2, 1480521600, '0.00', '0.00', '1000.00', 1288, '1', 1245048540, 1484277061, '0.0.0.0', '', '13800138006', 1, '', null, null, 'http://imgsrc.baidu.com/forum/pic/item/e4dde71190ef76c6eae6f0fb9d16fdfaaf51672d.jpg', '0', '0', '0', null, 1, '测试人员', 3, '0.95', '43574.91', 0, 1, 2, 5, 13, 'cc8e38f888d9ff221674830adf718049');";
    } */

    /**
     * 猜你喜欢
     * @author dyr
     */
    public function ajax_favorite()
    {
        $p = I('p', 1);
        $item = I('i', 5);
        $tpl = I('tpl');
        $goods_where = array('g.is_recommend' => 1, 'g.is_on_sale' => 1, 'g.goods_state' => 1);
        $favourite_goods = M('goods')->alias('g')->join('__STORE__ s' ,'g.store_id = s.store_id')
            ->field('g.*,s.store_name')
            ->where($goods_where)
            ->order('sort DESC')
            ->page($p, $item)
            ->cache(true, TPSHOP_CACHE_TIME)
            ->select();
        $this->assign('favourite_goods', $favourite_goods);
        if ($tpl) {
            return $this->fetch($tpl);
        } else {
            return $this->fetch();
        }
    }

}