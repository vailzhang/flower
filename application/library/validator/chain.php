<?php

namespace validator;

use library\validator;

class chain extends validator {
	protected $ruleList;
	public function addRule($validator, $params = array(), $type = NULL) {
		if (is_string ( $validator ) || is_array ( $validator ) || is_a ( $validator, '\common\validator' )) {
			$rule = array (
					$validator,
					$params 
			);
			if ($type) {
				$this->ruleList [$type] = $rule;
			} else {
				$this->ruleList [] = $rule;
			}
		} else {
			throw new \Exception ( 'inValid validator type' );
		}
	}
	public function isValid($value) {
		$return = $value;
		foreach ( $this->ruleList as $key => $rule ) {
			list ( $validator, $params ) = $rule;
			
			if (is_string ( $validator )) {
				$class = '\\validator\\' . $validator;
				\Yaf_Loader::import(VALID_PATH.$validator.'.php');
				$validator = new $class ();
			}
			$validator->setParams ( $params );
			$return = $validator->isValid ($return);
		}
		return $return;
	}
}