<?php

namespace Supra\NestedSet\SearchCondition;

use Supra\NestedSet\Node\NodeInterface;
use Supra\NestedSet\Exception;
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
	 * @throws Exception\InvalidArgument on invalid parameters
	 */
	function applyToQueryBuilder(QueryBuilder $qb, $parameterOffset = 0)
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
				case self::RELATION_MORE_OR_EQUALS:
					$where = $expr->gte($field, $valueExpr);
					break;
				case self::RELATION_LESS:
					$where = $expr->lt($field, $valueExpr);
					break;
				case self::RELATION_MORE:
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
		
		return $parameterOffset;
	}
	
	/**
	 * @param string $additionalCondition
	 */
	public function setAdditionalCondition($additionalCondition)
	{
		$this->additionalCondition = $additionalCondition;
	}
}