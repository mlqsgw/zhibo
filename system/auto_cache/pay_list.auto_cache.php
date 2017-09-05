<?php

class pay_list_auto_cache extends auto_cache{
	private $key = "pay:list";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
			//unserialize(
			$sql = "select id,name,class_name,logo from ".DB_PREFIX."payment where is_effect = 1 and online_pay in (3,4) order by sort";
			$list = $GLOBALS['db']->getAll($sql,true,true);

			foreach ( $list as $k => $v )
			{
				$list[$k]['logo'] = get_spec_image($v['logo']);
			}
			
			$GLOBALS['cache']->set($this->key,$list,3600,true);
		}
		
		return $list;
	}
	
	public function rm($param)
	{
		$GLOBALS['cache']->rm($this->key);
	}
	
	public function clear_all()
	{
		$GLOBALS['cache']->rm($this->key);
	}
}
?>