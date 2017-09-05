<?php

$payment_lang = array(
	'name'	=>	'微信支付',
	'wxapp_appid'	=>	'开放平台账号AppID',
	'wxapp_partnerid'	=>'商户号Partnerid',
	'wxapp_key'	=>	'密钥KEY',
	'wxapp_secret'	=>	'AppSecret',
);
$config = array(
	'wxapp_appid'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), 
		
	'wxapp_partnerid'	=>	array(
				'INPUT_TYPE'	=>	'0'
		),

	'wxapp_key'	=>	array(
		'INPUT_TYPE'	=>	'0'
	),

	'wxapp_secret'	=>	array(
		'INPUT_TYPE'	=>	'0'
	)		
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'WxApp';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


    /* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '3';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    $module['reg_url'] = '';
    return $module;
}

// 支付宝手机支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class WxApp_payment implements payment {

	public function get_payment_code($payment_notice_id)
	{
		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		//log_result("==payment_notice==");
		//log_result($payment_notice);
		//$order_sn = $GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
		$money = round($payment_notice['money'],2);
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);
		//log_result("==payment_info==");
		//log_result($payment_info);
		$this->init_define($payment_info);
					
		/*$sql = "select name ".
						  "from ".DB_PREFIX."deal_order_item ".					
						  "where order_id =". intval($payment_notice['order_id']);
		$title_name = $GLOBALS['db']->getOne($sql);*/
		
		$m_config =  load_auto_cache("m_config");
		$title_name = $m_config['ticket_name'];
		if($title_name=='')
		$title_name = '虚拟印币';
		
		if(empty($title_name))
		{
			$title_name = "充值".round($payment_notice['money'],2)."元";
		}
		
		$pay['pay_info'] = $title_name;
		$pay['payment_name'] = "微信支付";
        $pay['pay_money'] = $money;
		$pay['pay_id'] = $payment_notice['id'];
		$pay['class_name'] = "WxApp";
		
		
		//$subject = msubstr($title_name,0,40);
		$subject = $title_name;
		//$data_return_url = get_domain().APP_ROOT.'/../payment.php?act=return&class_name=Malipay';
		//$notify_url = get_domain().APP_ROOT.'/../shop.php?ctl=payment&act=response&class_name=Malipay';
		$data_notify_url = SITE_DOMAIN.APP_ROOT.'/callback/payment/wxapp_notify.php';

		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Api.php');
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Notify.php');
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Data.php');
		
		//统一下单
		$input = new WxPayUnifiedOrder();
		$input->SetAppid($payment_info['config']['wxapp_appid']);
		$input->SetMch_id($payment_info['config']['wxapp_partnerid']);		
		$input->SetBody($payment_notice['notice_sn']);
		$input->SetOut_trade_no($payment_notice['notice_sn']);
		$input->SetTotal_fee($money * 100);
		//$input->SetTime_start(to_date(get_gmtime(),"YmdHis"));
		//$input->SetTime_expire(date("YmdHis", time() + 600));
		//$input->SetGoods_tag($title_name);
		$input->SetNotify_url($data_notify_url);
		$input->SetTrade_type("APP");

		$result = WxPayApi::unifiedOrder($input);
		//log_result("==result==");
		//log_result($result);
		//print_r($result);
		//exit;
		//json_encode($result);
				

		
		/*
		$pay['appid'] = $payment_info['config']['wxapp_appid'];
		$pay['partnerid'] = $payment_info['config']['wxapp_partnerid'];
		$pay['prepayid'] = $result['prepayid'];//预支付交易会话ID
		$pay['package'] = 'Sign=WXPay';//暂填写固定值Sign=WXPay
		$pay['noncestr'] = WxPayApi::getNonceStr();//随机字符串
		$pay['timestamp'] = get_gmtime();//时间戳
		*/
		
		$nonce_str = WxPayApi::getNonceStr();
		$timestamp = get_gmtime();
		
		//调起支付
		$wx_pay = new WxPayDataBase();		
		$wx_pay->Set('appid',$payment_info['config']['wxapp_appid']);
		$wx_pay->Set('partnerid',$payment_info['config']['wxapp_partnerid']);
		$wx_pay->Set('prepayid',$result['prepay_id']);//预支付交易会话ID
		$wx_pay->Set('package','prepay_id='.$result['prepay_id']);//android 写法	
		$wx_pay->Set('noncestr',$nonce_str);//随机字符串
		$wx_pay->Set('timestamp',$timestamp);//时间戳				
		$wx_pay->SetSign(false);//签名		
		
		$pay['config'] = $wx_pay->GetValues();
		
		$wx_pay = new WxPayDataBase();
		$wx_pay->Set('appid',$payment_info['config']['wxapp_appid']);
		$wx_pay->Set('partnerid',$payment_info['config']['wxapp_partnerid']);
		$wx_pay->Set('prepayid',$result['prepay_id']);//预支付交易会话ID
		$wx_pay->Set('package','Sign=Wxpay');//ios 写法
		$wx_pay->Set('noncestr',$nonce_str);//随机字符串
		$wx_pay->Set('timestamp',$timestamp);//时间戳
		$wx_pay->SetSign(false);//签名
		
		$pay['config']['ios'] = $wx_pay->GetValues();
		
		

		$pay['config']['packagevalue'] = 'prepay_id='.$result['prepay_id'];
		$pay['config']['subject'] = $subject;
		$pay['config']['body'] = $title_name;
		$pay['config']['total_fee'] = $money;
		$pay['config']['total_fee_format'] = format_price($money);
		$pay['config']['out_trade_no'] = $payment_notice['notice_sn'];
		$pay['config']['notify_url'] = $data_notify_url;
		
		
		//$pay['mch_id'] = $payment_info['config']['wxapp_partnerid'];
		$pay['config']['key'] = $payment_info['config']['wxapp_key'];
		$pay['config']['secret'] = $payment_info['config']['wxapp_secret'];
		
		/*
		$agent_arr = agentArr();
		log_result("==agent_arr==");
		log_result($agent_arr);
		
		$pay['sdk_code'] = array("pay_sdk_type"=>"wxpay","config"=>
				array(
						"appid"=>$payment_info['config']['wxapp_appid'],
						"partnerid"=>$payment_info['config']['wxapp_partnerid'],
						"prepayid"=>$result['prepay_id'],
						"noncestr"=>$nonce_str,
						"timestamp"=>$timestamp,
						"packagevalue"=>'prepay_id='.$result['prepay_id'],
						"sign"=>$pay['config']['sign']
				)
		);
		*/			
		if(isios())
		{
			$pay['sdk_code'] = array("pay_sdk_type"=>"wxpay","config"=>
					array(
							"appid"=>$payment_info['config']['wxapp_appid'],
							"partnerid"=>$payment_info['config']['wxapp_partnerid'],
							"prepayid"=>$result['prepay_id'],
							"noncestr"=>$nonce_str,
							"timestamp"=>$timestamp,
							"package"=>"Sign=Wxpay",
							"sign" => $pay['config']['ios']['sign']
					)
			);
		}else{
			$pay['sdk_code'] = array("pay_sdk_type"=>"wxpay","config"=>
					array(
							"appid"=>$payment_info['config']['wxapp_appid'],
							"partnerid"=>$payment_info['config']['wxapp_partnerid'],
							"prepayid"=>$result['prepay_id'],
							"noncestr"=>$nonce_str,
							"timestamp"=>$timestamp,
							"packagevalue"=>'prepay_id='.$result['prepay_id'],
							"sign"=>$pay['config']['sign']
					)
			);
		}

		//支付定时日志 7天以后删除
        //log_result_wx_pay_log("==pay==");
        //log_result_wx_pay_log($pay);
		return $pay;
	}
	
	
	function init_define($payment){
		define('WXAPP_APPID',$payment['config']['wxapp_appid']);
		define('WXAPP_MCHID',$payment['config']['wxapp_partnerid']);
		define('WXAPP_KEY',$payment['config']['wxapp_key']);
		define('WXAPP_APPSECRET',$payment['config']['wxapp_secret']);
		
		define('WXAPP_SSLCERT_PATH','');
		define('WXAPP_SSLKEY_PATH','');
		define('WXAPP_CURL_PROXY_HOST',"0.0.0.0");
		define('WXAPP_CURL_PROXY_PORT',0);
		define('WXAPP_REPORT_LEVENL',1);
		
	}
	
	public function notify($request)
	{	
		//log_result("==notify_request==");
		//log_result($request);
		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='WxApp'");  
    	$payment['config'] = unserialize($payment['config']);
    	$this->init_define($payment);
    	//print_r($payment['config']);
    	//log_result("==payment==");
		//log_result($payment);
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Api.php');
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Notify.php');
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Data.php');
		
		try {
			$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
			
			$result = WxPayResults::Init($xml);
			$verify = 1;
		} catch (WxPayException $e){
			//支付定时日志 7天以后删除
            log_result_wx_pay_log("充值失败result");
            log_result_wx_pay_log($e->errorMessage());
			$msg = $e->errorMessage();
			//return false;
			$verify = 0;
		}
		
		
		if ($verify == 1)
		{
			//支付定时日志 7天以后删除
            //log_result_wx_pay_log("result");
            //log_result_wx_pay_log($result);
			if ($result['return_code'] == 'SUCCESS'){
				$payment_notice_sn = $result['out_trade_no'];
				$outer_notice_sn = $result['transaction_id'];
				
			   $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
			//log_result("==payment_notice==");
		    //log_result($payment_notice);
	
			   //$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
			   require_once APP_ROOT_PATH."system/libs/cart.php";
			   $rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);					
			   if ($rs)
			   {
			   		//file_put_contents(APP_ROOT_PATH."/alipaylog/1.txt","");
				   	//$GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set outer_notice_sn = '".$outer_notice_sn."' where id = ".$payment_notice['id']);				
					//order_paid($payment_notice['order_id']);	
			   	  echo "success";
			   }else{
			   		//file_put_contents(APP_ROOT_PATH."/alipaylog/2.txt","");
			   	  echo "success";
			   }
			   
			}else{
				//file_put_contents(APP_ROOT_PATH."/alipaylog/3.txt","");
			   echo "fail";
			} 			
		}
		else
		{
			//file_put_contents(APP_ROOT_PATH."/alipaylog/4.txt","");
		    echo "fail";
		}		
		exit; 				
	}
		//响应通知
	function response($request)
	{}
	
	//获取接口的显示
	function get_display_code()
	{
		return "微信支付";
	}
	
}




?>