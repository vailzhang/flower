<?php

namespace validator;

class equal extends \library\validator {
	protected $message = '重复输入不正确';
	protected $target;
	public function setTarget (& $target) {
		$this->target = $target;
	}
	public function isValid(& $value) {
		$target = $this->target;
		if (is_array ( $target )) {
			foreach ( $target as $item ) {
				if ($value != $item) {
					$this->error ($value);
				}
			}
		} else {
			if ($value != $target) {
				$this->error ($value);
			}
		}
		return $value;
	}
}