<?php 
// Yaf_loader::import('Collection.php');
class NewsModel {
	public function index() {
    	$collection = new Collection('news');
    	$data = $collection->find ( array (
    			'$or' => array (
    					array (
    							'tag' => 'hot'
    					),
    					array (
    							'tag' => 'warm'
    					)
    			)
    	) )->limit(20);
    	return iterator_to_array($data);
    }
}