<?php

namespace Supra\NestedSet\SearchOrder;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Exception;

/**
 * 
 */
class ArraySearchOrder extends SearchOrderAbstraction
{
	public function getOrderClosure()
	{
		$orderRules = $this->orderRules;
		$orderClosure = function (NodeInterface $nodeA, NodeInterface $nodeB) use (&$orderRules) {
			foreach ($orderRules as $orderRule) {
				$field = $orderRule[ArraySearchOrder::FIELD_POS];
				switch ($field) {
					case SearchOrderAbstraction::LEFT_FIELD:
						$valueA = $nodeA->getLeftValue();
						$valueB = $nodeB->getLeftValue();
						break;
					case SearchOrderAbstraction::RIGHT_FIELD:
						$valueA = $nodeA->getRightValue();
						$valueB = $nodeB->getRightValue();
						break;
					case SearchOrderAbstraction::LEVEL_FIELD:
						$valueA = $nodeA->getLevel();
						$valueB = $nodeB->getLevel();
						break;
					default:
						throw new Exception\InvalidOperation("Field $field is not recognized");
				}

				$diff = $valueA - $valueB;
				if ($diff == 0) {
					continue;
				}

				$direction = $orderRule[ArraySearchOrder::DIRECTION_POS];
				switch ($direction) {
					case SearchOrderAbstraction::DIRECTION_ASCENDING:
						break;
					case SearchOrderAbstraction::DIRECTION_DESCENDING:
						$diff = ( - $diff);
						break;
					default:
						throw new Exception\InvalidOperation("Direction $direction is not recognized");
				}
				return $diff;
			}

			return null;
		};

		return $orderClosure;
	}
}