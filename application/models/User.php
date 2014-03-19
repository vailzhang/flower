<?php
use validator\data;
class UserModel {
	private $collection;
	public function __construct(){
		$this->collection = new Collection ( 'user' );
	}
	public function validateEmail($email) {
		$email = (string) $email;
		return $this->collection->findOne ( array (
				'email' => $email 
		) );
	}
	public function register($_data) {
		if (empty ( $_data ['email'] ) || empty ( $_data ['password'] )) {
			return false;
		}
		Yaf_loader::import(APP_PATH.'/application/library/validator.php');
		$validator = new data();
		$validator->setFields('email, password, confirm-password');
		$validator->addRule('*', 'Require');
		$validator->addRule('password', 'password');
		$validator->addRule('email', 'email');
// 		$validator->addRule('email', 'dbExists', array( // 检测用户名是否唯一
// 				'collection' => 'user', 'field' => 'email', 'isExists' => false, 'message' => '该邮件地址已被注册'));
		
		$validator->addRule ( 'password','equal', array (// 检测两次密码是否相同
				'target' => $_data['confirm-password'],
				'message' => '两次密码不相同'
		));
 		$data = $validator->isValid($_data);
 		Yaf_loader::import(APP_PATH.'/application/library/filter/data.php');
 		$filter = new \filter\data();
 		$filter->addRule('password', 'password');
 		$data = $filter->filter($data);
		var_dump($data);exit;
		$data = array (
				'email' => $data ['email'],
				'password' => md5 ( 'gongfu' . $data ['password'] ) 
		);
		try {
			$this->collection->insert($data);		
		}catch (MongoCursorException $e){
			return false;
		}
		return true;
	}
}