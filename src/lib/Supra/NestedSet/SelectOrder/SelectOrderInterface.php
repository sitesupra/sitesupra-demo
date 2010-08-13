<?php

namespace Supra\NestedSet\SelectOrder;

/**
 * @method SelectOrderInterface byLeft(int $direction)
 * @method SelectOrderInterface byLeftAscending()
 * @method SelectOrderInterface byLeftDescending()
 * @method SelectOrderInterface byRight(int $direction)
 * @method SelectOrderInterface byRightAscending()
 * @method SelectOrderInterface byRightDescending()
 * @method SelectOrderInterface byLevel(int $direction)
 * @method SelectOrderInterface byLevelAscending()
 * @method SelectOrderInterface byLevelDescending()
 */
interface SelectOrderInterface
{
	public function add($field, $direction);
}