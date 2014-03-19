<?php

class UserController extends Yaf_Controller_Abstract {
	private $userModel;
	
	public function init(){
		if ($this->getRequest()->isXmlHttpRequest()) {
			//如果是Ajax请求, 关闭自动渲染, 由我们手工返回Json响应
			Yaf_Dispatcher::getInstance()->autoRender(FALSE);
		}
		$this->userModel = new UserModel();
	}
	
	public function loginAction() {
		$hp = new Yaf_Request_Http();
		$data = $hp->getPost();
		var_dump($data);exit;
		
		$mNews = new NewsModel();
		$model = $mNews->index();
		$this->_layout->meta_title = 'detail';
		$this->getView()->assign("model", $model);
	}
	public function registerAction(){
		$hp = new Yaf_Request_Http();
		$data = $hp->getPost();
		$reg = $this->userModel->register($data);
		if($reg){
			echo json_encode(array('isValid'=>true));
		}else{
			echo json_encode(array('isValid'=>false));
		}
	}
	public function validEmailAction(){
		//验证参数
		$params =  Yaf_Dispatcher::getInstance()->getRequest()->getParams();
		$hp = new Yaf_Request_Http();
		$email = $hp->getPost('value');
		if(empty($params['isReg'])){
			echo json_encode(array('isValid'=>false, 'errorMsg'=>'Param Error !','value'=>$email));
			exit;
		}
		//验证post数据
		if(!$email){
			echo json_encode(array('isValid'=>false, 'errorMsg'=>'Null Email !','value'=>$email));
			exit;
		}
		$target = $params['isReg'];
		$valid = $this->userModel->validateEmail($email);
		switch ($target){
			case 'y':		//注册
				if (!$valid) {
					echo json_encode(array('isValid'=>true,'value'=>$email));
				}else{
					echo json_encode(array('isValid'=>false, 'errorMsg'=>'该邮箱已经被别人注册过了','value'=>$email));
				}
				break;
			case 'n':		//登录
				if($valid){
					echo json_encode(array('isValid'=>true,'value'=>$email));
				}else{
					echo json_encode(array('isValid'=>false, 'errorMsg'=>'该邮箱还未注册,请先注册','value'=>$email));
				}
		}
		exit;
	}
}