<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class ShortUrlRedisService extends BaseRedisService
{
    //记录私密直播邀请名单
    var $short_url_db;//:video_id hash数据key:user_id; value:1/0 [1:邀请;0:踢除]
    
    
    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct()
    {
        parent::__construct($platform = 'sina');
        
        $this->short_url_db = $this->prefix.'short_url:'.$platform;
    }

    public function set_short($url_long,$url_short){
    	$key = md5($url_long);
    	
    	//echo '1url_long:'.$url_long.';md5:'.$key.";url_short:".$url_short."<br>";
    	$this->redis->hMSet($this->short_url_db,array($key=>$url_short));
    }
    
   
    public function get_short($url_long){
    	$key = md5($url_long);
    	$url_short = $this->redis->hGet($this->short_url_db,$key);
    	//echo '2url_long:'.$url_long.';md5:'.$key.";url_short:".$url_short."<br>";
    	return $url_short;
    }
    
}//类定义结束

?>