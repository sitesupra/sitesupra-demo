<?php

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