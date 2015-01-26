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

use Doctrine\ORM\QueryBuilder;
use Supra\Core\NestedSet\Exception;

/**
 * Sorting conditions for database nested set repository
 */
class DoctrineSelectOrder extends SelectOrderAbstraction
{
	/**
	 * Add order rules to the query builder
	 * NB! The alias of the main table must be "e".
	 * @param QueryBuilder $qb
	 * @throws Exception\InvalidArgument
	 */
	public function applyToQueryBuilder(QueryBuilder $qb, &$parameterOffset = 0)
	{
		$orderRules = $this->orderRules;
		foreach ($orderRules as $orderRule) {
			$field = $orderRule[self::FIELD_POS];
			switch ($field) {
				case self::LEFT_FIELD:
				case self::RIGHT_FIELD:
				case self::LEVEL_FIELD:
					break;
				default:
					throw new Exception\InvalidArgument("Field $field is not recognized");
			}

			$field = "e.$field";
			$directionSQL = null;;

			$direction = $orderRule[self::DIRECTION_POS];
			switch ($direction) {
				case self::DIRECTION_ASCENDING:
					$directionSQL = 'ASC';
					break;
				case self::DIRECTION_DESCENDING:
					$directionSQL = 'DESC';
					break;
				default:
					throw new Exception\InvalidArgument("Direction $direction is not recognized");
			}
			$qb->addOrderBy($field, $directionSQL);
		}
	}
}