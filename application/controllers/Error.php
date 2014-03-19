<?php 
class ErrorController extends Yaf_Controller_Abstract {
	public function errorAction($exception) {
		switch ($exception->getCode ()) {
			case YAF_ERR_LOADFAILD :
			case YAF_ERR_LOADFAILD_MODULE :
			case YAF_ERR_LOADFAILD_CONTROLLER :
			case YAF_ERR_LOADFAILD_ACTION :
				header ( "Not Found" ); // 404
				break;
			case CUSTOM_ERROR_CODE :
				// 自定义的异常
				$this->getView()->assign("code", $exception->getCode());
				$this->getView()->assign("message", $exception->getMessage());
				break;
			default:
				$this->getView()->assign("code", $exception->getCode());
				$this->getView()->assign("message", $exception->getMessage());
		}
	}
}