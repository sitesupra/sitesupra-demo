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
	/**
	 * Add a condition to the collection
	 * @param string $field
	 * @param string $relation
	 * @param int $value
	 */
	public function add($field, $operator, $value);
}