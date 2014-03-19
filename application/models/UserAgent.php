<?php
class UserAgentModel{
	public function insertUserAgent(){
		$ua = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
		$content = date('Y/m/d').':'.$_SERVER['REQUEST_URI'];
		$mysql = new Mysql();
		$sql_select = 'select * from tbl_user';
		$result = $mysql->getRowsArray($sql_select);
		/*如果能查询到已经有相同得ua*/
		foreach ($result as $v){
			if($v['ua_ip'] == $ua){
				$sql_insert = "update tbl_user set content = '".$v['content'].","."$content' where ua_ip = '$ua'";
			}
		}
		
		/*如果没有能查询到已经有相同得ua*/
		if (empty($sql_insert)) {
			$sql_insert = "insert into tbl_user values ('','$ua','$content')";
		}
		//var_dump($sql_insert);exit();
		$mysql->mysql_query_rst($sql_insert);
		return true;
	}
}