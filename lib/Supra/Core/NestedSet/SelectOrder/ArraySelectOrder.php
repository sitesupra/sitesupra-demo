<?php

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