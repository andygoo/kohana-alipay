<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 支付宝通知处理类
 * 处理支付宝各接口通知返回
 * 
 * @author camry
 *
 */
class AlipayNotify extends Kohana_AlipayNotify 
{
    function AlipayNotify($alipay_config) 
    {
    	parent::__construct($alipay_config);
    }
}
