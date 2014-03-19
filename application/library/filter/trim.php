<?php

namespace filter;
class trim {
	public function filter($value) {
		if (is_string($value)) {
			$value = trim ( $value );
		} else if (is_array($value)) {
			$value = array_map(array($this, 'filter'), $value);	
		}
		return $value;
	}
}