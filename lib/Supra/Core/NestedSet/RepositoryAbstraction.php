<?php

namespace Supra\Core\NestedSet;

use Supra\Core\NestedSet\Node\NodeInterface;
use Supra\Core\NestedSet\SelectOrder\SelectOrderAbstraction;
use Supra\Core\NestedSet\SearchCondition\SearchConditionAbstraction;

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
	 * @TODO doesn't take into account nodes from the doctrine repo array helper
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
	 * @TODO doesn't take into account nodes from the doctrine repo array helper
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
	
	/**
	 * Move the node to the new position and change level by {$levelDiff}
	 * @param NodeInterface $node
	 * @param int $pos
	 * @param int $levelDiff
	 */
	abstract public function move(NodeInterface $node, $pos, $levelDiff);
	
	/**
	 * Remove unused space in the nested set intervals
	 * @param int $offset
	 * @param int $size
	 */
	abstract public function truncate($offset, $size);
	
	/**
	 * Deletes the nested set part under the node including the node
	 * @param Node\NodeInterface $node
	 */
	abstract public function delete(Node\NodeInterface $node);
}
