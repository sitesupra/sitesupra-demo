<?php

namespace Supra\NestedSet\Node;

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
}