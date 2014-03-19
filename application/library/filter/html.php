<?php

namespace filter;

class html {
	public function filter($value) {
		return htmlentities ( $value, ENT_QUOTES, "UTF-8" );
	}
}