<?php
namespace filter;

class number {
	public function filter($value) {
		return floatval($value);
	}
}