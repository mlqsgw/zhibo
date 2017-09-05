<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class es_cookie 
{
    // 判断Cookie是否存在
    static function is_set($name) {
        return isset($_COOKIE[$name]);
    }

    // 获取某个Cookie值
    static function get($name) {
    	if(isset($_COOKIE[$name]))
        $value   = $_COOKIE[$name];
        else
        $value = null;
        return $value;
    }

    // 设置某个Cookie值
    static function set($name,$value,$expire='',$path='',$domain='') {   
    	$path = $GLOBALS['distribution_cfg']['COOKIE_PATH'];
     	$domain = $GLOBALS['distribution_cfg']['DOMAIN_ROOT'];
        $expire =   !empty($expire)?get_gmtime()+$expire:0;
        setcookie($name, $value,$expire,$path,$domain);
    }

    // 删除某个Cookie值
    static function delete($name) {
        es_cookie::set($name,'',0);
    }

    // 清空Cookie值
    static function clear() {
        unset($_COOKIE);
    }
}
?>