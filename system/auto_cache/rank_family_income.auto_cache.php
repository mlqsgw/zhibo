<?php

class rank_family_income_auto_cache extends auto_cache{
	private $key = "rank:family_income:";
	public function load($param)
	{
		$rank_name = strim($param['rank_name']);
		$table = strim($param['table']);
		$page = intval($param['page']);
		$page_size = intval($param['page_size']);
		$cache_time = strim($param['cache_time']);
		$limit = (($page - 1) * $page_size) . "," . $page_size;

		$this->key .= $rank_name . '_' . $page;
	
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				if($rank_name=='month')
				{
					$sql = "select sum(vp.total_ticket) as contribution,f.`id`, f.`logo`, f.`name`, f.`notice`, f.`manifesto`, f.`user_id`, f.`user_count`, f.`create_time`, f.`create_date`,
f.`create_y`, f.`create_m`, f.`create_d`, f.`create_w`, f.`memo`, f.`status`, f.`family_level`, f.`video_time`, f.`score`, f.`fanwe_level`
from ".$table." as vp
LEFT JOIN ".DB_PREFIX."family f on vp.family_id=f.id
where f.status=1
GROUP BY vp.family_id
order BY 1 desc limit ".$limit;

					$list=$GLOBALS['db']->getAll($sql,true,true);

					$GLOBALS['cache']->set($this->key, $list, $cache_time, true);//缓存时间 86400秒 24h
					$GLOBALS['cache']->set($key_bf, $list, 864000, true);//备份
				}elseif($rank_name=='all')
				{
					$sql = "select f.* from ".DB_PREFIX."family as f where f.status=1
				order BY f.contribution desc limit ".$limit;

					$list=$GLOBALS['db']->getAll($sql,true,true);

					$GLOBALS['cache']->set($this->key, $list, $cache_time, true);//缓存时间 86400秒 24h
					$GLOBALS['cache']->set($key_bf, $list, 864000, true);//备份
				}

			}
 		}
 		
 		if ($list == false) $list = array();
 		
		return $list;
	}
	
	public function rm()
	{

		$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>