<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class OpinionAction extends CommonAction{
    public function index()
    {
        parent::index();
    }

    public function show_content()
    {
        $id = intval($_REQUEST['id']);
        header("Content-Type:text/html; charset=utf-8");
        echo htmlspecialchars(M("DealMsgList")->where("id=".$id)->getField("content"));
    }

    public function content()
    {
        $id = intval($_REQUEST['id']);
        header("Content-Type:text/html; charset=utf-8");
        echo htmlspecialchars(M("Opinion")->where("id=".$id)->getField("content"));
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

    public function delete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->count();
            $list = M(MODULE_NAME)->where ( $condition )->delete();



            if ($list!==false) {
                foreach($rel_data as $k=>$v)
                {
                    if($v['log_id']==0)
                    {
                        $GLOBALS['db']->query("update ".DB_PREFIX."deal set comment_count = comment_count - 1 where id = ".$v['deal_id']);
                    }
                }
                save_log($info."成功删除",1);
                $this->success ("成功删除",$ajax);
            } else {
                save_log($info."删除出错",0);
                $this->error ("删除出错",$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }


}
?>