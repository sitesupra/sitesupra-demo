<?php

namespace Supra\NestedSet\SearchOrder;

/**
 * @method SearchOrderInterface byLeft(int $direction)
 * @method SearchOrderInterface byLeftAscending()
 * @method SearchOrderInterface byLeftDescending()
 * @method SearchOrderInterface byRight(int $direction)
 * @method SearchOrderInterface byRightAscending()
 * @method SearchOrderInterface byRightDescending()
 * @method SearchOrderInterface byLevel(int $direction)
 * @method SearchOrderInterface byLevelAscending()
 * @method SearchOrderInterface byLevelDescending()
 */
interface SearchOrderInterface
{
	public function add($field, $direction);
}