<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

interface sms{
	
	/**
	 * 发送短信
	 * @param array $mobile_number		手机号
	 * @param string $content		短信内容
	 * return array(status='',msg='')
	*/
	function sendSMS($mobile_number,$content);
	
	/*获取该短信接口的相关数据*/
	function getSmsInfo();
	
	function check_fee();
}
?>