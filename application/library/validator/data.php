<?php

namespace validator;

use library\validator;

class data extends validator {
	protected $fields = array ();
	protected $ruleList = array ();
	protected $requireFields = array ();
	protected $acceptUnknowFields = false;
	protected $breakOnFailure = false;
	public function setAcceptUnknowFields($isAccept) {
		$this->acceptUnknowFields = ( boolean ) $isAccept;
	}
	public function getBreakOnFailure() {
		return $this->breakOnFailure;
	}
	public function setBreakOnFailure($breakOnFailure) {
		$this->breakOnFailure = ( boolean ) $breakOnFailure;
	}
	public function setFields($fields) {
		if (is_string ( $fields )) {
			$fields = explode ( ',', $fields );
			$fields = array_map ( 'trim', $fields );
		}
		$this->fields = ( array ) $fields;
	}
	public function setRules(array $ruleList) {
		foreach ( $ruleList as $rule ) {
			call_user_func_array ( array (
					$this,
					'addRule' 
			), $rule );
		}
	}
	public function addRule($field, $validator, array $params = array()) {
		if ($validator == 'Require') {
			$this->requireFields = array_merge ( $this->requireFields, $this->parserFields ( $field ) );
			$this->requireFields = array_unique ( $this->requireFields );
		} else {
			$this->ruleList [] = array (
					'field' => $field,
					'validator' => $validator,
					'params' => $params 
			);
		}
	}
	protected function parserFields($fieldTag) {
		if ($fieldTag == '*') {
			$fields = $this->fields;
		} elseif (strpos ( $fieldTag, '-' ) === 0) {
			$exFields = explode ( ',', substr ( $fieldTag, 1 ) );
			$exFields = array_map ( 'trim', $exFields );
			$fields = array_diff ( $this->fields, $exFields );
		} elseif (strpos ( $fieldTag, ',' ) !== FALSE) {
			$fields = explode ( ',', $fieldTag );
			$fields = array_map ( 'trim', $fields );
		} else {
			$fields = ( array ) $fieldTag;
		}
		return $fields;
	}
	public function isValid(array $data) {
		
		if (! $this->acceptUnknowFields) {
			$unknowFields = array_diff_key ( $data, array_fill_keys ( $this->fields, 1 ) );
			if ($unknowFields) {
				$unknowFields = implode ( ',', array_keys ( $unknowFields ) );
				throw new \exception\validate ( 'unknow params : ' . $unknowFields, $data );
			}
		}
		
		if ($this->requireFields) {
			$requireParams = array_diff_key ( array_fill_keys ( $this->requireFields, 1 ), $data);
			if ($requireParams) {
				$requireParams = implode ( ',', array_keys ( $requireParams ) );
				throw new \exception\validate ( 'require params : ' . $requireParams, $data );
			}
		}

		$error = array();
		$return = $data;
		$chainList = $this->generateChainList ();
		foreach ( $chainList as $field => $validatorChain ) {
			if (! isset ( $data [$field] )) {
				continue;
			}
			
			try {
				$return [$field] = $validatorChain->isValid ( $data [$field] );
			} catch (\exception\validate $e) {
				$error [$field] = $e->getError();
				
				if ($this->breakOnFailure) {
					break;
				}
			}
		}
		
		if ($error) {
			throw new \exception\validate($error, $data);
		}	
		return $return;
	}

	protected function generateChainList() {
		$chainList = array ();
		foreach ( $this->ruleList as $rule ) {
			$fields = $this->parserFields ( $rule ['field'] );
			foreach ( $fields as $field ) {
				if (! isset ( $chainList [$field] )) {
					$chainList [$field] = new \validator\chain ();
				}
				$chainList [$field]->addRule ( $rule['validator'], $rule['params']);
			}
		}
		return $chainList;
	}
}