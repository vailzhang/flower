<?php

namespace library;

abstract class validator {
	protected $message = 'invalid param';
	public function setMessage($message) {
		$this->message = $message;
	}
	public function setParams(array $params) {
		foreach ( $params as $key => $option ) {
			$method = 'set' . ucfirst ( $key );
			if (method_exists ( $this, $method )) {
				$this->$method ( $option );
			} else {
				throw new \Exception('unknow param set :"' . $key . '"');
			}
		}
	}
	public function isValid($value) {
		if ($value) {
			return $value;
		} else {
			$this->error ( $value );
		}
	}
	protected function error($value, $type = NULL, $field = NULL) {
		if (is_string ( $this->message )) {
			$message = $this->message;
		} elseif (is_array ( $this->message )) {
			if ($type && isset ( $this->message [$type] )) {
				$message = $this->message [$type];
			} else {
				$message = current ( $this->message );
			}
		}
		
		if (strpos ( $message, '{$' ) !== false) {
			if (strpos ( $message, '{$value}' ) !== false) {
				$message = str_replace ( '{$value}', ( string ) $value, $message );
			}
			if (strpos ( $message, '{$' ) !== false) {
				foreach ( get_object_vars ( $this ) as $key => $val ) {
					$message = str_replace ( '{$' . $key . '}', ( string ) $val, $message );
				}
			}
		}
		\Yaf_Loader::import(VALID_EXCEP_PATH.'validate.php');
		throw new \exception\validate ( $message, $value, $field );
	}
}