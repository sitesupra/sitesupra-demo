<?php

namespace Supra\Core\NestedSet\SearchCondition;

use Supra\Core\NestedSet\Node\NodeInterface;
use Supra\Core\NestedSet\Exception;

/**
 * Search condition class for arrays
 */
class ArraySearchCondition extends SearchConditionAbstraction
{
	/**
	 * Create closure of the search
	 * @return \Closure
	 * @throws Exception\InvalidArgument on invalid parameters
	 */
	public function getSearchClosure()
	{
		$conditions = $this->conditions;
		
		$filter = function (NodeInterface $node) use (&$conditions) {
			
			foreach ($conditions as $condition) {
				$field = $condition[SearchConditionInterface::FIELD_POS];
				$testValue = null;
				
				switch ($field) {
					case SearchConditionInterface::LEFT_FIELD:
						$testValue = $node->getLeftValue();
						break;
					case SearchConditionInterface::RIGHT_FIELD:
						$testValue = $node->getRightValue();
						break;
					case SearchConditionInterface::LEVEL_FIELD:
						$testValue = $node->getLevel();
						break;
					default:
						throw new Exception\InvalidArgument("Field $field is not recognized");
				}
				
				$relation = $condition[SearchConditionInterface::RELATION_POS];
				$value = $condition[SearchConditionInterface::VALUE_POS];
				$result = null;
				
				switch ($relation) {
					case SearchConditionInterface::RELATION_EQUALS:
						$result = ($testValue == $value);
						break;
					case SearchConditionInterface::RELATION_LESS_OR_EQUALS:
						$result = ($testValue <= $value);
						break;
					case SearchConditionInterface::RELATION_GREATER_OR_EQUALS:
						$result = ($testValue >= $value);
						break;
					case SearchConditionInterface::RELATION_LESS:
						$result = ($testValue < $value);
						break;
					case SearchConditionInterface::RELATION_GREATER:
						$result = ($testValue > $value);
						break;
					case SearchConditionInterface::RELATION_NOT_EQUALS:
						$result = ($testValue != $value);
						break;
					default:
						throw new Exception\InvalidArgument("Relation $relation is not recognized");
				}
				
				if ( ! $result) {
					return false;
				}
			}
			return true;
		};

		return $filter;
	}
}