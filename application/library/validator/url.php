<?php

namespace validator;

class url extends \library\validator {
	protected $message = 'invalid url format';
	public function isValid($value) {
		if(!preg_match('/[a-zA-z]+:\/\/[^\s]*/', $value)){
			$this->error($value);
		}
		return $value;
	}
}
