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

use Supra\Core\NestedSet\Node\NodeInterface;
use Supra\Core\NestedSet\Exception;

/**
 * Sorting conditions for array nested set repository
 */
class ArraySelectOrder extends SelectOrderAbstraction
{
	/**
	 * Create closure of sorting rules used by usort() function
	 * @return \Closure
	 * @throws Exception\InvalidArgument
	 */
	public function getOrderClosure()
	{
		$orderRules = $this->orderRules;
		$orderClosure = function (NodeInterface $nodeA, NodeInterface $nodeB) use (&$orderRules) {
			foreach ($orderRules as $orderRule) {
				$field = $orderRule[SelectOrderInterface::FIELD_POS];
				switch ($field) {
					case SelectOrderInterface::LEFT_FIELD:
						$valueA = $nodeA->getLeftValue();
						$valueB = $nodeB->getLeftValue();
						break;
					case SelectOrderInterface::RIGHT_FIELD:
						$valueA = $nodeA->getRightValue();
						$valueB = $nodeB->getRightValue();
						break;
					case SelectOrderInterface::LEVEL_FIELD:
						$valueA = $nodeA->getLevel();
						$valueB = $nodeB->getLevel();
						break;
					default:
						throw new Exception\InvalidArgument("Field $field is not recognized");
				}

				$diff = $valueA - $valueB;
				if ($diff == 0) {
					continue;
				}

				$direction = $orderRule[SelectOrderInterface::DIRECTION_POS];
				switch ($direction) {
					case SelectOrderInterface::DIRECTION_ASCENDING:
						break;
					case SelectOrderInterface::DIRECTION_DESCENDING:
						$diff = ( - $diff);
						break;
					default:
						throw new Exception\InvalidArgument("Direction $direction is not recognized");
				}
				return $diff;
			}

			return null;
		};

		return $orderClosure;
	}
}