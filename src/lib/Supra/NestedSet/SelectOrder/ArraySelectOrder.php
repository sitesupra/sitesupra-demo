<?php

namespace Supra\NestedSet\SelectOrder;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Exception;

/**
 * 
 */
class ArraySelectOrder extends SelectOrderAbstraction
{
	public function getOrderClosure()
	{
		$orderRules = $this->orderRules;
		$orderClosure = function (NodeInterface $nodeA, NodeInterface $nodeB) use (&$orderRules) {
			foreach ($orderRules as $orderRule) {
				$field = $orderRule[ArraySelectOrder::FIELD_POS];
				switch ($field) {
					case SelectOrderAbstraction::LEFT_FIELD:
						$valueA = $nodeA->getLeftValue();
						$valueB = $nodeB->getLeftValue();
						break;
					case SelectOrderAbstraction::RIGHT_FIELD:
						$valueA = $nodeA->getRightValue();
						$valueB = $nodeB->getRightValue();
						break;
					case SelectOrderAbstraction::LEVEL_FIELD:
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

				$direction = $orderRule[ArraySelectOrder::DIRECTION_POS];
				switch ($direction) {
					case SelectOrderAbstraction::DIRECTION_ASCENDING:
						break;
					case SelectOrderAbstraction::DIRECTION_DESCENDING:
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