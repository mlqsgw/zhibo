<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class TipoffAction extends CommonAction{

    public function index()
    {
        $now=get_gmtime();
        if(trim($_REQUEST['video_id'])!='')
        {
            $map[DB_PREFIX.'tipoff.video_id'] = array('like','%'.trim($_REQUEST['video_id']).'%');
        }
        if(trim($_REQUEST['from_user_id'])!='')
        {
            $user=M("User")->where("nick_name like '%".trim($_REQUEST['from_user_id'])."%' ")->findAll();
            $user_arr_id = array();
            foreach($user as $k=>$v){
                $user_arr_id[$k] =intval($v['id']);
            }
            $map[DB_PREFIX.'tipoff.from_user_id'] = array('in',$user_arr_id);
        }

        if(trim($_REQUEST['to_user_id'])!='')
        {
            $user=M("User")->where("nick_name like '%".trim($_REQUEST['to_user_id'])."%' ")->findAll();
            $user_arr_id = array();
            foreach($user as $k=>$v){
                $user_arr_id[$k] =intval($v['id']);
            }
            $map[DB_PREFIX.'tipoff.to_user_id'] = array('in',$user_arr_id);
        }

        if(intval($_REQUEST['tipoff_type_id'])>0)
        {
            $map[DB_PREFIX.'tipoff.tipoff_type_id'] = intval($_REQUEST['tipoff_type_id']);
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($_REQUEST['create_time_1'])!='' )
        {
            $map[DB_PREFIX.'tipoff.create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        //举报类型
        $condition['is_effect'] = 1;
        $tipoff  =  M('TipoffType')->where($condition)->findAll();
        $this->assign ( 'tipoff', $tipoff );

        $this->display ();
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $vo['from_user_name'] = M("User")->where("id = ".$vo['from_user_id'])->getField("nick_name");
        $vo['to_user_name'] = M("User")->where("id = ".$vo['to_user_id'])->getField("nick_name");
        $this->assign ( 'vo', $vo );
        $tipoff_condition['is_effect'] = 1;
        $tipoff  =  M('TipoffType')->where($tipoff_condition)->findAll();
        $this->assign ( 'tipoff', $tipoff );
        $this->display ();
    }

    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("id");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));

        // 更新数据
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

    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['from_user_id'].'举报'.$data['to_user_id'];
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            //删除相关预览图
//				foreach($rel_data as $data)
//				{
//					@unlink(get_real_path().$data['preview']);
//				}
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                clear_auto_cache("get_help_cache");
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