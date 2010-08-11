<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\RepositoryInterface,
		Supra\NestedSet\Exception,
		Supra\NestedSet\SearchCondition\ArraySearchCondition,
		Supra\NestedSet\SearchOrder\ArraySearchOrder;

/**
 * 
 */
class ArrayNode extends NodeAbstraction
{
	public function createSearchCondition()
	{
		$searchCondition = new ArraySearchCondition();
		return $searchCondition;
	}

	public function createSearchOrderRule()
	{
		$searchOrder = new ArraySearchOrder();
		return $searchOrder;
	}

}