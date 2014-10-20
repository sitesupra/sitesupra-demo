<?php

namespace Supra\Core\NestedSet\SelectOrder;

use Supra\Core\NestedSet\Exception;

/**
 * Sorting conditions abstraction
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
	/**
	 * Possible sort fields
	 * @var array
	 */
	private static $fields = array(
		self::LEFT_FIELD,
		self::RIGHT_FIELD,
		self::LEVEL_FIELD
	);

	/**
	 * Possible sort directions with the textual representations
	 * @var array
	 */
	private static $directions = array(
		self::DIRECTION_ASCENDING => 'ascending',
		self::DIRECTION_DESCENDING => 'descending',
	);

	/**
	 * Collection of order rules
	 * @var array
	 */
	protected $orderRules = array();

	/**
	 * Add sorting rule
	 * @param string $field
	 * @param integer $direction
	 */
	public function add($field, $direction)
	{
		$this->orderRules[] = array(
				self::FIELD_POS => $field,
				self::DIRECTION_POS => $direction
		);
	}

	/**
	 * Magic method for addign sorting rules
	 * @param string $method
	 * @param array $arguments
	 * @return SelectOrderAbstraction
	 * @throws Exception\BadMethodCall
	 */
	public function __call($method, $arguments)
	{
		$methodRemainder = $method;

		if (stripos($method, 'by') === 0) {
			$methodRemainder = substr($method, 2);
		} else {
			throw new Exception\BadMethodCall("Unknown method $method called for search order object");
		}

		$fieldFound = false;
		foreach (self::$fields as $field) {
			if (stripos($methodRemainder, $field) === 0) {
				$methodRemainder = substr($methodRemainder, strlen($field));
				$fieldFound = $field;
				break;
			}
		}
		if ($fieldFound === false) {
			throw new Exception\BadMethodCall("Unknown method $method called for search order object, no field match found");
		}

		$direction = null;
		
		if ($methodRemainder != '') {
			foreach (self::$directions as $directionTest => $directionString) {
				if (strcasecmp($directionString, $methodRemainder) === 0) {
					$direction = $directionTest;
					break;
				}
			}
			if (is_null($direction)) {
				throw new Exception\BadMethodCall("Unknown method $method called for search order object, no relation match found");
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