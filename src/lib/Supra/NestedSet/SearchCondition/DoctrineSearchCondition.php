<?php

namespace Supra\NestedSet\SearchCondition;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Exception,
		Doctrine\ORM\QueryBuilder;

/**
 * 
 */
class DoctrineSearchCondition extends SearchConditionAbstraction
{
	function getSearchDQL(QueryBuilder $qb)
	{
		$expr = $qb->expr();

		$conditions = $this->conditions;
		foreach ($conditions as $condition) {
			$field = $condition[ArraySearchCondition::FIELD_POS];
			switch ($field) {
				case SearchConditionAbstraction::LEFT_FIELD:
				case SearchConditionAbstraction::RIGHT_FIELD:
				case SearchConditionAbstraction::LEVEL_FIELD:
					break;
				default:
					throw new Exception\InvalidOperation("Field $field is not recognized");
			}

			$field = "e.$field";

			$relation = $condition[ArraySearchCondition::RELATION_POS];
			$value = $condition[ArraySearchCondition::VALUE_POS];
			switch ($relation) {
				case SearchConditionAbstraction::RELATION_EQUALS:
					$where = $expr->eq($field, $value);
					break;
				case SearchConditionAbstraction::RELATION_LESS_OR_EQUALS:
					$where = $expr->lte($field, $value);
					break;
				case SearchConditionAbstraction::RELATION_MORE_OR_EQUALS:
					$where = $expr->gte($field, $value);
					break;
				case SearchConditionAbstraction::RELATION_LESS:
					$where = $expr->lt($field, $value);
					break;
				case SearchConditionAbstraction::RELATION_MORE:
					$where = $expr->gt($field, $value);
					break;
				case SearchConditionAbstraction::RELATION_NOT_EQUALS:
					$where = $expr->neq($field, $value);
					break;
				default:
					throw new Exception\InvalidOperation("Relation $relation is not recognized");
			}

			$qb->andWhere($where);
			
		}

		return $qb;
	}
}