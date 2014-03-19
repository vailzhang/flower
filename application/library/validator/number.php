<?php

namespace validator;

class number extends \library\validator {
	protected $int;
	protected $lt;
	protected $lte;
	protected $gt;
	protected $gte;
	protected $message = array(
			'type' => '请输入数字',
			'int' => '请输入整数',
			'lt' => '不能小于或等于{$lt}',
			'lte' => '不能小于{$lte}',
			'gt' => '不能大于或等于{$gt}',
			'gte' => '不能大于或等于{$gte}'
	);
	/**
	 * @param field_type $isInt
	 */
	public function setInt($isInt) {
		$this->int = $isInt;
	}

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
		if (! is_numeric($value)) {
			$this->error($value, 'int');
		}
		$num = floatval($value);
		if ($this->int) {
			$num = intval($value);
			if ($num != $value) {
				$this->error($value, 'int');
			}
		}
		if ($this->lt && $num <= $this->lt) {
			$this->error($value, 'lt');
		}
		if ($this->lte && $num < $this->lte) {
			$this->error($value, 'lte');
		}
		if ($this->gt && $num >= $this->gt) {
			$this->error($value, 'gt');
		}
		if ($this->gte && $num > $this->gte) {
			$this->error($value, 'gte');
		}
		return $num;
	}
}