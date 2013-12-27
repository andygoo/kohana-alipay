<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 类名：Kohana_AlipayNotify
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 * 
 * @author camry

 *************************注意*************************
 * 调试通知返回时，可查看或改写log日志的写入TXT里的数据，来检查通知返回是否正常
 */
class Kohana_AlipayNotify 
{
    /**
     * HTTPS形式消息验证地址
     */
	var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	
	/**
     * HTTP形式消息验证地址
     */
	var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
	
	var $alipay_config;

	function __construct($alipay_config)
	{
		$this->alipay_config = $alipay_config;
	}
	
	/**
	 * 创建一个新的AliplayNotify实例对象
	 * 
	 * @param $alipay_config 支付配置
	 */
	public static function instance(array $alipay_config = NULL)
	{
		if (NULL === $alipay_config) 
		{
			$alipay_config = Kohana::$config->load('alipay')->as_array();
		}
		return new AlipayNotify($alipay_config);
	}
	
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * 
     * @return 验证结果
     */
	function verifyNotify()
	{
		if(empty($_POST)) 
		{
			//判断POST来的数组是否为空
			return false;
		}
		else 
		{
			//生成签名结果
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_POST);
			//Func_AlipayCore::logResult($log_text);
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) 
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
	}
	
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * 
     * @return 验证结果
     */
	function verifyReturn()
	{
		if(empty($_GET)) 
		{
			//判断POST来的数组是否为空
			return false;
		}
		else 
		{
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n return_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_GET);
			//Func_AlipayCore::logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) 
			{
				return true;
			} 
			else 
			{
				return false;
			}
		}
	}
	
    /**
     * 获取返回时的签名验证结果
     * 
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序 网页暂时无用
     * @return 签名验证结果
     */
	function getSignVeryfy($para_temp, $sign, $isSort = false) 
	{
		//除去待签名参数数组中的空值和签名参数
		$para_filter = Func_AlipayCore::paraFilter($para_temp);
		
		//对待签名参数数组排序
		$para_sort = Func_AlipayCore::argSort($para_filter);
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = Func_AlipayCore::createLinkstring($para_sort);
		
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) 
		{
			case "MD5" :
				$isSgin = Func_AlipayMd5::md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * 
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) 
	{
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') 
		{
			$veryfy_url = $this->https_verify_url;
		}
		else 
		{
			$veryfy_url = $this->http_verify_url;
		}
		
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = Func_AlipayCore::getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
		
		return $responseTxt;
	}
}
