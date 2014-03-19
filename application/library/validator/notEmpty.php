<?php

namespace validator;

class notEmpty extends \library\validator {
	protected $message = 'the value can not be empty';
	public function isValid($value) {
		if (empty ( $value )) {
			$this->error ($value);
		}
		return $value;
	}
}