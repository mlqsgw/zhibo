<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class WeiboOrderAction extends CommonAction{
    public function index()
    {
        $weibo = $this->get_order_list();
        $this->assign ( 'list', $weibo );

        $this->display ();
    }

    public function weixin(){
        $weibo = $this->get_order_list('weixin');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function photo(){
        $weibo = $this->get_order_list('photo');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function red_photo(){
        $weibo = $this->get_order_list('red_photo');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function goods(){
        $weibo = $this->get_order_list('goods');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }

    public function reward(){
        $weibo = $this->get_order_list('reward');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }

    public function chat(){
        $weibo = $this->get_order_list('chat');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }


    public function get_order_list($type=''){
        $now=get_gmtime();

        if(intval($_REQUEST['weibo_id'])>0)
        {
            $condition = " weibo_id =". intval($_REQUEST['weibo_id']);
        }
        if(!$type){
            $type = $_REQUEST['type'];
        }
        if(trim($type))
        {
            $condition .= " type ='". trim($type)."'' ";
        }

        if($_REQUEST['status']!==''){
            $condition = " status =". intval($_REQUEST['status']);
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($_REQUEST['create_time_1'])!='' )
        {
            $map[DB_PREFIX.'weibo.create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));

            $condition .= " create_time  between (".to_timespan($_REQUEST['create_time_1']).",".$create_time_2." ) ";
        }

        $count = M('WeiboRed')->where($condition)->count();
        $p     = new Page($count, $listRows = 20);
        //举报类型

        $weibo  =  M('WeiboRed')->where($condition)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->findAll();
        $page = $p->show();
        $this->assign("page", $page);
        return $weibo;
    }


    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('WeiboRed')->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['user_id'].'的动态'.$data['id'];
            }
            if($info) $info = implode(",",$info);
            $list = M('WeiboRed')->where ( $condition )->delete();
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