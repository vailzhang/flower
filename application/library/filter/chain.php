<?php

namespace filter;

class chain {
	protected $ruleList = array ();
	public function addRule ($filter, $params = NULL, $key = NULL) {
		if (is_string ( $filter ) || is_array ( $filter )) {
			$rule = array($filter, $params);
			if ($key) {
				$this->ruleList [$key] = $rule;
			} else {
				$this->ruleList [] = $rule;
			}
		} else {
			throw new \Exception ( 'inValid filter type' );
		}
	}
	public function filter($value) {
		foreach ( $this->ruleList as $key => $rule ) {
			list ( $filter, $params ) = $rule;
		
			if (is_string ( $filter )) {
				$class = '\\filter\\' . $filter;
				echo FILTER__PATH.$filter.'.php';exit;
				Yaf_loader::import(FILTER__PATH.$filter.'.php');
				$filter = new $class ();
			}
			if ($params) {
				foreach ($params as $paramName => $paramValue) {
					$method = 'set' . ucfirst($paramName);
					if (method_exists($filter, $method)) {
						$filter->$method ($paramValue);
					}
				}
			}
			$value = $filter->filter ( $value );
		}
		return $value;
	}
}