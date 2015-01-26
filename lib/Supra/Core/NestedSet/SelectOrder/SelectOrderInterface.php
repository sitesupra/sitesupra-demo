<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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