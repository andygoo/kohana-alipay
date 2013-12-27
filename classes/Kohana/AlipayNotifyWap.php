<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 类名：AlipayNotifyWap
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 * 
 * @author camry

 *************************注意*************************
 * 调试通知返回时，可查看或改写log日志的写入TXT里的数据，来检查通知返回是否正常
 */
class Kohana_AlipayNotifyWap extends AlipayNotify
{
	/**
	 * 创建一个新的AlipayNotifyWap实例对象
	 * 
	 * @param $alipay_config 支付配置
	 */
	public static function instance(array $alipay_config = NULL)
	{
		if (NULL === $alipay_config) 
		{
			$alipay_config = Kohana::$config->load('alipay')->as_array();
		}
		return new AlipayNotifyWap($alipay_config);
	}
	
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * 
     * @return 验证结果
     */
	function verifyNotify(){
		if(empty($_POST)) 
		{
			//判断POST来的数组是否为空
			return false;
		}
		else 
		{
			
			//对notify_data解密
			$decrypt_post_para = $_POST;
			if ($this->alipay_config['sign_type'] == '0001') {
				$decrypt_post_para['notify_data'] = Func_AlipayRsa::rsaDecrypt($decrypt_post_para['notify_data'], $this->alipay_config['private_key_path']);
			}
			
			//notify_id从decrypt_post_para中解析出来（也就是说decrypt_post_para中已经包含notify_id的内容）
			$doc = new DOMDocument();
			$doc->loadXML($decrypt_post_para['notify_data']);
			$notify_id = $doc->getElementsByTagName( "notify_id" )->item(0)->nodeValue;
			
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($notify_id)) {$responseTxt = $this->getResponse($notify_id);}
			
			//生成签名结果
			$isSign = $this->getSignVeryfy($decrypt_post_para, $_POST["sign"], false);
			
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
			//判断GET来的数组是否为空
			return false;
		}
		else 
		{
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"], true);
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "return_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_GET);
			//Func_AlipayCore::logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if ($isSign) 
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
     * 解密
     * 
     * @param $input_para 要解密数据
     * @return 解密后结果
     */
	function decrypt($prestr) 
	{
		return Func_AlipayRsa::rsaDecrypt($prestr, trim($this->alipay_config['private_key_path']));
	}
	
	/**
     * 异步通知时，对参数做固定排序
     * 
     * @param $para 排序前的参数组
     * @return 排序后的参数组
     */
	function sortNotifyPara($para) 
	{
		$para_sort['service'] = $para['service'];
		$para_sort['v'] = $para['v'];
		$para_sort['sec_id'] = $para['sec_id'];
		$para_sort['notify_data'] = $para['notify_data'];
		return $para_sort;
	}
	
    /**
     * 获取返回时的签名验证结果
     * 
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序
     * @return 签名验证结果
     */
	function getSignVeryfy($para_temp, $sign, $isSort = false) 
	{
		//除去待签名参数数组中的空值和签名参数
		$para = Func_AlipayCore::paraFilter($para_temp);
		
		//对待签名参数数组排序
		if($isSort) 
		{
			$para = Func_AlipayCore::argSort($para);
		} 
		else 
		{
			$para = $this->sortNotifyPara($para);
		}
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = Func_AlipayCore::createLinkstring($para);
		
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) 
		{
			case "MD5" :
				$isSgin = Func_AlipayMd5::md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			case "RSA" :
				$isSgin = Func_AlipayRsa::rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
				break;
			case "0001" :
				$isSgin = Func_AlipayRsa::rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}
}
