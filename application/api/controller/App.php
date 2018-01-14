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
 */ 
namespace app\api\controller;

/**
 * Description of App
 *
 */
class App extends Base
{
    /**
     * 获取最新的app
     */
    public function latest()
    {
        $inVersion = input('get.version', '0');
        if ($inVersion === null || $inVersion === '') {
            $this->ajaxReturn(['status' => -1, 'msg' => 'app版本号无效']);
        }
        
        $app = tpCache('mobile_app');
        if (strnatcasecmp($app['android_app_version'], $inVersion) > 0) {
            $this->ajaxReturn(['status' => 1, 'msg' => '有新版本', 'result' => [
                'new' => 1,
                'url' => SITE_URL.'/'.$app['android_app_path'],
                'log' => $app['android_app_log']
            ]]);
        }
        
        $this->ajaxReturn(['status' => 1, 'msg' => '无新版本', 'result' => ['new' => 0]]);
    }
    
}
