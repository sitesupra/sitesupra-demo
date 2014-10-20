<?php

namespace Supra\Core\NestedSet\SearchCondition;

/**
 * @method SearchConditionInterface leftEqualsTo(int $value)
 * @method SearchConditionInterface leftLessThan(int $value)
 * @method SearchConditionInterface leftGreaterThan(int $value)
 * @method SearchConditionInterface leftLessThanOrEqualsTo(int $value)
 * @method SearchConditionInterface leftGreaterThanOrEqualsTo(int $value)
 * @method SearchConditionInterface leftNotEqualsTo(int $value)
 * @method SearchConditionInterface rightEqualsTo(int $value)
 * @method SearchConditionInterface rightLessThan(int $value)
 * @method SearchConditionInterface rightGreaterThan(int $value)
 * @method SearchConditionInterface rightLessThanOrEqualsTo(int $value)
 * @method SearchConditionInterface rightGreaterThanOrEqualsTo(int $value)
 * @method SearchConditionInterface rightNotEqualsTo(int $value)
 * @method SearchConditionInterface levelEqualsTo(int $value)
 * @method SearchConditionInterface levelLessThan(int $value)
 * @method SearchConditionInterface levelGreaterThan(int $value)
 * @method SearchConditionInterface levelLessThanOrEqualsTo(int $value)
 * @method SearchConditionInterface levelGreaterThanOrEqualsTo(int $value)
 * @method SearchConditionInterface levelNotEqualsTo(int $value)
 */
interface SearchConditionInterface
{
	const FIELD_POS = 0;
	const RELATION_POS = 1;
	const VALUE_POS = 2;

	const LEFT_FIELD = 'left';
	const RIGHT_FIELD = 'right';
	const LEVEL_FIELD = 'level';

	const RELATION_EQUALS = '==';
	const RELATION_LESS = '<';
	const RELATION_GREATER = '>';
	const RELATION_LESS_OR_EQUALS = '<=';
	const RELATION_GREATER_OR_EQUALS = '>=';
	const RELATION_NOT_EQUALS = '!=';
	
	/**
	 * Add a condition to the collection
	 * @param string $field
	 * @param string $relation
	 * @param int $value
	 */
	public function add($field, $operator, $value);
}