<?php

namespace validator;

class password extends \library\validator {
	protected $message = '请输入6-20位密码';
	public function isValid($value) {
		
		$strLen = mb_strlen($value);
		if ( ! ($strLen >= 6 && $strLen <= 20) ) {
			$this->error ($value);
		}
		
		return $value;
	}
}
