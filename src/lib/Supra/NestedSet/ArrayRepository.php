<?php

namespace Supra\NestedSet;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Node\ArrayNode,
		Supra\NestedSet\Node\NodeAbstraction,
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
			if ($node->getLeftValue() >= $offset) {
				$node->moveLeftValue($size);
			}
			if ($node->getRightValue() >= $offset) {
				$node->moveRightValue($size);
			}
		}
	}

	public function truncate($offset, $size)
	{
		/* @var $node NodeInterface */
		foreach ($this->array as $node) {
			if ($node->getLeftValue() >= $offset) {
				$node->moveLeftValue(- $size);
			}
			if ($node->getRightValue() >= $offset) {
				$node->moveRightValue(- $size);
			}
		}
	}

	public function add(NodeInterface $node)
	{
		if ( ! \in_array($node, $this->array)) {

			$node->setRepository($this);

			$max = $this->getMax();
			$node->setLeftValue($max);
			$node->setRightValue($max + 1);
			$node->setLevel(0);
			$this->array[] = $node;
			
			$this->max += 2;
		}
	}

	public function move(NodeInterface $node, $pos, $levelDiff = 0)
	{
		$diff = $pos - $node->getLeftValue();
		$lft = $node->getLeftValue();
		$rgt = $node->getRightValue();
		if ($diff == 0 && $levelDiff == 0) {
			return;
		}
		foreach ($this->array as $item) {
			if ($item->getLeftValue() >= $lft && $item->getRightValue() <= $rgt) {
				$item->moveLeftValue($diff);
				$item->moveRightValue($diff);
				$item->moveLevel($levelDiff);
			}
		}
	}

	public function drawTree()
	{
		$output = NodeAbstraction::output($this->array);
		return $output;
	}

	public function delete(NodeInterface $node)
	{
		$spaceUsed = $node->getRightValue() - $node->getLeftValue() + 1;
		$lft = $node->getLeftValue();
		$rgt = $node->getRightValue();
		foreach ($this->array as $key => $item) {
			if ($item->getLeftValue() >= $lft && $item->getRightValue() <= $rgt) {
				unset($this->array[$key]);
			}
		}

		$this->truncate($node->getLeftValue(), $spaceUsed);
	}

	public function search(SearchCondition\SearchConditionInterface $filter, SearchOrder\SearchOrderInterface $order = null)
	{
		if ( ! ($filter instanceof SearchCondition\ArraySearchCondition)) {
			throw new Exception\InvalidOperation("Only ArraySearchCondition instance can be passed to search method");
		}
		$filterClosure = $filter->getSearchClosure();
		
		$orderClosure = null;
		if ( ! \is_null($order)) {
			if ( ! ($order instanceof SearchOrder\ArraySearchOrder)) {
				throw new Exception\InvalidOperation("Only ArraySearchCondition instance can be passed to search method");
			}
			$orderClosure = $order->getOrderClosure();
		}
		
		$result = $this->searchByClosure($filterClosure, $orderClosure);
		return $result;
	}

	public function searchByClosure(Closure $filterClosure, Closure $orderClosure = null)
	{
		$result = array();
		foreach ($this->array as $item) {
			if ($filterClosure($item)) {
				$result[] = $item;
			}
		}
		if ( ! \is_null($orderClosure)) {
			usort($result, $orderClosure);
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
		$result = $this->searchByClosure($filter);
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
		$searchCondition = new SearchCondition\ArraySearchCondition();
		$searchCondition->levelEqualsTo(0);

		$rootNodes = $this->search($searchCondition);
		return $rootNodes;
	}
}