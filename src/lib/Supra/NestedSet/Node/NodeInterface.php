<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\SearchCondition\SearchConditionAbstraction,
		Supra\NestedSet\SearchOrder\SearchOrderAbstraction;

/**
 * 
 */
interface NodeInterface
{
	public function getLeftValue();

	public function getRightValue();

	public function getLevel();

	public function setLeftValue($lft);

	public function setRightValue($rgt);

	public function setLevel($lvl);

	public function moveLeftValue($diff);

	public function moveRightValue($diff);

	public function moveLevel($diff);

	/**
	 * @return SearchConditionAbstraction
	 */
	public function createSearchCondition();

	/**
	 * @return SearchOrderAbstraction
	 */
	public function createSearchOrderRule();
}