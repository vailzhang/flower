<?php 
/*
 * redis工厂类
 */
class RedisFactory
{
	private static $_instance = null;
	private function __construct(){}
	/*
	 * 链接redis
	* */
	public static function getInstance()
	{
		if (!isset(self::$_instance) || !self::$_instance){
			$redis_config = EnvConfig::getCurrentRedisConfig();
			$redis = new Redis();
			$redis->connect($redis_config['host'], $redis_config['port'], 5);
			self::$_instance = $redis;
		}
		return self::$_instance;
	}
	/*
	 * 得到key值
	 * */
	public function findByKey($key)
	{
		$res = $this->redis->hGetAll($key);
		if (!$res)
			$res = $this->redis->get($key);
		return $res;
	}
	/*
	 * 保存key值
	* */
	public function saveByKey($key,$value)
	{
		$this->redis->set($key,$value);
	}
	/*
	 * 删除key值
	* */
	public function deleteByKey($key)
	{
		$this->redis->del($key);
	}
	/*
	 * hash方式
	* */
	public function addNoChangeBsData($key,$info){
		$this->redis->hSet('NoChangeBs',$key,$info);
	}
	/*
	 * hash方式
	* */
	public function getNoChangeBsData($key){
		return $this->redis->hGet('NoChangeBs',$key);
	}
	/*
	 * hash方式
	* */
	public function getAllNoChangeBsData(){
		return $this->redis->hGetAll('NoChangeBs');
	}
}