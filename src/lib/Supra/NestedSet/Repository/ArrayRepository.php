<?php

namespace Supra\NestedSet\Repository;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Node\ArrayNode,
		Supra\NestedSet\Node\Node,
		Closure;

/**
 * 
 */
class ArrayRepository extends RepositoryAbstraction
{
	protected $array = array();

	protected $max = 0;

	public function getMax()
	{
		return $this->max;
	}

	public function createNode($title = null)
	{
		$node = new ArrayNode();
		$node->setTitle($title);
		$this->add($node);
		return $node;
	}

	public function extend($offset, $size)
	{

		/* @var $node NodeInterface */
		foreach ($this->array as $node) {
			if ($node->getStart() >= $offset) {
				$node->moveStart($size);
			}
			if ($node->getEnd() >= $offset) {
				$node->moveEnd($size);
			}
		}
	}

	public function truncate($offset, $size)
	{
		/* @var $node NodeInterface */
		foreach ($this->array as $node) {
			if ($node->getStart() >= $offset) {
				$node->moveStart(- $size);
			}
			if ($node->getEnd() >= $offset) {
				$node->moveEnd(- $size);
			}
		}
	}

	public function add(NodeInterface $node)
	{
		if ( ! \in_array($node, $this->array)) {

			$node->setRepository($this);

			$max = $this->getMax();
			$node->setStart($max);
			$node->setEnd($max + 1);
			$node->setDepth(0);
			$this->array[] = $node;
			
			$this->max += 2;
		}
	}

	public function move(NodeInterface $node, $pos, $depthDiff = 0)
	{
		$diff = $pos - $node->getStart();
		$start = $node->getStart();
		$end = $node->getEnd();
		if ($diff == 0 && $depthDiff == 0) {
			return;
		}
		foreach ($this->array as $item) {
			if ($item->getStart() >= $start && $item->getEnd() <= $end) {
				$item->moveStart($diff);
				$item->moveEnd($diff);
				$item->moveDepth($depthDiff);
			}
		}
	}

	public function drawTree()
	{
		$output = \Supra\NestedSet\Node\Node::output($this->array);
		return $output;
	}

	public function delete(NodeInterface $node)
	{
		$spaceUsed = $node->getEnd() - $node->getStart() + 1;
		$start = $node->getStart();
		$end = $node->getEnd();
		foreach ($this->array as $key => $item) {
			if ($item->getStart() >= $start && $item->getEnd() <= $end) {
				unset($this->array[$key]);
			}
		}

		$this->truncate($node->getStart(), $spaceUsed);
	}

	public function search(Closure $filter, Closure $order = null)
	{
		$result = array();
		foreach ($this->array as $item) {
			if ($filter($item)) {
				$result[] = $item;
			}
		}
		if ( ! \is_null($order)) {
			usort($result, $order);
		}
		return $result;
	}

	/**
	 * @param string $title
	 * @return ArrayNode
	 */
	public function byTitle($title)
	{
		$filter = function(NodeInterface $node) use ($title) {
			if ($node->getTitle() == $title) {
				return true;
			}
			return false;
		};
		$result = $this->search($filter);
		if (isset($result[0])) {
			return $result[0];
		} else {
			return null;
		}
	}

	/**
	 * @return array
	 */
	public function getRootNodes()
	{
		$filter = function(NodeInterface $node) {
			if ($node->getDepth() == 0) {
				return true;
			}
			return false;
		};

		$rootNodes = $this->search($filter);
		return $rootNodes;
	}
}