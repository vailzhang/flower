<?php

namespace validator;

class string extends \library\validator {
	protected $lt;
	protected $lte;
	protected $gt;
	protected $gte;
	protected $message = array (
			'type' => 'require string',
			'lt' => '长度不能小于或等于{$lt}字符',
			'lte' => '长度不能小于{$lte}字符',
			'gt' => '长度不能超过或等于{$gt}字符',
			'gte' => '长度不能超过{$gte}字符' 
	);
	/**
	 * @param field_type $lt
	 */
	public function setLt($lt) {
		$this->lt = $lt;
	}

	/**
	 * @param field_type $lte
	 */
	public function setLte($lte) {
		$this->lte = $lte;
	}

	/**
	 * @param field_type $gt
	 */
	public function setGt($gt) {
		$this->gt = $gt;
	}

	/**
	 * @param field_type $gte
	 */
	public function setGte($gte) {
		$this->gte = $gte;
	}

	public function isValid($value) {
		if (! is_string ( $value )) {
			$this->error ($value, 'type' );
		}

		$len = mb_strlen ( $value );
		if ($this->lt && $len <= $this->lt) {
			$this->error ($value, 'lt' );
		}
		if ($this->lte && $len < $this->lte) {
			$this->error ($value, 'lte' );
		}
		if ($this->gt && $len > $this->gt) {
			$this->error ($value, 'gt' );
		}
		if ($this->gte && $len > $this->gte) {
			$this->error ($value, 'gte' );
		}

		return $value;
	}
}
