<?php

namespace validator;

class mongoId extends \library\validator {
	protected $message = 'invalid mongoId format';
	public function isValid($value) {
		if (! is_a ( $value, 'MongoId' )) {
			if (is_string ( $value ) && strlen ( $value ) == 24) {
				$value = new \mongoId ( $value );
			} else {
				$this->error ($value);
			}
		}
		return $value;
	}
}