<?php

namespace filter;

class data {
	protected $ruleList = array ();
	protected $defaultValue = array ();
	protected $bindValue = array ();
	protected $changeKeyList = array ();
	protected $notEmptyKeyList = array ();
	protected $filterField;

	public function addRule($field, $filter, $params = array()) {
		switch ($filter) {
			case 'Bind':
				$this->bindValue [$field] = $params;
				break;
			case 'Default':
				$this->defaultValue [$field] = $params;
				break;
			case 'ChangKey' :
				$this->changeKeyList [$field] = $params;
				break;
			case 'RemoveEmpty' :
				$this->notEmptyKeyList [] = $field;
				break;
			case 'FilterField' :
				$this->filterField = $field;
				break;
			default :
				$this->ruleList [] = array (
						'field' => $field,
						'filter' => $filter,
						'params' => $params
				);
				break;
		}
	}

	public function setRules(array $ruleList) {
		foreach ( $ruleList as $rule ) {
			call_user_func_array ( array (
					$this,
					'addRule' 
			), $rule );
		}
	}
	protected function generateChainList(array $dataFields) {
		$chainList = array ();
		foreach ( $this->ruleList as $rule ) {
			if ($rule ['field'] == '*') {
				$fields = $dataFields;
			} elseif (strpos ( $rule ['field'], '~' ) === 0) {
				$exFields = explode ( ',', substr ( $rule ['field'], 1 ) );
				$fields = array_diff ( $dataFields, $exFields );
			} elseif (strpos ( $rule ['field'], ',' ) !== FALSE) {
				$fields = explode ( ',', $rule ['field'] );
			} else {
				$fields = $rule ['field'];
			}

			unset($rule ['field']);
			foreach ( ( array ) $fields as $field ) {
				if (! isset ( $chainList [$field] )) {
					$chainList [$field] = new \filter\chain ();
				}
				$chainList [$field]->addRule ( $rule ['filter'], $rule ['params'] );
			}
		}
		
		return $chainList;
	}
	public function filter($data) {
		$dataFields = array_keys($data);

		if ($this->filterField) {
			$fieldList = $this->parserFields($this->filterField, $dataFields);
			$data = array_intersect_key ( $data, array_fill_keys ( $fieldList, 1 ) );
		}

		if ($this->defaultValue) {
			foreach ($this->defaultValue as $fieldTag => $value) {
				foreach ($this->parserFields($fieldTag, $dataFields) as $field) {
					if (! isset ( $data [$field] )) {
						$data [$field] = $value;
					}
				}
			}
		}
		
		if ($this->notEmptyKeyList) {
			foreach ( $this->notEmptyKeyList as $fieldTag ) {
				foreach ($this->parserFields($fieldTag, $dataFields) as $field) {
					if (empty ( $data [$field] )) {
						unset ( $data [$field] );
					}
				}
			}
		}
		
		if ($this->bindValue) {
			foreach ($this->bindValue as $fieldTag => $value) {
				foreach ($this->parserFields($fieldTag, $dataFields) as $field) {
					$data [$field] = $value;
				}
			}
		}

		$chainList = $this->generateChainList ( $dataFields );
		foreach ( $chainList as $field => $filterChain ) {
			if (isset($data [$field])) {
				Yaf_loader::import(FILTER__PATH.'/chain.php');
				$data [$field] = $filterChain->filter ( $data [$field] );
			}
		}


		if ($this->changeKeyList) {
			foreach ( $this->changeKeyList as $oldFieldName => $newFieldName ) {
				if (isset($data [$oldFieldName])) {
					$data [$newFieldName] = $data [$oldFieldName];
					unset ( $data [$oldFieldName] );
				}
			}
		}
		
		return $data;
	}

	protected function parserFields($fieldTag, $dataFields) {
		if ($fieldTag == '*') {
			$fields = $dataFields;
		} elseif (strpos ( $fieldTag, '-' ) === 0) {
			$exFields = explode ( ',', substr ( $fieldTag, 1 ) );
			$exFields = array_map ( 'trim', $exFields );
			$fields = array_diff ( $dataFields, $exFields );
		} elseif (strpos ( $fieldTag, ',' ) !== FALSE) {
			$fields = explode ( ',', $fieldTag );
			$fields = array_map ( 'trim', $fields );
		} else {
			$fields = ( array ) $fieldTag;
		}
		return $fields;
	}
}