<?php

namespace Supra\NestedSet\Node;

/**
 * 
 */
interface NodeInterface
{
	public function getStart();

	public function getEnd();

	public function getDepth();

	public function setStart($start);

	public function setEnd($end);

	public function setDepth($depth);

	public function moveStart($diff);

	public function moveEnd($diff);

	public function moveDepth($diff);
}