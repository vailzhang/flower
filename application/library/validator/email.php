<?php

namespace validator;

class email extends \library\validator {
	protected $message = 'invalid email format';
	public function isValid($value) {
		$reg = '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/';
			
		if (preg_match ( $reg, ( string ) $value ) !== 1) {
			$this->error ($value);
		}
		return $value;
	}
}