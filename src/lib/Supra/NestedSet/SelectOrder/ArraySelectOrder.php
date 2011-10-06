<?php

namespace Supra\NestedSet\SelectOrder;

use Supra\NestedSet\Node\NodeInterface;
use Supra\NestedSet\Exception;

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
				$field = $orderRule[self::FIELD_POS];
				switch ($field) {
					case self::LEFT_FIELD:
						$valueA = $nodeA->getLeftValue();
						$valueB = $nodeB->getLeftValue();
						break;
					case self::RIGHT_FIELD:
						$valueA = $nodeA->getRightValue();
						$valueB = $nodeB->getRightValue();
						break;
					case self::LEVEL_FIELD:
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

				$direction = $orderRule[self::DIRECTION_POS];
				switch ($direction) {
					case self::DIRECTION_ASCENDING:
						break;
					case self::DIRECTION_DESCENDING:
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