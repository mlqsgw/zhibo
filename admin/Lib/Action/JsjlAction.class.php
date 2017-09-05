<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class JsjlAction extends CommonAction{
	//首页
    public function index(){ 

        // //获取用户类型
        // $user_type = session("user_type");

        //获取用户session数据
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $user_name = $adm_session["adm_name"];
        $user_id = $user_name;

        //获取搜索条件
        $search_data = $_GET;
        $search_name = isset($search_data['search_name']) ? $search_data['search_name'] : '';
        $status_date = isset($search_data['st']) ? $search_data['st'] : '';

        $status_time = strtotime($status_date);
        $type = $_GET['type'];

        

        //获取当前日期时间
        $now_date = date("Y-m-d",time());
        //获取当前的年月
        $now_year_month = date("Y-m",time());
        $now_year_month = str_replace("-","",$now_year_month); //去掉时间格式的横线
        //获取当前天日期
        $now_day = date("d",time());
        $now_day = 1;

        if($now_day >= 16){
              //获取当前月的第一天凌晨时间
              $now_month_status_date = date('Y-m-01', strtotime(date("Y-m-d")));
              $now_month_status_time = strtotime($now_month_status_date);
              //获取当前月十五号11::59:59时间
              $now_month_centre_time = $now_month_status_time + 86400*15 -1;

              $where_prop_user['create_time'] = array(
                  'between',"$now_month_status_time,$now_month_centre_time"
              );
              //设置数据表单名称
              $table_name_num = $now_year_month;
              $table_name = "fanwe_video_prop_". $table_name_num;

              //设置期数
              ////获取当前月的16号凌晨时间
              $expect_date = date('Y-m-16', strtotime(date("Y-m-d")));

        } elseif($now_day < 16){
          //获取上月的第一天凌晨时间
              $last_month_status_date = date('Y-m-01', strtotime('-1 month'));
              $last_month_status_time = strtotime($last_month_status_date);
              //获取上月十六号凌晨时间
              // $last_month_sixteen_time = $last_month_status_time + 86400*15;
              $now_month_status_time = $last_month_status_time + 86400*15;
              
              //获取上月的最后一天11:59:59时间
              $last_month_end_date = date('Y-m-t', strtotime('-1 month'));
              // $last_month_end_time = strtotime($last_month_end_date) + 86400 -1;
              $now_month_centre_time = strtotime($last_month_end_date) + 86400 -1;


              $where_prop_user['create_time'] = array(
                  'between',"$last_month_sixteen_time,$last_month_end_time"
              );

              //设置数据表单名称
              $table_name_num = $now_year_month - 1;
              $table_name = "fanwe_video_prop_". $table_name_num;

              //设置期数
              //获取当前月的第一天凌晨时间
              $expect_date = date('Y-m-01', strtotime(date("Y-m-d")));
        } 

        $m_familySettlementHistory = M('FamilySettlementHistory');

        if($type == 1){
            $where = array(
                "user_id" => $search_name,
                // "expect_date" => $expect_date
            );
        } elseif($type == 2){
            $where = array(
                "nick_name" => $search_name,
                // "expect_date" => $expect_date
            );
        } elseif($type == 3){
            $where = array(
                "expect_date" => $status_date
            );
        } elseif($type == 4){
            $where = array(
                "nick_name" => $search_name,
                "expect_date" => $status_date
            );
        } elseif($type == 5){
            $where = array(
                "user_id" => $search_name,
                "expect_date" => $status_date
            );
        } else{
            $where = array(
                "expect_date" => $expect_date
            );
        }

        import("ORG.Util.Page"); //  导入分页类 
        if(!$_GET['p']){
        		$_GET['p'] = 1;
        }

        if($user_name == "admin"){
            
            $list = $m_familySettlementHistory->where($where)->page($_GET['p'].',25')->order('id desc')->select();

            $count = $m_familySettlementHistory->where($where)->count();
            $p     = new Page($count,25);
            $page = $p->show();

        } else {
        	
            if($status_date){
                $where = array(
                    "user_id" => $user_id,
                    "expect_date" => $status_date
                );
            } else {
                $where = array(
                    "user_id" => $user_id
                );
            }

            $list = $m_familySettlementHistory->where($where)->page($_GET['p'].',25')->order('id desc')->select();

            $count = $m_familySettlementHistory->where($where)->count();
            $p     = new Page($count,25);
            $page = $p->show();
        }

        //设置搜索时间
        if($status_date){
            $where_search = $status_date;
        } else {
            $where_search = $expect_date;
        }

        session_start();
        $_SESSION['where'] = $where;

        // print_r($list);exit;
        $this->assign("list",$list);
        $this->assign("page",$page);
        $this->assign("search_name",$search_name);
        $this->assign("where_search", $where_search);

        $this->display();
    }


    //本期直播记录列表导出
    public function payExport_four(){
        // $name =trim(date("Y/m/d",time()));
        // $name = date("Y/m/d",time());
        //获取要导出的数据
        
    	session_start();
        $where = $_SESSION['where'];
        $m_familySettlementHistory = D('familySettlementHistory');
        $data = $m_familySettlementHistory->where($where)->order('id desc')->select();

        
          include 'PHPExcel/Classes/PHPExcel.php';
          error_reporting(E_ALL);
          date_default_timezone_set('Europe/London');
          $a=count($data);
          $objPHPExcel = new \PHPExcel();
          /*以下是一些设置 ，什么作者  标题啊之类的*/
          $objPHPExcel->getProperties()->setCreator("firefly")
              ->setLastModifiedBy("firefly")
              ->setTitle("数据EXCEL导出")
              ->setSubject("数据EXCEL导出")
              ->setDescription("数据查看")
              ->setKeywords("excel")
              ->setCategory("result file");
          /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
          $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
          ->setCellValue('A1',"结算记录列表")
              ->setCellValue('A2',"家族长id")
              ->setCellValue('B2', "家族长昵称")
              ->setCellValue('C2', "家族名称")
              ->setCellValue('D2', "本期结算魔力值")
              ->setCellValue('E2', "本期未结算魔力值")
              ->setCellValue('F2', "本期收益/元")
              ->setCellValue('G2', "提成系数")
              ->setCellValue('H2', "期数")
              ->setCellValue('I2', "创建时间")
              ;
          foreach($data as $k => $v){
            $create_time = date("Y-m-d H:i:s",$v["create_time"]); 
              $num=$k+1;
              $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
              ->setCellValue('A'.($num+2),$v['user_id'])
                  ->setCellValue('B'.($num+2), $v['nick_name'])
                  ->setCellValue('C'.($num+2), $v['name'])
                  ->setCellValue('D'.($num+2), $v['total_ticket'])
                  ->setCellValue('E'.($num+2), $v['total_ticket_no'])
                  ->setCellValue('F'.($num+2), $v['earnings'])
                  ->setCellValue('G'.($num+2), $v['coefficient'])
                  ->setCellValue('H'.($num+2), $v['expect_date'])
                  ->setCellValue('I'.($num+2), $create_time)
                  ;
  //设置格式
              /*     $objPHPExcel->getActiveSheet()->getStyle('A'.($num+1))->getNumberFormat()
                       ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DMYSLASH);*/

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
          $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);

  //合并单元格
          $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
  //设置字体样式
          $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setSize(10);
          $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true);
  //设置居中
          $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $objPHPExcel->getActiveSheet()->getStyle('A2:I2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $objPHPExcel->getActiveSheet()->getStyle('A3:I3'.($a+1))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $objPHPExcel->getActiveSheet()->setTitle('结算记录');
          $objPHPExcel->setActiveSheetIndex(0);
  //         
         
          $name=date('Y-m-d');//设置文件名
          // $title="家族长管理表";
          ob_end_clean();
          ob_start();
          header('Content-Type: applicationnd.ms-excel');
          header('Content-Disposition: attachment;filename="结算记录表'.$name.'.xlsx"');
          header('Cache-Control: max-age=0');
          $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
          $objWriter->save('php://output');
          exit;
    }
    
}
?>