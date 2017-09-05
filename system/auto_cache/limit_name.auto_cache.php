<?php

class limit_name_auto_cache extends auto_cache{
	private $key = "limit:name";
	
	public function load($param)
	{
	    $type = isset($_POST['type']) ? $_POST['type'] : 1;
	    $this->key = $this->key.$type;
		$list = $GLOBALS['cache']->get($this->key);
		if($list === false)
		{
            $m_config =  load_auto_cache("m_config");
            $sql = "select name from ".DB_PREFIX."limit_name";

            $list = $GLOBALS['db']->getAll($sql,true,true);
           
			foreach ( $list as $k => $v )
			{
				$list[$k] = $v['name'];
			}
	
			$GLOBALS['cache']->set($this->key,$list);
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