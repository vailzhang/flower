<?php

namespace validator;

class is extends \library\validator {
	protected $message = 'invalid variable type';
	protected $type;
	public function setType ($type) {
		$this->type = $type;
	}
	public function isValid($value) {
		switch ($this->type) {
			case 'array':
				if (! is_array($value)) {
					$this->error($value);
				}
				break;
			default:
				if (! is_a($value, $this->type)) {
					$this->error($value);
				}
		}
		return $value;
	}
}