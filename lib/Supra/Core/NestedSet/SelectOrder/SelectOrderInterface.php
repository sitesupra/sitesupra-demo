<?php

namespace Supra\Core\NestedSet\SelectOrder;

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
	const FIELD_POS = 0;
	const DIRECTION_POS = 1;

	const LEFT_FIELD = 'left';
	const RIGHT_FIELD = 'right';
	const LEVEL_FIELD = 'level';

	const DIRECTION_ASCENDING = 1;
	const DIRECTION_DESCENDING = -1;
	
	/**
	 * Add sorting rule
	 * @param string $field
	 * @param integer $direction
	 */
	public function add($field, $direction);
}