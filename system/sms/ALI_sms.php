<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['server_url'] = 'http://www.alidayu.com/';
	
    $module['class_name']    = 'ALI';
    /* 名称 */
    $module['name']    = "阿里大于短信平台";
  
    if(ACTION_NAME == "install" || ACTION_NAME == "edit"){
		$lang = array(

		);
		$config = array(

		);
	    $module['lang']  = $lang;
	    $module['config'] = $config;
    }
    return $module;
}

// 企信通短信平台
require_once APP_ROOT_PATH."system/libs/sms.php";  //引入接口
require_once APP_ROOT_PATH."system/sms/ALI/TopSdk.php";
class ALI_sms implements sms
{
	public $sms;
	public $message = "";
	private $appkey;
	private $secretKey;

    public function __construct($smsInfo = '')
    {
		if(!empty($smsInfo))
		{			
			$this->sms = $smsInfo;
		}
    }
	
	public function sendSMS($mobile_number,$content,$is_adv=0)
	{
		$result = array('status'=>0,'msg'=>'');
		$appkey = $this->sms['user_name'];
		$secretKey = $this->sms['password'];
		//短信前缀
		$smsfreesignname = '皮蛋直播';
		//短信模板变量
		$product = '拾麦';

		if($appkey!=''&&$secretKey!=''){
			$content = str_replace('验证码为','',$content);
			$c = new TopClient;
			$c->appkey = $appkey;
			$c->secretKey = $secretKey;
			$req = new AlibabaAliqinFcSmsNumSendRequest;
			$req->setSmsType("normal");
			$req->setSmsFreeSignName($smsfreesignname);
			$req->setSmsParam("{\"code\":\"$content\",\"product\":\"$product\"}");
			$mobile_number = implode(',',$mobile_number);
			$req->setRecNum($mobile_number);
			$req->setSmsTemplateCode("SMS_71205006");
			$resp = $c->execute($req);
			$return = $this->object2array($resp);
			if($return['result']['err_code']==0&&$return['result']['success']){
				$result['status'] = 1;
				$result['msg'] = '发送成功';
			}else{
				$result['msg'] ='code:'.$return['code'].',sub_msg:'.$return['sub_msg'];
			}
		}else{
			$result['msg'] = 'appkey或secretKey为空！';
		}
		return $result;
	}
	
	public function getSmsInfo()
	{	

		return "阿里大于短信平台";
		
	}
	
	public function check_fee()
	{
		$result['info'] = '未开放查询';
		$str = $result['info'];

		return $str;
	}

	function object2array(&$object) {
		if (is_object($object)) {
			$arr = (array)($object);
		} else {
			$arr = &$object;
		}
		if (is_array($arr)) {
			foreach($arr as $varName => $varValue){
				$arr[$varName] = $this->object2array($varValue);
			}
		}
		return $arr;
	}
}
?>