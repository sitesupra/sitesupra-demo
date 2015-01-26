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

namespace Supra\Core\NestedSet\SearchCondition;

use Supra\Core\NestedSet\Exception;
use Doctrine\ORM\QueryBuilder;

/**
 * Search condition class for doctrine
 */
class DoctrineSearchCondition extends SearchConditionAbstraction
{
	/**
	 * Additional condition
	 * @var string
	 */
	private $additionalCondition;
	
	/**
	 * Adds search filter to the query builder passed.
	 * NB! The alias of the main table must be "e".
	 * @param QueryBuilder $qb
	 * @param integer $parameterOffset
	 * @throws Exception\InvalidArgument on invalid parameters
	 */
	public function applyToQueryBuilder(QueryBuilder $qb, &$parameterOffset = 0)
	{
		$expr = $qb->expr();

		$conditions = $this->conditions;
		foreach ($conditions as $condition) {
			$field = $condition[self::FIELD_POS];
			switch ($field) {
				case self::LEFT_FIELD:
				case self::RIGHT_FIELD:
				case self::LEVEL_FIELD:
					break;
				default:
					throw new Exception\InvalidArgument("Field $field is not recognized");
			}

			$where = null;
			$field = "e.$field";
			$parameterIndex = $parameterOffset;
			$parameterOffset++;

			$relation = $condition[self::RELATION_POS];
			$value = $condition[self::VALUE_POS];
			$valueExpr = '?' . $parameterIndex;
			
			switch ($relation) {
				case self::RELATION_EQUALS:
					$where = $expr->eq($field, $valueExpr);
					break;
				case self::RELATION_LESS_OR_EQUALS:
					$where = $expr->lte($field, $valueExpr);
					break;
				case self::RELATION_GREATER_OR_EQUALS:
					$where = $expr->gte($field, $valueExpr);
					break;
				case self::RELATION_LESS:
					$where = $expr->lt($field, $valueExpr);
					break;
				case self::RELATION_GREATER:
					$where = $expr->gt($field, $valueExpr);
					break;
				case self::RELATION_NOT_EQUALS:
					$where = $expr->neq($field, $valueExpr);
					break;
				default:
					throw new Exception\InvalidArgument("Relation $relation is not recognized");
			}

			$qb->andWhere($where)
					->setParameter($parameterIndex, $value);
		}
		
		if ( ! empty($this->additionalCondition)) {
			$qb->andWhere($this->additionalCondition);
		}
	}
	
	/**
	 * @param string $additionalCondition
	 */
	public function setAdditionalCondition($additionalCondition)
	{
		$this->additionalCondition = $additionalCondition;
	}
}