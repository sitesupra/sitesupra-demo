<?php

namespace Supra\NestedSet\SelectOrder;

use Supra\NestedSet\Exception;

/**
 * @method SelectOrderAbstraction byLeft(int $direction)
 * @method SelectOrderAbstraction byLeftAscending()
 * @method SelectOrderAbstraction byLeftDescending()
 * @method SelectOrderAbstraction byRight(int $direction)
 * @method SelectOrderAbstraction byRightAscending()
 * @method SelectOrderAbstraction byRightDescending()
 * @method SelectOrderAbstraction byLevel(int $direction)
 * @method SelectOrderAbstraction byLevelAscending()
 * @method SelectOrderAbstraction byLevelDescending()
 */
class SelectOrderAbstraction implements SelectOrderInterface
{
	const FIELD_POS = 0;
	const DIRECTION_POS = 1;

	const LEFT_FIELD = 'left';
	const RIGHT_FIELD = 'right';
	const LEVEL_FIELD = 'level';

	const DIRECTION_ASCENDING = 1;
	const DIRECTION_DESCENDING = -1;

	private static $fields = array(
		self::LEFT_FIELD,
		self::RIGHT_FIELD,
		self::LEVEL_FIELD
	);

	private static $directions = array(
		self::DIRECTION_ASCENDING => 'ascending',
		self::DIRECTION_DESCENDING => 'descending',
	);

	protected $orderRules = array();

	public function add($field, $direction)
	{
		$this->orderRules[] = array(
				self::FIELD_POS => $field,
				self::DIRECTION_POS => $direction
		);
	}

	public function __call($method, $arguments)
	{
		$methodRemainder = $method;

		if (\stripos($method, 'by') === 0) {
			$methodRemainder = substr($method, 2);
		} else {
			throw new Exception\InvalidOperation("Unknown method $method called for search order object");
		}

		$fieldFound = false;
		foreach (self::$fields as $field) {
			if (\stripos($methodRemainder, $field) === 0) {
				$methodRemainder = substr($methodRemainder, strlen($field));
				$fieldFound = $field;
				break;
			}
		}
		if ($fieldFound === false) {
			throw new Exception\InvalidOperation("Unknown method $method called for search order object, no field match found");
		}

		if ($methodRemainder != '') {
			$direction = false;
			foreach (self::$directions as $directionTest => $directionString) {
				if (\strcasecmp($directionString, $methodRemainder) === 0) {
					$direction = $directionTest;
					break;
				}
			}
			if ($direction === false) {
				throw new Exception\InvalidOperation("Unknown method $method called for search order object, no relation match found");
			}
		} else {
			if ( ! isset($arguments[0])) {
				$direction = self::DIRECTION_ASCENDING;
			} else {
				$direction = (int)$arguments[0];
				if ($direction > 0) {
					$direction = self::DIRECTION_ASCENDING;
				} else {
					$direction = self::DIRECTION_DESCENDING;
				}
			}
		}

		$this->add($fieldFound, $direction);
		return $this;
	}
}