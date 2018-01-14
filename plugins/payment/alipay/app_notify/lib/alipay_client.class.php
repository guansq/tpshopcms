<?php

/**
 * 新版支付宝支付签名
 */
class AopClient {
	//应用ID
	public $appId;
	
	//私钥文件路径
	public $rsaPrivateKeyFilePath;

	//私钥值
	public $rsaPrivateKey;

	//网关
	public $gatewayUrl = "https://openapi.alipay.com/gateway.do";
	//返回数据格式
	public $format = "json";
	//api版本
	public $apiVersion = "1.0";

	// 表单提交字符集编码
	public $postCharset = "UTF-8";

	//使用文件读取文件格式，请只传递该值
	public $alipayPublicKey = null;

	//使用读取字符串格式，请只传递该值
	public $alipayrsaPublicKey;


	public $debugInfo = false;

	private $fileCharset = "UTF-8";

	private $RESPONSE_SUFFIX = "_response";

	private $ERROR_RESPONSE = "error_response";

	private $SIGN_NODE_NAME = "sign";


	//加密XML节点名称
	private $ENCRYPT_XML_NODE_NAME = "response_encrypted";

	private $needEncrypt = false;


	//签名类型
	public $signType = "RSA";


	//加密密钥和类型

	public $encryptKey;

	public $encryptType = "AES";

	protected $alipaySdkVersion = "alipay-sdk-php-20161101";

	public function generateSign($params, $signType = "RSA") {
		return $this->sign($this->getSignContent($params), $signType);
	}

	public function rsaSign($params, $signType = "RSA") {
		return $this->sign($this->getSignContent($params), $signType);
	}

	public function getSignContent($params) {
		ksort($params);

		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

				// 转换成目标字符集
				$v = $this->characet($v, $this->postCharset);

				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		unset ($k, $v);
		return $stringToBeSigned;
	}

	protected function sign($data, $signType = "RSA") {
		if($this->checkEmpty($this->rsaPrivateKeyFilePath)){
			$priKey=$this->rsaPrivateKey;
			$res = !$priKey ? '' : 
                "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap($priKey, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
		}else {
			$priKey = file_get_contents($this->rsaPrivateKeyFilePath);
			$res = openssl_get_privatekey($priKey);
		}

		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置'); 

		if ("RSA2" == $signType) {
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
		} else {
			openssl_sign($data, $sign, $res);
		}

		if(!$this->checkEmpty($this->rsaPrivateKeyFilePath)){
			openssl_free_key($res);
		}
		$sign = base64_encode($sign);
		return $sign;
	}

    /**
     * 生成用于调用收银台SDK的字符串（新版本支付宝签名）
     * @param $request SDK接口的请求参数对象
     * @return string 
     * @author guofa.tgf
     */
	public function execute($biz_content, $notify_url = '') 
    {
		$params['app_id'] = $this->appId;
		$params['method'] = "alipay.trade.app.pay";
		$params['format'] = $this->format; 
		$params['sign_type'] = $this->signType;
		$params['timestamp'] = date("Y-m-d H:i:s");
		$params['alipay_sdk'] = $this->alipaySdkVersion;
		$params['charset'] = $this->postCharset;
		$params['version'] = $this->apiVersion;

		if ($notify_url) {
			$params['notify_url'] = $notify_url;
		}

		$params['biz_content'] = $biz_content;
		$params['sign'] = $this->generateSign($params, $this->signType);

		foreach ($params as &$value) {
			$value = $this->characet($value, $params['charset']);
		}
		
		return http_build_query($params);
	}

	/**
	 * 转换字符集编码
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	function characet($data, $targetCharset) {
		
		if (!empty($data)) {
			$fileType = $this->fileCharset;
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
				//				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
			}
		}


		return $data;
	}

	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	protected function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}

	/** rsaCheckV1 & rsaCheckV2
	 *  验证签名
	 *  在使用本方法前，必须初始化AopClient且传入公钥参数。
	 *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
	 **/
	public function rsaCheckV1($params, $rsaPublicKeyFilePath,$signType='RSA') {
		$sign = $params['sign'];
		$params['sign_type'] = null;
		$params['sign'] = null;
		return $this->verify($this->getSignContent($params), $sign, $rsaPublicKeyFilePath,$signType);
	}
	public function rsaCheckV2($params, $rsaPublicKeyFilePath, $signType='RSA') {
		$sign = $params['sign'];
		$params['sign'] = null;
		return $this->verify($this->getSignContent($params), $sign, $rsaPublicKeyFilePath, $signType);
	}

	function verify($data, $sign, $rsaPublicKeyFilePath, $signType = 'RSA') {

		if($this->checkEmpty($this->alipayPublicKey)){

			$pubKey= $this->alipayrsaPublicKey;
			$res = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap($pubKey, 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";
		}else {
			//读取公钥文件
			$pubKey = file_get_contents($rsaPublicKeyFilePath);
			//转换为openssl格式密钥
			$res = openssl_get_publickey($pubKey);
		}

		($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');  

		//调用openssl内置方法验签，返回bool值

		if ("RSA2" == $signType) {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else {
			$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		}

		if(!$this->checkEmpty($this->alipayPublicKey)) {
			//释放资源
			openssl_free_key($res);
		}

		return $result;
	}

/** 
	 *  在使用本方法前，必须初始化AopClient且传入公私钥参数。
	 *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
	 **/
	public function checkSignAndDecrypt($params, $rsaPublicKeyPem, $rsaPrivateKeyPem, $isCheckSign, $isDecrypt, $signType='RSA') {
		$charset = $params['charset'];
		$bizContent = $params['biz_content'];
		if ($isCheckSign) {
			if (!$this->rsaCheckV2($params, $rsaPublicKeyPem, $signType)) {
				echo "<br/>checkSign failure<br/>";
				exit;
			}
		}
		if ($isDecrypt) {
			return $this->rsaDecrypt($bizContent, $rsaPrivateKeyPem, $charset);
		}

		return $bizContent;
	}

	/** 
	 *  在使用本方法前，必须初始化AopClient且传入公私钥参数。
	 *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
	 **/
	public function encryptAndSign($bizContent, $rsaPublicKeyPem, $rsaPrivateKeyPem, $charset, $isEncrypt, $isSign, $signType='RSA') {
		// 加密，并签名
		if ($isEncrypt && $isSign) {
			$encrypted = $this->rsaEncrypt($bizContent, $rsaPublicKeyPem, $charset);
			$sign = $this->sign($encrypted, $signType);
			$response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$encrypted</response><encryption_type>RSA</encryption_type><sign>$sign</sign><sign_type>$signType</sign_type></alipay>";
			return $response;
		}
		// 加密，不签名
		if ($isEncrypt && (!$isSign)) {
			$encrypted = $this->rsaEncrypt($bizContent, $rsaPublicKeyPem, $charset);
			$response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$encrypted</response><encryption_type>$signType</encryption_type></alipay>";
			return $response;
		}
		// 不加密，但签名
		if ((!$isEncrypt) && $isSign) {
			$sign = $this->sign($bizContent, $signType);
			$response = "<?xml version=\"1.0\" encoding=\"$charset\"?><alipay><response>$bizContent</response><sign>$sign</sign><sign_type>$signType</sign_type></alipay>";
			return $response;
		}
		// 不加密，不签名
		$response = "<?xml version=\"1.0\" encoding=\"$charset\"?>$bizContent";
		return $response;
	}

	/** 
	 *  在使用本方法前，必须初始化AopClient且传入公私钥参数。
	 *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
	 **/
	public function rsaEncrypt($data, $rsaPublicKeyPem, $charset) {
		if($this->checkEmpty($this->alipayPublicKey)){
			//读取字符串
			$pubKey= $this->alipayrsaPublicKey;
			$res = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap($pubKey, 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";
		}else {
			//读取公钥文件
			$pubKey = file_get_contents($rsaPublicKeyFilePath);
			//转换为openssl格式密钥
			$res = openssl_get_publickey($pubKey);
		}

		($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确'); 
		$blocks = $this->splitCN($data, 0, 30, $charset);
		$chrtext  = null;
		$encodes  = array();
		foreach ($blocks as $n => $block) {
			if (!openssl_public_encrypt($block, $chrtext , $res)) {
				echo "<br/>" . openssl_error_string() . "<br/>";
			}
			$encodes[] = $chrtext ;
		}
		$chrtext = implode(",", $encodes);

		return base64_encode($chrtext);
	}

	/** 
	 *  在使用本方法前，必须初始化AopClient且传入公私钥参数。
	 *  公钥是否是读取字符串还是读取文件，是根据初始化传入的值判断的。
	 **/
	public function rsaDecrypt($data, $rsaPrivateKeyPem, $charset) {
		
		if($this->checkEmpty($this->rsaPrivateKeyFilePath)){
			//读字符串
			$priKey=$this->rsaPrivateKey;
			$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap($priKey, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
		}else {
			$priKey = file_get_contents($this->rsaPrivateKeyFilePath);
			$res = openssl_get_privatekey($priKey);
		}
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置'); 
		//转换为openssl格式密钥
		$decodes = explode(',', $data);
		$strnull = "";
		$dcyCont = "";
		foreach ($decodes as $n => $decode) {
			if (!openssl_private_decrypt($decode, $dcyCont, $res)) {
				echo "<br/>" . openssl_error_string() . "<br/>";
			}
			$strnull .= $dcyCont;
		}
		return $strnull;
	}
    
    private function setupCharsets($request) {
		if ($this->checkEmpty($this->postCharset)) {
			$this->postCharset = 'UTF-8';
		}
		$str = preg_match('/[\x80-\xff]/', $this->appId) ? $this->appId : print_r($request, true);
		$this->fileCharset = mb_detect_encoding($str, "UTF-8, GBK") == 'UTF-8' ? 'UTF-8' : 'GBK';
	}
}