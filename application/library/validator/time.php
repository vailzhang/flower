<?php

namespace validator;

class time extends \library\validator {
	protected $message = 'invalid time format';
	protected $format;
	public function setFormat ($format) {
		$this->format = $format;
	}
	public function isValid($value) {
		if (!$value || $value == '至今') {
			return $value;
		}
		if (date($this->format, strtotime($value)) != $value) {
			$this->error($value);
		}
		return $value;
	}
}