<?php 
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

if(!defined("ADMIN_ROOT"))
{
	die("Invalid access");
}
require './system/system_init.php';

define('BASE_PATH','./');
define('THINK_PATH', './admin/ThinkPHP');
//定义项目名称和路径
define('APP_NAME', 'admin');
define('APP_PATH', './admin');

// 加载框架入口文件 
require(THINK_PATH."/ThinkPHP.php");

//实例化一个网站应用实例
$AppWeb = new App(); 
//应用程序初始化
$AppWeb->run();

?>