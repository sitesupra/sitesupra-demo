<?php

namespace Supra\NestedSet;

use Supra\NestedSet\Node\NodeInterface;

/**
 * 
 */
abstract class RepositoryAbstraction
{
	abstract protected function getMax();

	public function add(NodeInterface $node)
	{
		$node->setRepository($this);

		$max = $this->getMax();
		$node->setLeftValue($max + 1);
		$node->setRightValue($max + 2);
		$node->setLevel(0);
	}

	/**
	 * @return array
	 */
	public function getRootNodes()
	{
		$searchCondition = $this->createSearchCondition();
		$searchCondition->levelEqualsTo(0);

		$rootNodes = $this->search($searchCondition);
		return $rootNodes;
	}

	public function drawTree()
	{
		$searchCondition = $this->createSearchCondition();
		$orderRule = $this->createSelectOrderRule()
				->byLeftAscending();
		$nodes = $this->search($searchCondition, $orderRule);
		$output = Node\NodeAbstraction::output($nodes);
		return $output;
	}

	/**
	 * @param SearchCondition\SearchConditionInterface $filter
	 * @param SelectOrder\SelectOrderInterface $order
	 * @return array
	 */
	abstract public function search(SearchCondition\SearchConditionInterface $filter, SelectOrder\SelectOrderInterface $order = null);

	/**
	 * @return SearchConditionAbstraction
	 */
	abstract public function createSearchCondition();

	/**
	 * @return SelectOrderAbstraction
	 */
	abstract public function createSelectOrderRule();
}