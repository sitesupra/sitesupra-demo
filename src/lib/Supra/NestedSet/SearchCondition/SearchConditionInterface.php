<?php

namespace Supra\NestedSet\SearchCondition;

/**
 * @method SearchConditionInterface leftEqualsTo(int $value)
 * @method SearchConditionInterface leftLessThan(int $value)
 * @method SearchConditionInterface leftMoreThan(int $value)
 * @method SearchConditionInterface leftLessThanOrEqualsTo(int $value)
 * @method SearchConditionInterface leftMoreThanOrEqualsTo(int $value)
 * @method SearchConditionInterface leftNotEqualsTo(int $value)
 * @method SearchConditionInterface rightEqualsTo(int $value)
 * @method SearchConditionInterface rightLessThan(int $value)
 * @method SearchConditionInterface rightMoreThan(int $value)
 * @method SearchConditionInterface rightLessThanOrEqualsTo(int $value)
 * @method SearchConditionInterface rightMoreThanOrEqualsTo(int $value)
 * @method SearchConditionInterface rightNotEqualsTo(int $value)
 * @method SearchConditionInterface levelEqualsTo(int $value)
 * @method SearchConditionInterface levelLessThan(int $value)
 * @method SearchConditionInterface levelMoreThan(int $value)
 * @method SearchConditionInterface levelLessThanOrEqualsTo(int $value)
 * @method SearchConditionInterface levelMoreThanOrEqualsTo(int $value)
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
	const RELATION_MORE = '>';
	const RELATION_LESS_OR_EQUALS = '<=';
	const RELATION_MORE_OR_EQUALS = '>=';
	const RELATION_NOT_EQUALS = '!=';
	
	/**
	 * Add a condition to the collection
	 * @param string $field
	 * @param string $relation
	 * @param int $value
	 */
	public function add($field, $operator, $value);
}