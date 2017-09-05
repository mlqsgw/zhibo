<?php
// +----------------------------------------------------------------------
// | 拾麦科技
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class SmallVideoCommentAction extends CommonAction{
	public function index()
	{
		if(trim($_REQUEST['title'])!='')
		{
            $map['title'] = array('like','%'.trim($_REQUEST['title']).'%');
		}
		if($_REQUEST['small_video_id']!=''&&intval($_REQUEST['small_video_id'])!=-1)
		{
		    $map['small_video_id']=intval($_REQUEST['small_video_id']);
		}
		
		$model = M ('SmallVideoComment');
        if (! empty ( $model )) {
           
            $this->_list ( $model, $map );
        }
        $list = $this->get('list');
        $user = M ("user");
        $small_video = M ('small_video');
        foreach ($list as $key=>$row){
            $list[$key]['nick_name'] = $user->getField('nick_name',array('id'=>$row['user_id']));
            $list[$key]['small_video_title'] = $small_video->getField('title',array('id'=>$row['small_video_id']));
        }
        //小视频
        $small_video_list =$small_video->findAllField('id,title');
        $this->assign("list",$list);
        $this->assign("small_video_list",$small_video_list);
        
        $this->display ();
	}
	public function foreverdelete() {
	    //彻底删除指定记录
	    $ajax = intval($_REQUEST['ajax']);
	    $id = $_REQUEST ['id'];
	    if (isset ( $id )) {
	        $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
	        $rel_data = M(MODULE_NAME)->where($condition)->findAll();
	        foreach($rel_data as $data)
	        {
	            $info[] = $data['id'];
	        }
	        if($info) $info = implode(",",$info);
	        $list = M(MODULE_NAME)->where ( $condition )->delete();
	        	
	        if ($list!==false) {
	            save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
	            $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
	        } else {
	            save_log($info.l("FOREVER_DELETE_FAILED"),0);
	            $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
	        }
	    } else {
	        $this->error (l("INVALID_OPERATION"),$ajax);
	    }
	}
}
?>