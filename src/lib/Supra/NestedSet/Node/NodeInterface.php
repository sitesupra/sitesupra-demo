<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\SearchCondition\SearchConditionAbstraction,
		Supra\NestedSet\SelectOrder\SelectOrderAbstraction;

/**
 * 
 */
interface NodeInterface
{
	public function getLeftValue();

	public function getRightValue();

	public function getLevel();
	
	public function setLeftValue($left);

	public function setRightValue($right);

	public function setLevel($level);
}