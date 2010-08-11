<?php

namespace Supra\NestedSet;

use Closure;

/**
 * 
 */
interface RepositoryInterface
{
	public function search(SearchCondition\SearchConditionInterface $filter, Closure $order = null);
}