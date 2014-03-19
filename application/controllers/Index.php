<?php
class IndexController extends Yaf_Controller_Abstract {

    private $_layout;

    public function init(){
        $this->_layout = Yaf_Registry::get('layout');
    }

    public function indexAction() {//默认Action
    	/*存取访问记录*/
    	$userAgent = new UserAgentModel();
    	$userAgent->insertUserAgent();
		$mySql = new Mysql();
		$sql = 'select * from tbl_goods';
		$result = $mySql->getRowsArray($sql);
    	$this->_layout->meta_title = 'LIUHUI';
    	$this->getView()->assign('result',$result);
    	
    }
    
}
