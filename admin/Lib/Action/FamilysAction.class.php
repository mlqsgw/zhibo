<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------
class FamilysAction extends CommonAction
{
    protected static function str_trim($str)
    {
        $str = preg_replace("@<script(.*?)</script>@is", "", $str);
        $str = preg_replace("@<iframe(.*?)</iframe>@is", "", $str);
        $str = preg_replace("@<style(.*?)</style>@is", "", $str);
        return preg_replace("@<(.*?)>@is", "", $str);
    }
    public function index()
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        if($adm_session['adm_name'] == 'admin'){
            $where = 'f.user_id = u.id and f.status = 1';
        }else{
            $where = 'f.user_id = u.id and f.status = 1 and u.id = ' . $adm_session['adm_name'];
        }
        if (isset($_REQUEST['user_id'])) {
            $where .= ' and u.id like \'%' . addslashes($_REQUEST['user_id']) . '%\'';
        }
        if (isset($_REQUEST['nick_name']) && $_REQUEST['nick_name']) {
            $where .= ' and u.nick_name like \'%' . addslashes($_REQUEST['nick_name']) . '%\'';
        }
        //获取家族
        $model = M('family');
        $table = DB_PREFIX .'family f,'.DB_PREFIX .'user u';
        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count) {
            $field = 'f.*,u.nick_name';
            $list  = $model->table($table)->where($where)->field($field)->order('f.id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        //当前的对应月
        $new_month = date('Ym');
        $table_name = "fanwe_video_prop_". $new_month;
        //搜索时间条件处理
        $begin_month = $end_month = '';
        $begin_time = isset($_REQUEST['begin_time']) ? substr($_REQUEST['begin_time'], 0,10) : '';
        $end_time =  isset($_REQUEST['end_time']) ? substr($_REQUEST['end_time'], 0,10) : '';
        $public_where = '';
        if($begin_time){
            $begin_month = str_replace('-', '', substr($begin_time, 0,8));
            $table_name = "fanwe_video_prop_". $begin_month;
            $begin_month = str_replace('-', '', substr($begin_time, 0,8));
            $public_where .= ' create_time>=' . strtotime($begin_time);
        }
        if($end_time){
            if(!$begin_time){
                $table_name = "fanwe_video_prop_". $begin_month;
            }else{
                $end_month = str_replace('-', '',substr($end_time, 0,8));
                $table_name = "fanwe_video_prop_". $begin_month;
                $public_where .= ' and create_time<=' . strtotime($end_time);
            }
        }
        foreach ($list as $key => $val){
            $ticket_num_all = 0;
            $ticket_num_no_all = 0;
            //获取对应家族下的所有主播的魔力总值
             if($public_where) 
                 $prop_where = $public_where . ' and family_id = '.$val['id'];
             else
                 $prop_where = 'family_id = '.$val['id'];
         
            $sql = 'select family_id, to_user_id ,SUM(total_ticket) as total_ticket
                    from '.$table_name. ' where ' . $prop_where . ' GROUP BY to_user_id';
            
            //echo $sql;exit;
            $res = $model->query($sql);
            if(!empty($res)){
                foreach ($res as $k => $v) {        
                    $total_ticket = $v['total_ticket'];
                    if($total_ticket >= 20000 ){ //判断主播魔力值是否大于20000
                        $ticket_num_all += $total_ticket; //一个主播可结算魔力值
                    } else {
                        $ticket_num_no_all += $total_ticket; //一个主播未结算魔力值
                    }
                }
            }
            $list[$key]['total_ticket'] = $ticket_num_all;
            $list[$key]['total_ticket_no'] = $ticket_num_no_all;
            $list[$key]['earnings'] = number_format($ticket_num_all * 0.329/100,2);
        }
        $this->assign("page", $p->show());
        $this->assign("list", $list);
        $this->display();
    }
    public function view()
    {
        $family_id     = intval($_REQUEST['family_id']);
        $model  = M('user');
        $table  = DB_PREFIX .'user';
        $where  = 'family_id='.$family_id ;
        if (isset($_REQUEST['user_id'])) {
            $where .= ' and id like \'%' . addslashes($_REQUEST['user_id']) . '%\'';
        }
        if (isset($_REQUEST['nick_name']) && $_REQUEST['nick_name']) {
            $where .= ' and nick_name like \'%' . addslashes($_REQUEST['nick_name']) . '%\'';
        }
        
        $field = 'id,nick_name,create_time';
        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        $list = $model->table($table)->where($where)->field($field)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        //搜索时间条件处理
        $begin_time = isset($_REQUEST['begin_time']) ? substr($_REQUEST['begin_time'], 0,10) : '';
        $end_time = isset($_REQUEST['end_time']) ? substr($_REQUEST['end_time'], 0,10) : '';
        $prop_where = $video_where = '';
        if($begin_time && $end_time){   //开始时候和结束时间都在
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '',$begin_month);
            $prop_where .= ' create_time>=' . strtotime($begin_time);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //开始和结束时间月份不一致处理
            $end_month = substr($end_time, 0,7);
            if($begin_month == $end_month){
                $prop_where  .= ' and create_time<=' . strtotime($end_time);
                $video_where .= ' and end_time<=' . strtotime($end_time);
            }else{
                //获取需要月份最后一天
                $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
                //prop可以不处理 因为它是每一个月生产一张表
                $video_where .= ' and end_time<=' . strtotime($end_date);
            }
        }else if($begin_time && !$end_time) { //开始时候有和结束时间无
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $begin_month);
            $prop_where .= ' create_time>=' . strtotime($begin_time);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //处理结束时间
            $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
            $video_where .= ' and end_time<=' . strtotime($end_date);
        }else if (!$begin_time && $end_time){ //开始时候无和结束时间有
            $end_month = substr($end_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $end_month);
            $prop_where .= ' and create_time<=' . strtotime($end_time);
            //处理结束时间
            $start_date = $end_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
            $video_where .= ' and end_time<=' . strtotime($end_time);
        }else{
            //当前的对应月
            $new_month = date('Y-m');
            $table_name = "fanwe_video_prop_". str_replace('-', '', $new_month);
            $start_date = $new_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
        }
        $video_table =  DB_PREFIX .'video_history';
        
        foreach ($list as $key=>$val){
            //获取对应家族下的所有主播的魔力总值
            if($prop_where){ 
                $ticket_where = $prop_where . ' and to_user_id = '.$val['id'];
            }else{
                $ticket_where = ' to_user_id = '.$val['id'] ;
            }    
            $sql = 'select SUM(total_ticket) as total_ticket  from '.$table_name. ' where ' . $ticket_where . ' GROUP BY to_user_id';
            //echo $sql;exit;
            $res = $model->query($sql);
            $total_ticket = empty($res) ? 0 : $res[0]['total_ticket'];
            $list[$key]['total_ticket'] = $total_ticket;
            //直播时间
            $video_live_where = $video_where . ' and user_id = '.$val['id'];
            $livetime = $model->table($video_table)->where($video_live_where)->field('begin_time,end_time,end_date')->select();
            $list[$key]['valid_days'] = $this->dolivetime($livetime);  
        }
        $this->assign('family_id', $family_id);
        $this->assign('family_name', $_REQUEST['family_name']);
        $this->assign('list', $list);
        $this->assign("page", $p->show());
        $this->display();
    }
    //本期直播记录
    public function bqzbjlview()
    {  
        $id     = intval($_REQUEST['id']);
        $model  = M('user');
        $table  = DB_PREFIX .'user';
        //用户昵称
        $user_nick_name =$model->table($table)->where('id = ' . $id)->field('nick_name,mobile')->find();
        //搜索时间条件处理
        $begin_time = isset($_REQUEST['begin_time']) ? substr($_REQUEST['begin_time'], 0,10) : '';
        $end_time = isset($_REQUEST['end_time']) ? substr($_REQUEST['end_time'], 0,10) : '';
        $video_where = '';
        if($begin_time && $end_time){   //开始时候和结束时间都在
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '',$begin_month);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //开始和结束时间月份不一致处理
            $end_month = substr($end_time, 0,7);
            if($begin_month == $end_month){
                $video_where .= ' and end_time<=' . strtotime($end_time);
            }else{
                //获取需要月份最后一天
                $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
                //prop可以不处理 因为它是每一个月生产一张表
                $video_where .= ' and end_time<=' . strtotime($end_date);
            }
        }else if($begin_time && !$end_time) { //开始时候有和结束时间无
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $begin_month);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //处理结束时间
            $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
            $video_where .= ' and end_time<=' . strtotime($end_date);
        }else if (!$begin_time && $end_time){ //开始时候无和结束时间有
            $end_month = substr($end_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $end_month);
            //处理结束时间
            $start_date = $end_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
            $video_where .= ' and end_time<=' . strtotime($end_time);
        }else{
            //当前的对应月
            $new_month = date('Y-m');
            $table_name = "fanwe_video_prop_". str_replace('-', '', $new_month);
            $start_date = $new_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
        }
        $video_where .= ' and user_id = ' . $id;
        $video_table =  DB_PREFIX .'video_history';
        $field = 'watch_number,begin_time,end_time,end_date,fans_count';
        $videos = $model->table($video_table)->where($video_where)->field($field)->order('begin_time asc')->select();
        
        $list = $result = array();
        foreach ($videos as $row){
            $list[$row['end_date']][] = $row;
        }    
        $count = count($list);
        foreach ($list as $key=>$row){
            $result[$key]['live_day'] = $key;
            $live_day_time = 0; //直播时长
            $live_day_fans = 0; //日粉丝增量
            foreach ($row as $v){
                $time = $v['end_time'] - $v['begin_time'];
                $live_day_time += $time;
                $live_day_fans += $v['fans_count'];
                $watch_number[] = $v['watch_number'];//在线人数
            }
            $result[$key]['live_day_time'] = number_format($live_day_time / 3600,2);
            $result[$key]['live_day_fans'] = $live_day_fans;
            //日魔力值增量
            $sql = "select SUM(total_ticket) as total_ticket  from $table_name where to_user_id = $id and create_date = '$key' " ;
           
            $res = $model->query($sql);
            $result[$key]['live_day_ticket'] = empty($res) ? 0 : $res[0]['total_ticket'];
            //最高在线人数
            rsort($watch_number);
            $result[$key]['live_day_watch_number'] = $watch_number[0];
            //是否有效天数
            $result[$key]['valid_days'] = $live_day_time > 3 * 3600 ? 1 : 0;
        }
        $total = array();  
        foreach ($result as $row){
            $live_day_time_total += $row['live_day_time'];
            $live_day_ticket_total += $row['live_day_ticket'];
            $live_day_fans_total += $row['live_day_fans'];
            $watch_number_max[] = $row['live_day_watch_number'];
            rsort($watch_number_max);
            $valid_days_total = 0;
            if($row['valid_days'] > 0 ){
                $valid_days_total += 1;
            }
            $total['live_day_time_total'] = $live_day_time_total;
            $total['live_day_ticket_total'] = $live_day_ticket_total;
            $total['watch_number_max'] = $watch_number_max[0];
            $total['valid_days_total'] = $valid_days_total;
            $total['live_day_fans_total'] = $live_day_fans_total;
        }
      
        $total['live_day_ticket_total_pinjun'] = $total['live_day_ticket_total'] / $count;
        $total['live_day_fans_pinjun'] = $total['live_day_fans_total'] / $count;
       // p($total);
        $this->assign('total', $total);
        $this->assign('family_id', $_REQUEST['family_id']);
        $this->assign('id', $id);
        $this->assign('user_nick_name', $user_nick_name['nick_name']);
        $this->assign('user_mobile', $user_nick_name['mobile']);
        $this->assign('family_name', $_REQUEST['family_name']);
        $this->assign('family_id', $_REQUEST['family_id']);
        $this->assign('list', $result);
        $this->display();
    }
    public function export(){
        set_time_limit(0);
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        if($adm_session['adm_name'] == 'admin'){
            $where = 'f.user_id = u.id and f.status = 1';
        }else{
            $where = 'f.user_id = u.id and f.status = 1 and u.id = ' . $adm_session['adm_name'];
        }
        if (isset($_REQUEST['user_id'])) {
            $where .= ' and u.id like \'%' . addslashes($_REQUEST['user_id']) . '%\'';
        }
        if (isset($_REQUEST['nick_name']) && $_REQUEST['nick_name']) {
            $where .= ' and u.nick_name like \'%' . addslashes($_REQUEST['nick_name']) . '%\'';
        }
        //获取家族
        $model = M('family');
        $table = DB_PREFIX .'family f,'.DB_PREFIX .'user u';
        $field = 'f.*,u.nick_name';
        $list  = $model->table($table)->where($where)->field($field)->order('f.id desc')->select();
        //当前的对应月
        $new_month = date('Ym');
        $table_name = "fanwe_video_prop_". $new_month;
        //搜索时间条件处理
        $begin_month = $end_month = '';
        $begin_time = isset($_REQUEST['begin_time']) ? substr($_REQUEST['begin_time'], 0,10) : '';
        $end_time =  isset($_REQUEST['end_time']) ? substr($_REQUEST['end_time'], 0,10) : '';
        $public_where = '';
        if($begin_time){
            $begin_month = str_replace('-', '', substr($begin_time, 0,8));
            $table_name = "fanwe_video_prop_". $begin_month;
            $begin_month = str_replace('-', '', substr($begin_time, 0,8));
            $public_where .= ' create_time>=' . strtotime($begin_time);
        }
        if($end_time){
            if(!$begin_time){
                $table_name = "fanwe_video_prop_". $begin_month;
            }else{
                $end_month = str_replace('-', '',substr($end_time, 0,8));
                $table_name = "fanwe_video_prop_". $begin_month;
                $public_where .= ' and create_time<=' . strtotime($end_time);
            }
        }
        foreach ($list as $key => $val){
            $ticket_num_all = 0;
            $ticket_num_no_all = 0;
            //获取对应家族下的所有主播的魔力总值
            if($public_where)
                $prop_where = $public_where . ' and family_id = '.$val['id'];
            else
                $prop_where = 'family_id = '.$val['id'];
             
            $sql = 'select family_id, to_user_id ,SUM(total_ticket) as total_ticket
                    from '.$table_name. ' where ' . $prop_where . ' GROUP BY to_user_id';
        
            //echo $sql;exit;
            $res = $model->query($sql);
            if(!empty($res)){
                foreach ($res as $k => $v) {
                    $total_ticket = $v['total_ticket'];
                    if($total_ticket >= 20000 ){ //判断主播魔力值是否大于20000
                        $ticket_num_all += $total_ticket; //一个主播可结算魔力值
                    } else {
                        $ticket_num_no_all += $total_ticket; //一个主播未结算魔力值
                    }
                }
            }
            $list[$key]['total_ticket'] = $ticket_num_all;
            $list[$key]['total_ticket_no'] = $ticket_num_no_all;
            $list[$key]['earnings'] = number_format($ticket_num_all * 0.329/100,2);
        }
        $this->excel($list, '家族长列表',1);
    }
    public function view_export(){
        $family_id     = intval($_REQUEST['family_id']);
        $model  = M('user');
        $table  = DB_PREFIX .'user';
        $where  = 'family_id='.$family_id ;
        if (isset($_REQUEST['user_id'])) {
            $where .= ' and id like \'%' . addslashes($_REQUEST['user_id']) . '%\'';
        }
        if (isset($_REQUEST['nick_name']) && $_REQUEST['nick_name']) {
            $where .= ' and nick_name like \'%' . addslashes($_REQUEST['nick_name']) . '%\'';
        }
        
        $field = 'id,nick_name,create_time';
        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        $list = $model->table($table)->where($where)->field($field)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        //搜索时间条件处理
        $begin_time = isset($_REQUEST['begin_time']) ? substr($_REQUEST['begin_time'], 0,10) : '';
        $end_time = isset($_REQUEST['end_time']) ? substr($_REQUEST['end_time'], 0,10) : '';
        $prop_where = $video_where = '';
        if($begin_time && $end_time){   //开始时候和结束时间都在
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '',$begin_month);
            $prop_where .= ' create_time>=' . strtotime($begin_time);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //开始和结束时间月份不一致处理
            $end_month = substr($end_time, 0,7);
            if($begin_month == $end_month){
                $prop_where  .= ' and create_time<=' . strtotime($end_time);
                $video_where .= ' and end_time<=' . strtotime($end_time);
            }else{
                //获取需要月份最后一天
                $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
                //prop可以不处理 因为它是每一个月生产一张表
                $video_where .= ' and end_time<=' . strtotime($end_date);
            }
        }else if($begin_time && !$end_time) { //开始时候有和结束时间无
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $begin_month);
            $prop_where .= ' create_time>=' . strtotime($begin_time);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //处理结束时间
            $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
            $video_where .= ' and end_time<=' . strtotime($end_date);
        }else if (!$begin_time && $end_time){ //开始时候无和结束时间有
            $end_month = substr($end_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $end_month);
            $prop_where .= ' and create_time<=' . strtotime($end_time);
            //处理结束时间
            $start_date = $end_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
            $video_where .= ' and end_time<=' . strtotime($end_time);
        }else{
            //当前的对应月
            $new_month = date('Y-m');
            $table_name = "fanwe_video_prop_". str_replace('-', '', $new_month);
            $start_date = $new_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
        }
        $video_table =  DB_PREFIX .'video_history';
        foreach ($list as $key=>$val){
            //获取对应家族下的所有主播的魔力总值
            if($prop_where){
                $ticket_where = $prop_where . ' and to_user_id = '.$val['id'];
            }else{
                $ticket_where = ' to_user_id = '.$val['id'] ;
            }
            $sql = 'select SUM(total_ticket) as total_ticket  from '.$table_name. ' where ' . $ticket_where . ' GROUP BY to_user_id';
            $res = $model->query($sql);
            $total_ticket = empty($res) ? 0 : $res[0]['total_ticket'];
            $list[$key]['total_ticket'] = $total_ticket;
            //直播时间
            $video_live_where = $video_where . ' and user_id = '.$val['id'];
            $livetime = $model->table($video_table)->where($video_live_where)->field('begin_time,end_time,end_date')->select();
            $list[$key]['valid_days'] = $this->dolivetime($livetime);
        }
        $A1name = '家族【' . $_REQUEST['family_name'] .'】成员列表';
        $this->excel($list, $A1name, 2);
    }
    //本期直播记录
    public function bqzbjlview_export()
    {
        $id     = intval($_REQUEST['id']);
        $model  = M('user');
        $table  = DB_PREFIX .'user';
        //搜索时间条件处理
        $begin_time = isset($_REQUEST['begin_time']) ? substr($_REQUEST['begin_time'], 0,10) : '';
        $end_time = isset($_REQUEST['end_time']) ? substr($_REQUEST['end_time'], 0,10) : '';
        $video_where = '';
        if($begin_time && $end_time){   //开始时候和结束时间都在
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '',$begin_month);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //开始和结束时间月份不一致处理
            $end_month = substr($end_time, 0,7);
            if($begin_month == $end_month){
                $video_where .= ' and end_time<=' . strtotime($end_time);
            }else{
                //获取需要月份最后一天
                $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
                //prop可以不处理 因为它是每一个月生产一张表
                $video_where .= ' and end_time<=' . strtotime($end_date);
            }
        }else if($begin_time && !$end_time) { //开始时候有和结束时间无
            $begin_month = substr($begin_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $begin_month);
            $video_where .= ' create_time>=' . strtotime($begin_time);
            //处理结束时间
            $end_date = date('Y-m-d', strtotime("$begin_month +1 month -1 day"));
            $video_where .= ' and end_time<=' . strtotime($end_date);
        }else if (!$begin_time && $end_time){ //开始时候无和结束时间有
            $end_month = substr($end_time, 0,7);
            $table_name = "fanwe_video_prop_". str_replace('-', '', $end_month);
            //处理结束时间
            $start_date = $end_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
            $video_where .= ' and end_time<=' . strtotime($end_time);
        }else{
            //当前的对应月
            $new_month = date('Y-m');
            $table_name = "fanwe_video_prop_". str_replace('-', '', $new_month);
            $start_date = $new_month .'-01';
            $video_where .= ' create_time>=' . strtotime($start_date);
        }
        $video_where .= ' and user_id = ' . $id;
        $video_table =  DB_PREFIX .'video_history';
        $field = 'watch_number,begin_time,end_time,end_date,fans_count';
        $videos = $model->table($video_table)->where($video_where)->field($field)->order('begin_time asc')->select();
    
        $list = $result = array();
        foreach ($videos as $row){
            $list[$row['end_date']][] = $row;
        }
        $count = count($list);
        foreach ($list as $key=>$row){
            $result[$key]['live_day'] = $key;
            $live_day_time = 0; //直播时长
            $live_day_fans = 0; //日粉丝增量
            foreach ($row as $v){
                $time = $v['end_time'] - $v['begin_time'];
                $live_day_time += $time;
                $live_day_fans += $v['fans_count'];
                $watch_number[] = $v['watch_number'];//在线人数
            }
            $result[$key]['live_day_time'] = number_format($live_day_time / 3600,2);
            $result[$key]['live_day_fans'] = $live_day_fans;
            //日魔力值增量
            $sql = "select SUM(total_ticket) as total_ticket  from $table_name where to_user_id = $id and create_date = '$key' " ;
            $res = $model->query($sql);
            $result[$key]['live_day_ticket'] = empty($res) ? 0 : $res[0]['total_ticket'];
            //最高在线人数
            rsort($watch_number);
            $result[$key]['live_day_watch_number'] = $watch_number[0];
            //是否有效天数
            $result[$key]['valid_days'] = $live_day_time > 3 * 3600 ? 1 : 0;
        }
        $total = array();
        foreach ($result as $row){
            $live_day_time_total += $row['live_day_time'];
            $live_day_ticket_total += $row['live_day_ticket'];
            $live_day_fans_total += $row['live_day_fans'];
            $watch_number_max[] = $row['live_day_watch_number'];
            rsort($watch_number_max);
            $valid_days_total = 0;
            if($row['valid_days'] > 0 ){
                $valid_days_total += 1;
            }
            $total['live_day_time_total'] = $live_day_time_total;
            $total['live_day_ticket_total'] = $live_day_ticket_total;
            $total['watch_number_max'] = $watch_number_max[0];
            $total['valid_days_total'] = $valid_days_total;
            $total['live_day_fans_total'] = $live_day_fans_total;
        }
        $total['user_mobile'] = $_REQUEST['family_name'];
        $total['live_day_ticket_total_pinjun'] = $total['live_day_ticket_total'] / $count;
        $total['live_day_fans_pinjun'] = $total['live_day_fans_total'] / $count;
        $export_data = array('total'=>$total,'result'=>$result);
        $A1name = '家族【'.$_REQUEST['family_name'].'】成员【'.$_REQUEST['nick_name'].'】直播明细';
        $this->excel($export_data, $A1name, 3);
    }
    //处理时间
    private function dolivetime($livetime){
        if(empty($livetime)) return 0;
        $live = array();
        $days = 0;
        foreach ($livetime as $list){
            $live[$list['end_date']][] = $list;
        }
        $days = 0;
        foreach ($live as $key=>$val){
            $total_time = 0;
            foreach ($val as $v){
                $time = $v['end_time'] - $v['begin_time'];
                $total_time += $time;
            }
            if($total_time > 3*3600){
                $days += 1;
            }
        }
        return $days;
    }
    private function excel($list,$A1name,$type){
        include 'PHPExcel/Classes/PHPExcel.php';
        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        $count = count($list);
        $objPHPExcel = new \PHPExcel();
        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("firefly")->setLastModifiedBy("firefly")->setTitle("数据EXCEL导出")
        ->setSubject("数据EXCEL导出") ->setDescription("数据查看")->setKeywords("excel")->setCategory("result file");
        switch ($type){
            case 1:
                /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
                $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A1',$A1name)
                ->setCellValue('A2',"家族ID")
                ->setCellValue('B2',"家族长ID")
                ->setCellValue('C2', "家族长昵称")
                ->setCellValue('D2', "家族名称")
                ->setCellValue('E2', "可结算魔力值")
                ->setCellValue('F2', "未结算魔力值")
                ->setCellValue('G2', "本期收益/元")
                ->setCellValue('H2', "创建时间")
                ;
                foreach($list as $k => $v){
                    $num=$k+1;
                    $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                    ->setCellValue('A'.($num+2),$v['id'])
                    ->setCellValue('B'.($num+2), $v['user_id'])
                    ->setCellValue('C'.($num+2), $v['nick_name'])
                    ->setCellValue('D'.($num+2), $v['name'])
                    ->setCellValue('E'.($num+2), $v['total_ticket'])
                    ->setCellValue('F'.($num+2), $v['total_ticket_no'])
                    ->setCellValue('G'.($num+2), $v['earnings'])
                    ->setCellValue('H'.($num+2), to_date($v['create_time']));
                }
                //设置单元格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
                //合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
                //设置字体样式
                $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setSize(10);
                $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setBold(true);
                //设置居中
                $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A3:H3'.($count+1))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->setTitle($A1name);
                $objPHPExcel->setActiveSheetIndex(0);
                break;
            case 2:
                /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
                $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A1',$A1name)
                ->setCellValue('A2',"用户ID")
                ->setCellValue('B2',"用户昵称")
                ->setCellValue('C2', "新增魔力值")
                ->setCellValue('D2', "有效天数")
                ->setCellValue('E2', "加入时间")
                ;
                foreach($list as $k => $v){
                    $num=$k+1;
                    $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                    ->setCellValue('A'.($num+2),$v['id'])
                    ->setCellValue('B'.($num+2), $v['nick_name'])
                    ->setCellValue('C'.($num+2), $v['total_ticket'])
                    ->setCellValue('D'.($num+2), $v['valid_days'])
                    ->setCellValue('E'.($num+2), to_date($v['create_time']));
                }
                //设置单元格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                //合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');
                //设置字体样式
                $objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFont()->setSize(10);
                $objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFont()->setBold(true);
                //设置居中
                $objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A3:E3'.($count+1))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->setTitle($A1name);
                $objPHPExcel->setActiveSheetIndex(0);
                break;
            case 3:
                $total = $list['total'];
                /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
                $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A1',$A1name)
                ->setCellValue('A2',"手机号")
                ->setCellValue('B2',"本期直播时长")
                ->setCellValue('C2', "本期魔力值增量")
                ->setCellValue('D2', "本期最高在线人数")
                ->setCellValue('E2', "本期有效天数")
                ->setCellValue('F2', "平均日增魔力")
                ->setCellValue('G2', "平均日增粉丝");
                #-------------------------------#
                $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A3',$total['user_mobile'])
                ->setCellValue('B3',$total['live_day_time_total'])
                ->setCellValue('C3',$total['live_day_ticket_total'])
                ->setCellValue('D3',$total['watch_number_max'])
                ->setCellValue('E3',$total['valid_days_total'])
                ->setCellValue('F3',$total['live_day_ticket_total_pinjun'])
                ->setCellValue('G3',$total['live_day_fans_pinjun']); 
                #-------------------------------#
                $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A4',"日期")
                ->setCellValue('B4',"日直播时长(小时)")
                ->setCellValue('C4', "日魔力值增量")
                ->setCellValue('D4', "日粉丝增量")
                ->setCellValue('E4', "日最高在线人数")
                ->setCellValue('F4', "是否有效天");
                $num=5;
                foreach($list['result'] as $k => $v){
                    $create_time = date("Y-m-d H:i:s",$v["create_time"]);
                    $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                    ->setCellValue('A'.$num,$v['live_day'])
                    ->setCellValue('B'.$num, $v['live_day_time'])
                    ->setCellValue('C'.$num, $v['live_day_ticket'])
                    ->setCellValue('D'.$num, $v['live_day_fans'])
                    ->setCellValue('E'.$num, $v['live_day_watch_number'])
                    ->setCellValue('F'.$num, $v['valid_days']);
                    $num++;
                }
                //设置单元格宽度
                $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                //合并单元格
                $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
                //设置字体样式
                $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setSize(10);
                $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setBold(true);
                //设置居中
                $objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A2:H2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A3:H3'.($count+1))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->setTitle($A1name);
                $objPHPExcel->setActiveSheetIndex(0);
                break;
            default:
                exit('导出类型有误');
        }
        //设置格式
        /*     $objPHPExcel->getActiveSheet()->getStyle('A'.($num+1))->getNumberFormat()
         ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DMYSLASH);*/
        //设置文件名
        $name = $A1name . date('Y-m-d');
        // $title="家族长管理表";
        ob_end_clean();
        ob_start();
        header('Content-Type: applicationnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$name.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    //
    public function update_data(){
        set_time_limit(0);
        $model = M('family');
        $table = DB_PREFIX .'video_prop_201706';
        $user_table = DB_PREFIX .'user';
        $list = $model->table($table)->where('family_id = 0')->field('id,to_user_id')->order('id asc')->select();
        foreach ($list as $k=>$row){
           
            $user = $model->table($user_table)->field('family_id')->where('id='.$row['to_user_id'])->find();
            if($user['family_id'] == 0){
                echo "--------------".$row['id']."条数据没有家族信息-----------------<br/>";
            }else{
                echo "--------------正在更新第".$row['id']."条数据-----------------<br/>";
                $sql = "update " . $table . " set family_id = " . $user['family_id'] . " where id = " . $row['id'];
                $update = $model->query($sql);
            }
            
           
        }
        echo "----------更新end----------------------";
    }
}
