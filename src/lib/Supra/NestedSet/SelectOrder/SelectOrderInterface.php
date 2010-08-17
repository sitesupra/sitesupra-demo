<?php

namespace Supra\NestedSet\SelectOrder;

/**
 * Sorting conditions interface
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
	/**
	 * Add sorting rule
	 * @param string $field
	 * @param integer $direction
	 */
	public function add($field, $direction);
}