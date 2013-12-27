<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 类名：AlipaySubmitWap
 * 功能：支付宝各接口请求提交类
 * 详细：构造支付宝各接口表单HTML文本，获取远程HTTP数据
 * 版本：3.3
 * 日期：2012-07-23
 * 
 * @author camry
 */
class Kohana_AlipaySubmitWap extends AlipaySubmit 
{
	/**
	 *支付宝网关地址
	 */
	//var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
	var $alipay_gateway_new = 'http://wappaygw.alipay.com/service/rest.htm?';

	/**
	 * 创建一个新的AlipaySubmitWap实例对象
	 * 
	 * @param $alipay_config 支付配置
	 */
	public static function instance(array $alipay_config = NULL)
	{
		if (NULL === $alipay_config) 
		{
			$alipay_config = Kohana::$config->load('alipay')->as_array();
		}
		return new AlipaySubmitWap($alipay_config);
	}
	
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	function buildRequestMysign($para_sort) 
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = Func_AlipayCore::createLinkstring($para_sort);
		
		$mysign = "";
		switch (strtoupper(trim($this->alipay_config['sign_type']))) 
		{
			case "MD5" :
				$mysign = Func_AlipayMd5::md5Sign($prestr, $this->alipay_config['key']);
				break;
			case "RSA" :
				$mysign = Func_AlipayRsa::rsaSign($prestr, $this->alipay_config['private_key_path']);
				break;
			case "0001" :
				$mysign = Func_AlipayRsa::rsaSign($prestr, $this->alipay_config['private_key_path']);
				break;
			default :
				$mysign = "";
		}
		
		return $mysign;
	}

	/**
     * 生成要请求给支付宝的参数数组
     * 
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	function buildRequestPara($para_temp) 
	{
		//除去待签名参数数组中的空值和签名参数
		$para_filter = Func_AlipayCore::paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = Func_AlipayCore::argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort);
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		if($para_sort['service'] != 'alipay.wap.trade.create.direct' && $para_sort['service'] != 'alipay.wap.auth.authAndExecute') 
		{
			$para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
		}
		
		return $para_sort;
	}
	
	/**
     * 解析远程模拟提交后返回的信息
     * 
	 * @param $str_text 要解析的字符串
     * @return 解析结果
     */
	function parseResponse($str_text) 
	{
		//以“&”字符切割字符串
		$para_split = explode('&',$str_text);
		//把切割后的字符串数组变成变量与数值组合的数组
		foreach ($para_split as $item) 
		{
			//获得第一个=字符的位置
			$nPos = strpos($item,'=');
			//获得字符串长度
			$nLen = strlen($item);
			//获得变量名
			$key = substr($item,0,$nPos);
			//获得数值
			$value = substr($item,$nPos+1,$nLen-$nPos-1);
			//放入数组中
			$para_text[$key] = $value;
		}
		
		if( ! empty ($para_text['res_data'])) 
		{
			//解析加密部分字符串
			if($this->alipay_config['sign_type'] == '0001') 
			{
				$para_text['res_data'] = Func_AlipayRsa::rsaDecrypt($para_text['res_data'], $this->alipay_config['private_key_path']);
			}
			
			//token从res_data中解析出来（也就是说res_data中已经包含token的内容）
			$doc = new DOMDocument();
			$doc->loadXML($para_text['res_data']);
			$para_text['request_token'] = $doc->getElementsByTagName( "request_token" )->item(0)->nodeValue;
		}
		
		return $para_text;
	}
}
