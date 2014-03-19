<?php

namespace filter;

class arrayKey {
	protected $keys;
	public function setKeys($keys) {
		if (is_string ( $keys ) && strpos ( $keys, ',' ) !== FALSE) {
			$keys = explode ( ',', $keys );
			$keys = array_map ( 'trim', $keys );
		}
		$this->keys = ( array ) $keys;
	}
	public function filter($value) {
		return array_intersect_key ( $value, array_fill_keys ( $this->keys, 1 ) );
	}
}