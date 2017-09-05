<?php 
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------\
error_reporting(0);
if(!defined('APP_ROOT_PATH')) 
define('APP_ROOT_PATH', str_replace('verify.php', '', str_replace('\\', '/', __FILE__)));
require './system/system_init.php';
require APP_ROOT_PATH."system/utils/es_image.php";
$very_name = strim($_REQUEST['name'])?strim($_REQUEST['name']):'login_verify';
es_image::buildImageVerify(4,1,'gif',48,22,$very_name);
?>