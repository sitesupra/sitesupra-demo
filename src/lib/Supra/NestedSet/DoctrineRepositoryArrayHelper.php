<?php

namespace Supra\NestedSet;

/**
 * Array nested set repository to keep and update Doctrine nodes locally
 */
class DoctrineRepositoryArrayHelper extends ArrayRepository
{
	/**
	 * Get the maximal value of interval index among the nodes
	 */
	public function getCurrentMax()
	{
		$max = 0;
		/* @var $node Node\NodeInterface */
		foreach ($this->array as $node) {
			$max = max($max, $node->getRightValue());
		}
		return $max;
	}

	/**
	 * Register the loaded node
	 * @param Node\NodeInterface $node
	 */
	public function register(Node\NodeInterface $node)
	{
		if ( ! \in_array($node, $this->array, true)) {
			$this->array[] = $node;
		}
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		if (is_null($node)) {
			$this->array = array();
		} elseif (\in_array($node, $this->array, true)) {
			$key = \array_search($node,	$this->array, true);
			unset($this->array[$key]);
		}
	}

	/**
	 * Prepare the repository for removal
	 */
	public function destroy()
	{
		$this->free();
	}
}