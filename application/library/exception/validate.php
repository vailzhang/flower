<?php

namespace exception;

class validate extends \Exception {
	protected $error;
	protected $data;
	public function getError() {
		return $this->error;
	}
	public function getData() {
		return $this->data;
	}
	public function __construct($error, $data, $field = NULL) {
		if (! is_array ( $error ) && ! is_array ( $data ) && $field) {
			$error = array (
					$field => $error 
			);
			$data = array (
					$field => $data 
			);
		}
		$this->error = $error;
		$this->data = $data;
	}
	public function __toString() {
		$str = '';
		if (is_array ( $this->error )) {
			foreach ( $this->error as $key => $err ) {
				$str .= $key . ':' . $err . "\n";
			}
		} else {
			$str = $this->error;
		}
		return $str;
	}
}