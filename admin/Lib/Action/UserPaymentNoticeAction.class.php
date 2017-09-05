<?php
// +----------------------------------------------------------------------
// | Fanwe 拾麦科技有限公司
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.pidantv.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------

class UserPaymentNoticeAction extends CommonAction{
	public function index()
	{
		if(trim($_REQUEST['user_id'])!='')
		{
			$user_id = intval($_REQUEST['user_id']);
			$map['user_id'] = $user_id;
		}

		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		
		$model = M ("UserLog");
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$list = $this->get("list");
		$this->assign("list",$list);
		$this->display();
		return;
	}

	
}
?>