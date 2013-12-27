<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 功能：支付宝各接口请求提交类
 * 详细：构造支付宝各接口表单HTML文本，获取远程HTTP数据
 * 
 * @author camry
 *
 */
class AlipaySubmitWap extends Kohana_AlipaySubmitWap 
{
	function AlipaySubmitWap($alipay_config) 
    {
    	parent::__construct($alipay_config);
    }
}