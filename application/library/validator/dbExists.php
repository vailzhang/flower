<?php

namespace validator;

class dbExists extends \library\validator {
	protected $collection;
	protected $field;
	protected $isExists;
	protected $message = array (
			'exists' => 'the value is existed',
			'notExists' => 'the value is not existed' 
	);
	public function getCollection() {
		return $this->collection;
	}
	public function getIsExists() {
		return $this->isExists;
	}
	public function setCollection($collection) {
		$this->collection = $collection;
	}
	public function setField ($field) {
		$this->field = $field;
	}
	public function setIsExists($isExists) {
		$this->isExists = $isExists;
	}
	public function isValid($value) {
		$field = $this->field;
		$isExists = $this->isExists;
		$collection = \collection ( $this->collection );
		$where = array(
				$field => $value
		);
		$exists = $collection->findOne ($where);
		
		if ($isExists && ! $exists) {
			$this->error ( $value, 'notExists' );
		} elseif ((! $isExists) && $exists) {
			$this->error ( $value, 'exists' );
		}
		
		return $value;
	}
}