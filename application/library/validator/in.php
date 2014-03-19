<?php

namespace validator;

class in extends \library\validator {
	protected $message = 'invalid variable';
	protected $list;
	public function setList (array $list) {
		$this->list = $list;
	}
	public function isValid($value) {
		if (! in_array($value, $this->list)) {
			$this->error($value);
		}
		return $value;
	}
}