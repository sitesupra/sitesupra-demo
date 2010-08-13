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

	protected function getMax()
	{
		$max = $this->max;
		$this->max += 2;
		return $max;
	}

	public function createNode($title = null)
	{
		$node = new ArrayNode();
		$node->setTitle($title);
		$this->add($node);
		$this->array[] = $node;
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

	public function move(NodeInterface $node, $pos, $levelDiff = 0)
	{
		$diff = $pos - $node->getLeftValue();
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		if ($diff == 0 && $levelDiff == 0) {
			return;
		}
		foreach ($this->array as $item) {
			if ($item->getLeftValue() >= $left && $item->getRightValue() <= $right) {
				$item->moveLeftValue($diff);
				$item->moveRightValue($diff);
				$item->moveLevel($levelDiff);
			}
		}
	}

	public function delete(NodeInterface $node)
	{
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		foreach ($this->array as $key => $item) {
			if ($item->getLeftValue() >= $left && $item->getRightValue() <= $right) {
				unset($this->array[$key]);
			}
		}
	}

	public function search(SearchCondition\SearchConditionInterface $filter, SelectOrder\SelectOrderInterface $order = null)
	{
		if ( ! ($filter instanceof SearchCondition\ArraySearchCondition)) {
			throw new Exception\WrongInstance($filter, 'SearchCondition\ArraySearchCondition');
		}
		$filterClosure = $filter->getSearchClosure();

		$orderClosure = null;
		if ( ! \is_null($order)) {
			if ( ! ($order instanceof SelectOrder\ArraySelectOrder)) {
				throw new Exception\WrongInstance($order, 'SelectOrder\ArraySelectOrder');
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

	public function createSearchCondition()
	{
		$searchCondition = new SearchCondition\ArraySearchCondition();
		return $searchCondition;
	}

	public function createSelectOrderRule()
	{
		$SelectOrder = new SelectOrder\ArraySelectOrder();
		return $SelectOrder;
	}
}