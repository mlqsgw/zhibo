<?php
// +----------------------------------------------------------------------
// | 拾麦科技
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class small_video_commentModule  extends baseModule
{
    public $table = 'small_video_comment';
	function get_list()
	{

		$root = array();
		if ($GLOBALS['user_info']) {
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
			api_ajax_return($root);
		} else {
			//分页
			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			//分页数量 每次20条 
			$page_size = isset($_REQUEST['page_size']) ? $_REQUEST['page_size'] : 10;
			$limit = (($page - 1) * $page_size) . "," . $page_size;

			$small_video_id = isset($_REQUEST['small_video_id']) ? intval($_REQUEST['small_video_id']) : 0;
			$sql = "SELECT * FROM " . DB_PREFIX . "small_video_comment WHERE small_video_id = " . $small_video_id . " order by create_time desc limit " . $limit;
			$list = $GLOBALS['db']->getAll($sql, true, true);
			$count_sql = "SELECT  count(*)   FROM " . DB_PREFIX . "small_video_comment WHERE small_video_id = " . $small_video_id;
			$count = $GLOBALS['db']->getOne($count_sql, true, true);

			if ($list) {
				$root['list'] = $list;
				if (count($list) < $page_size) {
					$root['page'] = array('page' => $page, 'has_next' => 0);
				} else {
					$root['page'] = array('page' => $page, 'has_next' => 1);
				}
				$root['total_count'] = $count;
				$root['status'] = 1;
			} else {
				$root['list'] = $list;
				$root['page'] = array('page' => $page, 'has_next' => 0);
				$root['status'] = 1;
				$root['error'] = '';
			}
			api_ajax_return($root);
		}
	}
	function add()
	{
		if (!$GLOBALS['user_info']) {
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		} else {
			$small_video_id = isset($_REQUEST['small_video_id']) ? intval($_REQUEST['small_video_id']) : '';
			$title = isset($_REQUEST['title']) ? strim($_REQUEST['title']) : '';	
			if(!$small_video_id || !$title){
				$root=array('status'=>0,'error'=>'参数错误');
				api_ajax_return($root);
			}
			
			//过滤特殊符号
			$str_title = strFilter($title);
			//过滤敏感字
			$limit_name = load_auto_cache("limit_name");
			if($this->keyWordCheck($str_title, $limit_name)){
			    $root=array('status'=>0,'error'=>'评论内容里有敏感词，发布不成功');
			    api_ajax_return($root);
			}
			try{
				$pInTrans = $GLOBALS['db']->StartTrans();
				$user_id =  '100002';$GLOBALS['user_info']['id'];
				$data=array(
				    'title' => $title,
					'small_video_id' => $small_video_id,
					'user_id' => $user_id,
					'create_time' => time()
				);
				$GLOBALS['db']->autoExecute(DB_PREFIX . $this->table, $data, "INSERT");
				$GLOBALS['db']->Commit($pInTrans);
				$root=array('status'=>1,'error'=>'');
			}catch(Exception $e){
				$GLOBALS['db']->Rollback($pInTrans);
				$root=array('status'=>0,'error'=>$e->getMessage());
			}

		}
		api_ajax_return($root);
	}
    /* PHP中用strpos函数过滤关键字 */
    function keyWordCheck($str,$array){
        foreach ($array as $key){
            if(@strpos($str,trim($key))!==false){ // 如果检测到关键字，则返回匹配的关键字,并终止运行
                //$i=$k;
                return true;
            }
        }
        return false; // 如果没有检测到关键字则返回false
    }

}


