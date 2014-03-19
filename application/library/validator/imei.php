<?php

namespace validator;

class imei extends \library\validator {
	protected $message = 'invalid imei format';
	public function isValid($value) {
		$reg = '/^\d{15}$/';

		if ($value && preg_match ( $reg, ( string ) $value ) !== 1) {
			$this->error ($value);
		}
		return $value;
	}
}