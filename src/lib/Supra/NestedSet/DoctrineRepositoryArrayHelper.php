<?php

namespace Supra\NestedSet;

/**
 * 
 */
class DoctrineRepositoryArrayHelper extends ArrayRepository
{
	public function register(Node\DoctrineNode $node)
	{
		if ( ! \in_array($node, $this->array, true)) {
			$this->array[] = $node;
		}
	}

	public function free(Node\DoctrineNode $node)
	{
		if (\in_array($node, $this->array, true)) {
			$key = \array_search($node,	$this->array, true);
			unset($this->array[$key]);
		}
	}
}