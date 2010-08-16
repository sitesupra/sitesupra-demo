<?php

namespace Supra\NestedSet;

/**
 * 
 */
class DoctrineRepositoryArrayHelper extends ArrayRepository
{
	public function register(Node\NodeInterface $node)
	{
		if ( ! \in_array($node, $this->array, true)) {
			$this->array[] = $node;
		}
	}

	public function free(Node\NodeInterface $node)
	{
		if (\in_array($node, $this->array, true)) {
			$key = \array_search($node,	$this->array, true);
			unset($this->array[$key]);
		}
	}

	public function destroy()
	{
		foreach ($this->array as $key => $node) {
			unset($this->array[$key]);
		}
	}
}