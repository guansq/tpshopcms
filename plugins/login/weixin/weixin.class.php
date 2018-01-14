
<?php
/*
 * 微信扫码登陆插件
 * 扫码登陆，请先注册微信开放平台账号，然后创建网站应用
 * 特别注意：如果需要同步手机微信直接登陆账户，务必在开放平台绑定微信公众号
 */

class weixin{
	public $appid;
	public $secret;
	public $return_url;

	public function __construct($config){
		$this->appid = $config['appid'];
		$this->secret = $config['secret'];
		//$this->return_url = "http://".$_SERVER['HTTP_HOST']."/index.php/Home/ThirdLogin/callback/oauth/weixin";
		$this->return_url = "http://".$_SERVER['HTTP_HOST'].U('LoginApi/callback',array('oauth'=>'weixin'));
		
	}
	//构造要请求的参数数组，无需改动
	public function login(){
		header("Content-type: text/html; charset=utf-8"); 
		exit('请购买高级版支持此功能');
	}
 
}


?>