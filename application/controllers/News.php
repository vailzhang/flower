<?php

class NewsController extends Yaf_Controller_Abstract {
	
	private $_layout;
	
	public function init(){
		$this->_layout = Yaf_Registry::get('layout');
	}
	
	public function detailAction() {//默认Action
		$mNews = new NewsModel();
		$model = $mNews->index();
		$this->_layout->meta_title = 'detail';
		$this->getView()->assign("model", $model);
	}
}