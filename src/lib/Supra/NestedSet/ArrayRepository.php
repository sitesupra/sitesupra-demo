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

	public function add(NodeInterface $node)
	{
		$node->setRepository($this);
		parent::add($node);
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

	public function betterMove(NodeInterface $node, $pos, $levelDiff)
	{
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		$spaceUsed = $right - $left + 1;
		if ($pos > $left) {
			$a = $right + 1;
			$b = $pos - 1;
			$moveA = $pos - $left - $spaceUsed;
			$moveB = - $spaceUsed;
		} else {
			$a = $left - 1;
			$b = $pos;
			$moveA = $pos - $left;
			$moveB = $spaceUsed;
		}
		foreach ($this->array as $item) {
			if (self::isBetween($item->getLeftValue(), $left, $right)) {
				$item->moveLeftValue($moveA);
				$item->moveRightValue($moveA);
				$item->moveLevel($levelDiff);
				continue;
			}

			if (self::isBetween($item->getLeftValue(), $a, $b)) {
				$item->moveLeftValue($moveB);
			}
			if (self::isBetween($item->getRightValue(), $a, $b)) {
				$item->moveRightValue($moveB);
			}
		}
	}

	public static function isBetween($a, $b, $c)
	{
		if ($b <= $c) {
			return $b <= $a && $a <= $c;
		} else {
			return $b >= $a && $a >= $c;
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
		$selectOrder = new SelectOrder\ArraySelectOrder();
		return $selectOrder;
	}
}