<?php

namespace Supra\NestedSet\SelectOrder;

use Supra\NestedSet\Node\NodeInterface;
use Supra\NestedSet\Exception;
use Doctrine\ORM\QueryBuilder;

/**
 * Sorting conditions for database nested set repository
 */
class DoctrineSelectOrder extends SelectOrderAbstraction
{
	/**
	 * Add order rules to the query builder
	 * NB! The alias of the main table must be "e".
	 * @param QueryBuilder $qb
	 * @return QueryBuilder
	 * @throws Exception\InvalidArgument
	 */
	public function getOrderDQL(QueryBuilder $qb)
	{
		$orderRules = $this->orderRules;
		foreach ($orderRules as $orderRule) {
			$field = $orderRule[ArraySelectOrder::FIELD_POS];
			switch ($field) {
				case SelectOrderAbstraction::LEFT_FIELD:
				case SelectOrderAbstraction::RIGHT_FIELD:
				case SelectOrderAbstraction::LEVEL_FIELD:
					break;
				default:
					throw new Exception\InvalidArgument("Field $field is not recognized");
			}

			$field = "e.$field";

			$direction = $orderRule[ArraySelectOrder::DIRECTION_POS];
			switch ($direction) {
				case SelectOrderAbstraction::DIRECTION_ASCENDING:
					$directionSQL = 'ASC';
					break;
				case SelectOrderAbstraction::DIRECTION_DESCENDING:
					$directionSQL = 'DESC';
					break;
				default:
					throw new Exception\InvalidArgument("Direction $direction is not recognized");
			}
			$qb->addOrderBy($field, $directionSQL);
		}
		
		return $qb;
	}
}