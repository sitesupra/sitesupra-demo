<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\RepositoryInterface,
		Supra\NestedSet\Exception,
		Supra\NestedSet\SearchCondition\ArraySearchCondition;

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

}