<?php
class Collection extends \MongoCollection {
	public function __construct($name) {
		static $db = NULL;
		if (! $db) {
			$mongo = new \MongoClient (); // connect
			$db = $mongo->selectDB ( 'movie' );
		}
		parent::__construct ( $db, $name );
	}
}