<?php

namespace filter;

class password {
	public function filter($value) {
		echo 1;exit;
		return md5( '123' . $value . 'youhuo');
	}
}