<?php
// +----------------------------------------------------------------------
// | 拾麦科技
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class small_videoModule  extends baseModule
{

	function get_list()
	{

		$root = array();
		if (!$GLOBALS['user_info']) {
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
			api_ajax_return($root);
		} else {
			//分页
			$page = intval($_REQUEST['page']);//取第几页数据
			if ($page == 0 || $page == '') {
				$page = 1;
			}
			//每次20条
			$page_size = intval($_REQUEST['page_size']);//分页数量;
			if ($page_size == '') {
				$page_size = 10;
			}
			$limit = (($page - 1) * $page_size) . "," . $page_size;
			//搜索
			$user_id=$GLOBALS['user_info']['id'];
			$title = trim($_REQUEST['title']);
			$sql = "SELECT sv.*,u.nick_name,if(sl.id,1,0) as is_supported,u.head_image as user_head_image FROM " . DB_PREFIX . "small_video sv
		 LEFT JOIN " . DB_PREFIX . "user u  on  sv.user_id=u.id
		 LEFT JOIN " . DB_PREFIX . "support_log sl on sl.user_id=$user_id AND sl.relation_id=sv.id AND sl.type=1
		WHERE sv.is_deleted=0 AND sv.is_banned=0 order by sort_init,id desc limit " . $limit;
			$list = $GLOBALS['db']->getAll($sql, true, true);
			$count_sql = "SELECT  count(*)   FROM " . DB_PREFIX . "small_video sv
		 LEFT JOIN " . DB_PREFIX . "user u  on  sv.user_id=u.id
		WHERE sv.is_deleted=0 AND sv.is_banned=0";
			$count = $GLOBALS['db']->getOne($count_sql, true, true);

			if ($list) {
				foreach($list as $k=>$value)
				{
					$list[$k]['user_head_image']=get_spec_image($value['user_head_image']);
				}
				$root['list'] = $list;

				if (count($list) < $page_size) {
					$root['page'] = array('page' => $page, 'has_next' => 0);
				} else {
					$root['page'] = array('page' => $page, 'has_next' => 1);
				}
//			$root['page'] = $page;
				$root['total_count'] = $count;
				$root['status'] = 1;
				$root['error'] = '';
			} else {
				$root['list'] = $list;
				$root['page'] = array('page' => $page, 'has_next' => 0);
				$root['status'] = 1;
				$root['error'] = '';
			}
			api_ajax_return($root);
		}
	}

	function get_detail()
	{
		if (!$GLOBALS['user_info']) {
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
			api_ajax_return($root);
		} else {
			$id = intval($_REQUEST['id']);
			if (!$id) {
				$root = array('status' => 0, 'error' => '参数错误');
				api_ajax_return($root);
			}
			$detail = $GLOBALS['db']->getRow("SELECT sv.*,u.nick_name,u.head_image as user_head_image FROM " . DB_PREFIX . "small_video sv
		 LEFT JOIN " . DB_PREFIX . "user u  on  sv.user_id=u.id
		WHERE sv.is_deleted=0 AND sv.is_banned=0 AND sv.id=" . $id);
			$detail['user_head_image']=get_spec_image($detail['user_head_image']);
			if (!$detail) {
				$root = array('status' => 0, 'error' => '该数据不存在');
				api_ajax_return($root);
			} else {
				$user_id=$GLOBALS['user_info']['id'];
				$sql='SELECT id FROM '. DB_PREFIX . "support_log sl WHERE user_id=$user_id AND relation_id=$id AND type=1";
				$log=$GLOBALS['db']->getOne($sql,true,true);
				if($log)
				{
					//已点赞
					$is_supported=1;
				}else{
					//未点赞
					$is_supported=0;
				}
				$root = array('status' => 1, 'detail' => $detail, 'error' => '','is_supported'=>$is_supported);
				api_ajax_return($root);
			}
		}
	}

	function support()
	{
		if (!$GLOBALS['user_info']) {
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		} else {
			$id=intval($_REQUEST['id']);
			if(!$id){
				$root=array('status'=>0,'error'=>'参数错误');
				api_ajax_return($root);
			}
			try{
				$pInTrans = $GLOBALS['db']->StartTrans();
				$user_id=$GLOBALS['user_info']['id'];
				$sql='SELECT id FROM '. DB_PREFIX . "support_log sl WHERE user_id=$user_id AND relation_id=$id AND type=1";
				$log=$GLOBALS['db']->getOne($sql,true,true);
				if($log)
				{
					$sql = "delete FROM " . DB_PREFIX . "support_log where id = " . $log;
					$GLOBALS['db']->query($sql);
					$sql = "update " . DB_PREFIX . "small_video set support_count = support_count - 1 where id = " . $id;
					$GLOBALS['db']->query($sql);
					$GLOBALS['db']->Commit($pInTrans);
					$root=array('status'=>1,'error'=>'');
				}else{
					$sql = "update " . DB_PREFIX . "small_video set support_count = support_count + 1 where id = " . $id;
					$GLOBALS['db']->query($sql);
					$data=array(
							'log_time'=>date('Y-m-d H:i:s'),
							'user_id'=> $user_id,
							'relation_id'=>$id,
							'type'=>1);
					$GLOBALS['db']->autoExecute(DB_PREFIX . "support_log", $data, "INSERT");
					$GLOBALS['db']->Commit($pInTrans);
					$root=array('status'=>1,'error'=>'');
				}
			}catch(Exception $e)
			{
				$GLOBALS['db']->Rollback($pInTrans);
				$root=array('status'=>0,'error'=>$e->getMessage());
			}

		}
		api_ajax_return($root);
	}

}


