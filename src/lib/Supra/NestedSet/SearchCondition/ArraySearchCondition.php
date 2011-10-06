<?php

namespace Supra\NestedSet\SearchCondition;

use Supra\NestedSet\Node\NodeInterface;
use Supra\NestedSet\Exception;

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
				$field = $condition[self::FIELD_POS];
				switch ($field) {
					case self::LEFT_FIELD:
						$testValue = $node->getLeftValue();
						break;
					case self::RIGHT_FIELD:
						$testValue = $node->getRightValue();
						break;
					case self::LEVEL_FIELD:
						$testValue = $node->getLevel();
						break;
					default:
						throw new Exception\InvalidArgument("Field $field is not recognized");
				}
				$relation = $condition[self::RELATION_POS];
				$value = $condition[self::VALUE_POS];
				switch ($relation) {
					case self::RELATION_EQUALS:
						$result = ($testValue == $value);
						break;
					case self::RELATION_LESS_OR_EQUALS:
						$result = ($testValue <= $value);
						break;
					case self::RELATION_MORE_OR_EQUALS:
						$result = ($testValue >= $value);
						break;
					case self::RELATION_LESS:
						$result = ($testValue < $value);
						break;
					case self::RELATION_MORE:
						$result = ($testValue > $value);
						break;
					case self::RELATION_NOT_EQUALS:
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