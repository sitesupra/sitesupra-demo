<?php

namespace Supra\NestedSet\SearchCondition;

use Supra\NestedSet\Exception;

/**
 * @method SearchConditionAbstraction leftEqualsTo(int $value)
 * @method SearchConditionAbstraction leftLessThan(int $value)
 * @method SearchConditionAbstraction leftMoreThan(int $value)
 * @method SearchConditionAbstraction leftLessThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction leftMoreThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction leftNotEqualsTo(int $value)
 * @method SearchConditionAbstraction rightEqualsTo(int $value)
 * @method SearchConditionAbstraction rightLessThan(int $value)
 * @method SearchConditionAbstraction rightMoreThan(int $value)
 * @method SearchConditionAbstraction rightLessThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction rightMoreThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction rightNotEqualsTo(int $value)
 * @method SearchConditionAbstraction levelEqualsTo(int $value)
 * @method SearchConditionAbstraction levelLessThan(int $value)
 * @method SearchConditionAbstraction levelMoreThan(int $value)
 * @method SearchConditionAbstraction levelLessThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction levelMoreThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction levelNotEqualsTo(int $value)
 */
class SearchConditionAbstraction implements SearchConditionInterface
{
	const FIELD_POS = 0;
	const RELATION_POS = 1;
	const VALUE_POS = 2;

	const LEFT_FIELD = 'left';
	const RIGHT_FIELD = 'right';
	const LEVEL_FIELD = 'level';

	const RELATION_EQUALS = '=';
	const RELATION_LESS = '<';
	const RELATION_MORE = '>';
	const RELATION_LESS_OR_EQUALS = '<=';
	const RELATION_MORE_OR_EQUALS = '>=';
	const RELATION_NOT_EQUALS = '!=';

	private static $fields = array(
		self::LEFT_FIELD,
		self::RIGHT_FIELD,
		self::LEVEL_FIELD,
	);

	private static $relationMethods = array(
		self::RELATION_EQUALS => 'equalsTo',
		self::RELATION_LESS => 'lessThan',
		self::RELATION_MORE => 'moreThan',
		self::RELATION_LESS_OR_EQUALS => 'lessThanOrEqualsTo',
		self::RELATION_MORE_OR_EQUALS => 'moreThanOrEqualsTo',
		self::RELATION_NOT_EQUALS => 'notEqualsTo'
	);

	protected $conditions = array();

	public function add($field, $relation, $value)
	{
		$this->conditions[] = array(
				self::FIELD_POS => $field,
				self::RELATION_POS => $relation,
				self::VALUE_POS => $value
		);
	}

	public function __call($method, $arguments)
	{
		$fieldFound = false;
		$methodRemainder = $method;
		foreach (self::$fields as $field) {
			if (\stripos($method, $field) === 0) {
				$methodRemainder = substr($method, strlen($field));
				$fieldFound = $field;
				break;
			}
		}
		if ($fieldFound === false) {
			throw new Exception\InvalidOperation("Unknown method $method called for search condition object, no field match found");
		}
		
		$relationFound = false;
		foreach (self::$relationMethods as $relationTest => $relationString) {
			if (\strcasecmp($relationString, $methodRemainder) === 0) {
				$relationFound = $relationTest;
				break;
			}
		}
		if ($relationFound === false) {
			throw new Exception\InvalidOperation("Unknown method $method called for search condition object, no relation match found");
		}

		if ( ! isset($arguments[0])) {
			throw new Exception\InvalidOperation("No value passed to method $method");
		}
		$value = (int)$arguments[0];

		$this->add($fieldFound, $relationFound, $value);
		return $this;
	}
}