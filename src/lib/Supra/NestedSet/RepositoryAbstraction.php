<?php

namespace Supra\NestedSet;

use Supra\NestedSet\Node\NodeInterface;

/**
 * Nested set repository abstraction
 */
abstract class RepositoryAbstraction implements RepositoryInterface
{
	/**
	 * Get maximal right interval value among all nodes
	 * @return int
	 */
	abstract protected function getMax();

	/**
	 * Created to implement RepositoryInterface
	 * @return RepositoryAbstraction
	 */
	public function getNestedSetRepository()
	{
		return $this;
	}

	/**
	 * Adds the new node to the repository
	 * @param NodeInterface $node
	 */
	public function add(NodeInterface $node)
	{
		$max = $this->getMax();
		$node->setLeftValue($max + 1);
		$node->setRightValue($max + 2);
		$node->setLevel(0);
	}

	/**
	 * Get root nodes' array
	 * @return array
	 */
	public function getRootNodes()
	{
		$searchCondition = $this->createSearchCondition();
		$searchCondition->levelEqualsTo(0);

		$rootNodes = $this->search($searchCondition);
		return $rootNodes;
	}

	/**
	 * Output the dump of the whole node tree
	 * @return string
	 */
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