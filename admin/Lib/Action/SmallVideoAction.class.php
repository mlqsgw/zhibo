<?php
// +----------------------------------------------------------------------
// | 拾麦科技
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class SmallVideoAction extends CommonAction{
	public function index()
	{
		if(trim($_REQUEST['title'])!='')
		{
            $map['title'] = array('like','%'.trim($_REQUEST['title']).'%');
		}

        $map['is_deleted'] = 0;
		$model = M ('SmallVideo');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ();
	}

	public function add()
	{
		$this->display();
	}
	public function edit() {		
		$id = intval($_REQUEST ['id']);
		$condition['is_deleted'] = 0;
		$condition['id'] = $id;		
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ( 'vo', $vo );
		$this->display ();
	}

	
	public function insert() {
		B('FilterString');
		$ajax = intval($_REQUEST['ajax']);
		$data = M(MODULE_NAME)->create();
	
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/add"));
		if(!check_empty($data['title']))
		{
			$this->error('标题不能为空');
		}
		if(!check_empty($data['head_image'])&&$data['head_image']=='')
		{
			$this->error('封面图片不能为空');
		}
		if(!$data['video_path'])
		{
			$this->error('视频文件不能为空');
		}
		// 更新数据
		$log_info = $data['title'];
		$data['create_time'] = to_date();
		$data['updated'] = to_date();
		$list=M(MODULE_NAME)->add($data);
		if (false !== $list) {
			//成功提示
			save_log($log_info.L("INSERT_SUCCESS"),1);
			clear_auto_cache("get_help_cache");
			$this->success(L("INSERT_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("INSERT_FAILED"),0);
			$this->error(L("INSERT_FAILED"));
		}
	}	
	
	public function update() {
		B('FilterString');
		$data = M(MODULE_NAME)->create ();
		$log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("title");
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
		if(!check_empty($data['title']))
		{
			$this->error('标题不能为空');
		}
		if(!check_empty($data['head_image'])&&$data['head_image']=='')
		{
			$this->error('封面图片不能为空');
		}
		if(!$data['video_path'])
		{
			$this->error('视频文件不能为空');
		}
		// 更新数据
		$data['updated'] = to_date();
		$list=M(MODULE_NAME)->save ($data);
		if (false !== $list) {
			//成功提示
			save_log($log_info.L("UPDATE_SUCCESS"),1);
			clear_auto_cache("get_help_cache");
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
		}
	}

}
?>