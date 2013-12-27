<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * 支付宝配置文件
 * 版本：3.3
 * 日期：2012-07-19
	
 * 提示：如何获取安全校验码和合作身份者id
 * 1.用您的签约支付宝账号登录支付宝网站(www.alipay.com)
 * 2.点击“商家服务”(https://b.alipay.com/order/myorder.htm)
 * 3.点击“查询合作者身份(pid)”、“查询安全校验码(key)”
	
 * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
 * 解决方法：
 * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
 * 2、更换浏览器或电脑，重新登录查询。
 */
return array
(
	'partner'	=>	'',	//合作身份者id，以2088开头的16位纯数字
	'key'		=>	'',	//安全检验码，以数字和字母组成的32位字符(如果签名方式设置为“MD5”时，请设置该参数)
	'private_key_path'	=>	getcwd().'/alipay_private_rsa_key.pem',	//商户的私钥（后缀是.pem）文件相对路径(如果签名方式设置为“0001”时，请设置该参数)
	'ali_public_key_path'	=>	getcwd().'/alipay_public_key.pem',	//支付宝公钥（后缀是.pem）文件相对路径(如果签名方式设置为“0001”时，请设置该参数)
	'sign_type'	=>	strtoupper('MD5'),	//签名方式(0001,MD5)
	'input_charset'	=>	strtolower('utf-8'),	//字符编码格式 目前支持 gbk 或 utf-8
	'cacert'	=>	getcwd().'/cacert.pem',	//ca证书路径地址，用于curl中ssl校验; 请保证cacert.pem文件在当前文件夹目录中
	'transport'	=>	'http'	//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
);