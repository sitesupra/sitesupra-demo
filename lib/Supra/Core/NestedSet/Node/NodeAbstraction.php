<?php

namespace Supra\Core\NestedSet\Node;

use Supra\Core\NestedSet\RepositoryAbstraction;
use Supra\Core\NestedSet\Exception;

/**
 * Abstract class for nested set node objects
 */
abstract class NodeAbstraction implements NodeInterface
{
	const DUMP_PREFIX = '  ';
	const DUMP_FORMAT = '%1$s(%2$d; %3$d) %4$d %5$s';
	const DUMP_PREFIX_POS = 1;
	const DUMP_LEFT_POS = 2;
	const DUMP_RIGHT_POS = 3;
	const DUMP_LEVEL_POS = 4;
	const DUMP_TITLE_POS = 5;

	/**
	 * @var int
	 */
	protected $left;

	/**
	 * @var int
	 */
	protected $right;

	/**
	 * @var int
	 */
	protected $level;
	
	/**
	 * @var NodeInterface
	 */
	protected $masterNode;
	
	/**
	 * True if cannot have children nodes
	 * @var boolean
	 */
	protected $leafInterface = false;

	/**
	 * @var RepositoryAbstraction
	 */
	protected $repository;

	/**
	 * @var string
	 */
	protected $title;
	
	/**
	 * Makes sure the delete trigger isn't called twice
	 * @var boolean
	 */
	private $deleted = false;

	/**
	 * Pass the original entity the nested set node belongs to
	 * @param NodeInterface $node
	 */
	public function belongsTo(NodeInterface $node)
	{
		$this->masterNode = $node;
		$this->refresh();
		
		if ($node instanceof NodeLeafInterface) {
			$this->setLeafInterface(true);
		}
	}

	/**
	 * Refresh indeces
	 */
	public function refresh()
	{
		$node = $this->masterNode;
		$this->left = $node->getLeftValue();
		$this->right = $node->getRightValue();
		$this->level = $node->getLevel();
		$this->title = $node->__toString();
	}
	
	/**
	 * @param string $title
	 * @return NodeAbstraction
	 */
	public function setNodeTitle($title)
	{
		$this->title = $title;
		
		return $this;
	}

	/**
	 * @return string
	 */
	public function getNodeTitle()
	{
		return $this->title;
	}

	/**
	 * Sets repository
	 * @param RepositoryAbstraction $repository
	 * @return NodeAbstraction
	 */
	public function setRepository(RepositoryAbstraction $repository)
	{
		$this->repository = $repository;
		
		return $this;
	}

	/**
	 * @return RepositoryAbstraction
	 */
	public function getRepository()
	{
		return $this->repository;
	}

	/**
	 * Get interval left value (starting from 1)
	 * @return int
	 */
	public function getLeftValue()
	{
		return $this->left;
	}

	/**
	 * Get interval right value (more than 1)
	 * @return int
	 */
	public function getRightValue()
	{
		return $this->right;
	}

	/**
	 * Get node depth level
	 * @return int
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * Set interval left value
	 * @param int $left
	 * @return NodeAbstraction
	 */
	public function setLeftValue($left)
	{
		$this->left = $left;
		
		return $this;
	}

	/**
	 * Set interval right value
	 * @param int $right
	 * @return NodeAbstraction
	 */
	public function setRightValue($right)
	{
		$this->right = $right;
		
		return $this;
	}

	/**
	 * Set node depth level
	 * @param int $level
	 * @return NodeAbstraction
	 */
	public function setLevel($level)
	{
		$this->level = $level;
		return $this;
	}
	
	/**
	 * Get if this node can have parents
	 * @return boolean
	 */
	public function isLeafInterface()
	{
		return $this->leafInterface;
	}
	
	/**
	 * Set if this node should be leaf, no parents allowed
	 * @param boolean $leafInterface
	 */
	public function setLeafInterface($leafInterface)
	{
		$this->leafInterface = $leafInterface;
	}

	/**
	 * Increase left value
	 * @param int $diff
	 * @return NodeAbstraction
	 */
	public function moveLeftValue($diff)
	{
		$this->setLeftValue($this->getLeftValue() + $diff);
		return $this;
	}

	/**
	 * Increase right value
	 * @param int $diff
	 * @return NodeAbstraction
	 */
	public function moveRightValue($diff)
	{
		$this->setRightValue($this->getRightValue() + $diff);
		return $this;
	}

	/**
	 * Inclrease level value
	 * @param int $diff
	 * @return NodeAbstraction
	 */
	public function moveLevel($diff)
	{
		$this->setLevel($this->getLevel() + $diff);
		return $this;
	}

	/**
	 * Get interval size
	 * @return int
	 */
	public function getIntervalSize()
	{
		return $this->getRightValue() - $this->getLeftValue();
	}

	/**
	 * Add child node for the current node
	 * @param NodeInterface $child
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function addChild(NodeInterface $child)
	{
		$child->moveAsLastChildOf($this);
		
		return $this;
	}

	/**
	 * Deletes the current node from the repository
	 * @nestedSetMethod
	 */
	public function delete()
	{
		if ($this->deleted) {
			return;
		}
		$this->deleted = true;

		$left = $this->getLeftValue();
		$spaceUsed = $this->getIntervalSize() + 1;
		$this->repository->delete($this);

		// Free the unused space
		$this->repository->truncate($left, $spaceUsed);
	}

	/**
	 * Does the node have next sibling
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function hasNextSibling()
	{
		$nextSibling = $this->getNextSibling();
		$hasNext = ( ! is_null($nextSibling));
		
		return $hasNext;
	}

	/**
	 * Does the node have previous sibling
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function hasPrevSibling()
	{
		$prevSibling = $this->getPrevSibling();
		$hasPrev = ( ! is_null($prevSibling));
		
		return $hasPrev;
	}

	/**
	 * Get count of children (direct descendants)
	 * @return int
	 * @nestedSetMethod
	 */
	public function getNumberChildren()
	{
		$children = $this->getChildren();
		$count = count($children);
		
		return $count;
	}

	/**
	 * Get number of descendants
	 * @return int
	 * @nestedSetMethod
	 */
	public function getNumberDescendants()
	{
		$intervalSize = $this->getIntervalSize();
		$intervalSize = $intervalSize - 1;
		
		if ($intervalSize % 2 != 0) {
			$dump = static::dump($this);
			throw new Exception\InvalidStructure("The size of node {$dump} must be odd number, even number received");
		}
		$descendantCount = (int) ($intervalSize / 2);
		
		return $descendantCount;
	}

	/**
	 * Does the node has parent node
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function hasParent()
	{
		$hasParent = ( ! $this->isRoot());
		
		return $hasParent;
	}

	/**
	 * Get node's parent node
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function getParent()
	{
		if ( ! $this->hasParent()) {
			return null;
		}
		$parents = $this->getAncestors(1);
		if ( ! isset($parents[0])) {
			$dump = static::dump($this);
			throw new Exception\InvalidStructure("Parent node was not found for {$dump} but must exist");
		}
		$parent = $parents[0];
		
		return $parent;
	}

	/**
	 * Get path of ancestor nodes to the current node. Uses __toString() method.
	 * @param string $separator
	 * @param boolean $includeNode
	 * @return string
	 * @nestedSetMethod
	 */
	public function getPath($separator = ' > ', $includeNode = true)
	{
		$pathNodes = $this->getAncestors(0, $includeNode);
		$items = array();
		foreach ($pathNodes as $node) {
			array_unshift($items, $node->__toString());
		}
		$path = implode($separator, $items);
		
		return $path;
	}

	/**
	 * Get array of ancestor nodes starting the deepest element
	 * @param int $levelLimit
	 * @param boolean $includeNode
	 * @return array
	 * @throws Exception\Domain
	 * @nestedSetMethod
	 */
	public function getAncestors($levelLimit = 0, $includeNode = false)
	{
		$left = $this->getLeftValue();
		$right = $this->getRightValue();
		$level = $this->getLevel();

		$searchCondition = $this->repository->createSearchCondition();
		
		// Will include the self node if required in the end
		$searchCondition->leftLessThan($left)
				->rightGreaterThan($right);

		if ($levelLimit < 0) {
			throw new Exception\Domain("Level limit cannot be negative in getAncestors method");
		} elseif ($levelLimit > 0) {
			$searchCondition->levelGreaterThanOrEqualsTo($level - $levelLimit);
		}

		$orderRule = $this->repository->createSelectOrderRule()
				->byLevelDescending();

		$ancestors = $this->repository->search($searchCondition, $orderRule);
		
		// Fixes not flushed node returning
		if ($includeNode) {
			if (isset($this->masterNode)) {
				array_unshift($ancestors, $this->masterNode);
			} else {
				array_unshift($ancestors, $this);
			}
		}
		
		return $ancestors;
	}

	/**
	 * Get array of descendant nodes
	 * @param int $levelLimit
	 * @param boolean $includeNode
	 * @return array
	 * @throws Exception\Domain
	 * @nestedSetMethod
	 */
	public function getDescendants($levelLimit = 0, $includeNode = false)
	{
		$left = $this->getLeftValue();
		$right = $this->getRightValue();
		$level = $this->getLevel();

		$searchCondition = $this->repository->createSearchCondition();
		if ($includeNode) {
			$searchCondition->leftGreaterThanOrEqualsTo($left)
					->rightLessThanOrEqualsTo($right);
		} else {
			$searchCondition->leftGreaterThan($left)
					->rightLessThan($right);
		}

		if ($levelLimit < 0) {
			throw new Exception\Domain("Level limit cannot be negative in getDescendants method");
		} elseif ($levelLimit > 0) {
			$searchCondition->levelLessThanOrEqualsTo($level + $levelLimit);
		}

		$orderRule = $this->repository->createSelectOrderRule()
				->byLeftAscending();

		$descendants = $this->repository->search($searchCondition, $orderRule);
		
		return $descendants;
	}

	/**
	 * Get the first node children. Null is returned if it hasn't any.
	 * @return NodeAbstraction
	 * @throws Exception\InvalidStructure if nested set structure is invalid
	 * @nestedSetMethod
	 */
	public function getFirstChild()
	{
		if ( ! $this->hasChildren()) {
			return null;
		}
		$left = $this->getLeftValue() + 1;

		$searchCondition = $this->repository->createSearchCondition();
		$searchCondition->leftEqualsTo($left);

		$firstChild = $this->repository->search($searchCondition);
		if ( ! isset($firstChild[0])) {
			$dump = static::dump($this);
			throw new Exception\InvalidStructure("Could not find the first child of {$dump} but it must exist");
		}
		$firstChild = $firstChild[0];
		
		return $firstChild;
	}

	/**
	 * Get the last node children. Null is returned if it hasn't any.
	 * @return NodeAbstraction
	 * @throws Exception\InvalidStructure if nested set structure is invalid
	 * @nestedSetMethod
	 */
	public function getLastChild()
	{
		if ( ! $this->hasChildren()) {
			return null;
		}
		$right = $this->getRightValue() - 1;

		$searchCondition = $this->repository->createSearchCondition()
				->rightEqualsTo($right);

		$lastChild = $this->repository->search($searchCondition);
		if ( ! isset($lastChild[0])) {
			$dump = static::dump($this);
			throw new Exception\InvalidStructure("Could not find the last child of {$dump} but it must exist");
		}
		$lastChild = $lastChild[0];
		
		return $lastChild;
	}

	/**
	 * Get next sibling
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function getNextSibling()
	{
		$left = $this->getRightValue() + 1;
		$searchCondition = $this->repository->createSearchCondition()
				->leftEqualsTo($left);

		$nextSibling = $this->repository->search($searchCondition);
		if ( ! isset($nextSibling[0])) {
			return null;
		}
		$nextSibling = $nextSibling[0];
		
		return $nextSibling;
	}

	/**
	 * Get previous sibling
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function getPrevSibling()
	{
		$right = $this->getLeftValue() - 1;
		if ($right <= 0) {
			return null;
		}
		$searchCondition = $this->repository->createSearchCondition()
				->rightEqualsTo($right);

		$prevSibling = $this->repository->search($searchCondition);
		if ( ! isset($prevSibling[0])) {
			return null;
		}
		$prevSibling = $prevSibling[0];
		
		return $prevSibling;
	}

	/**
	 * Get array of direct descendants
	 * @return array
	 * @nestedSetMethod
	 */
	public function getChildren()
	{
		return $this->getDescendants(1, false);
	}

	/**
	 * Get siblings of the current node
	 * @param boolean $includeNode
	 * @return array
	 * @nestedSetMethod
	 */
	public function getSiblings($includeNode = true)
	{
		$parent = $this->getParent();
		$siblings = null;
		
		if ( ! is_null($parent)) {
			$siblings = $parent->getChildren();
		} else {
			$siblings = $this->repository->getRootNodes();
		}
		
		if ( ! $includeNode) {
			foreach ($siblings as $key => $sibling) {
				if ($sibling->isEqualTo($this)) {
					unset($siblings[$key]);
					break;
				}
			}
			// reset index
			$siblings = array_values($siblings);
		}
		
		return $siblings;
	}

	/**
	 * Does the node has children
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function hasChildren()
	{
		$hasChildren = ($this->getIntervalSize() > 1);
		
		return $hasChildren;
	}

	/**
	 * Move the current node as next sibling of the node passed
	 * @param NodeInterface $afterNode
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function moveAsNextSiblingOf(NodeInterface $afterNode)
	{
		$pos = $afterNode->getRightValue() + 1;
		$level = $afterNode->getLevel();
		$this->move($pos, $level);

		return $this;
	}

	/**
	 * Move the current node as previous sibling of the node passed
	 * @param NodeInterface $beforeNode
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function moveAsPrevSiblingOf(NodeInterface $beforeNode)
	{
		$pos = $beforeNode->getLeftValue();
		$level = $beforeNode->getLevel();
		$this->move($pos, $level);
		
		return $this;
	}
	
	/**
	 * Check if children can be added to the node
	 * @param NodeInterface $parentNode
	 * @throws Exception\InvalidOperation
	 */
	private function validateAddingChildren(NodeInterface $parentNode)
	{
		$allow = true;
		
		/*
		 * FIXME: These checks are not good, but we can receive NodeAbstraction
		 * or other NodeInterface with magic __call as well..
		 */
		if ($parentNode instanceof NodeAbstraction) {
			if ($parentNode->leafInterface) {
				$allow = false;
			}
		}
		
		if ($parentNode instanceof NodeLeafInterface) {
			$allow = false;
		}
		
		if ( ! $allow) {
			$parentDump = static::dump($parentNode);
			throw new Exception\InvalidOperation("Children cannot added to the NodeLeafInterface object {$parentDump}");
		}
	}

	/**
	 * Move the current node as the first child of the node passed
	 * @param NodeInterface $parentNode
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function moveAsFirstChildOf(NodeInterface $parentNode)
	{
		$this->validateAddingChildren($parentNode);
		
		$pos = $parentNode->getLeftValue() + 1;
		$level = $parentNode->getLevel() + 1;
		$this->move($pos, $level);

		return $this;
	}

	/**
	 * Move the current node as last child of the node passed
	 * @param NodeInterface $parentNode
	 * @return NodeAbstraction
	 * @nestedSetMethod
	 */
	public function moveAsLastChildOf(NodeInterface $parentNode)
	{
		$this->validateAddingChildren($parentNode);

		$pos = $parentNode->getRightValue();
		$level = $parentNode->getLevel() + 1;
		$this->move($pos, $level);

		return $this;
	}

	/**
	 * Wheather the node is a leaf node
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function isLeaf()
	{
		$isLeaf = ( ! $this->hasChildren());
		
		return $isLeaf;
	}

	/**
	 * Wheather the node is a root node
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function isRoot()
	{
		$isRoot = ($this->getLevel() == 0);
		
		return $isRoot;
	}

	/**
	 * If the current node is ancestor of the given node
	 * @param NodeInterface $node
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function isAncestorOf(NodeInterface $node)
	{
		if ($this->getLeftValue() < $node->getLeftValue()
				&& $this->getRightValue() > $node->getRightValue()) {
			
			return true;
		}
		
		return false;
	}

	/**
	 * If the current node is descendant of the given node
	 * @param NodeInterface $node
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function isDescendantOf(NodeInterface $node)
	{
		$isAncestor = $node->isAncestorOf($this);
		
		return $isAncestor;
	}

	/**
	 * If the nodes are equal. Works for nodes within one repository only.
	 * @param NodeInterface $node
	 * @return boolean
	 * @nestedSetMethod
	 */
	public function isEqualTo(NodeInterface $node)
	{
		$isEqual = ($this->getLeftValue() == $node->getLeftValue());
		
		return $isEqual;
	}

	/**
	 * Inner move function for node
	 * @param int $pos
	 * @param int $level
	 * @return NodeAbstraction
	 */
	protected function move($pos, $level)
	{
		$validMove = $this->validateMove($pos);
		
		// Skip invalid move
		if ( ! $validMove) {
			return $this;
		}

		// Functionality with better performance
		$levelDiff = $level - $this->getLevel();
		$this->repository->move($this, $pos, $levelDiff);

//		$spaceNeeded = $this->getIntervalSize() + 1;
//		
//		// I) reserve the space
//		$this->repository->extend($pos, $spaceNeeded);
//
//		$oldPos = $this->getLeftValue();
//		$levelDiff = $level - $this->getLevel();
//
//		// II) move the node to the place
//		$this->repository->oldMove($this, $pos, $levelDiff);
//
//		// III) trim the unused space
//		$this->repository->truncate($oldPos, $spaceNeeded);

		return $this;
	}

	/**
	 * Check if the move is valid, block node movement under it's descendants
	 * @param int $pos
	 * @return boolean, false on invalid move
	 */
	protected function validateMove($pos)
	{
		if ($this->getLeftValue() <= $pos && $this->getRightValue() >= $pos) {
			
			return false;
//			$dump = static::dump($this);
//			throw new Exception\InvalidOperation("The move not allowed for {$dump} to position $pos");
		}
		
		return true;
	}

	/**
	 * Debug output of node collection
	 * @param array $nodes
	 * @return string
	 */
	public static function output(array $nodes)
	{
		$tree = '';
		$prevNode = null;
		$array = array();
		foreach ($nodes as $item) {
			
			$leftValue = $item->getLeftValue();
			
			if (isset($array[$leftValue])) {
				throw new Exception\InvalidStructure("Two nodes with equal left value '$leftValue' are found");
			}
			
			$array[$leftValue] = $item;
		}
		ksort($array);
		
		/* @var $item NodeInterface */
		foreach ($array as $item) {

			// Mark skipped tree parts with dots
			if ( ! \is_null($prevNode)) {
				$levelDiff = $item->getLevel() - $prevNode->getLevel();
				$nextLeftVal = $prevNode->getLeftValue() + 2 - $levelDiff;
				if ($item->getLeftValue() != $nextLeftVal) {
					$tree .= "...\n";
				}
			} else {
				if ($item->getLeftValue() != 1) {
					$tree .= "...\n";
				}
			}

			// Output the node
			$tree .= static::dump($item);
			$tree .= "\n";
			
			$prevNode = $item;
		}
		
		return $tree;
	}

	/**
	 * Convert node to string for debug, title is used by default
	 * @return string
	 */
	public function __toString()
	{
		return $this->getNodeTitle();
	}

	/**
	 * Dump the node data as string
	 * @param NodeInterface $node
	 * @return string
	 */
	public static function dump(NodeInterface $node)
	{
		$prefix = str_repeat(static::DUMP_PREFIX, $node->getLevel());
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		$level = $node->getLevel();
		$title = $node->getNodeTitle();
		$dumpData = array(
			static::DUMP_PREFIX_POS => $prefix,
			static::DUMP_LEFT_POS => $left,
			static::DUMP_RIGHT_POS => $right,
			static::DUMP_LEVEL_POS => $level,
			static::DUMP_TITLE_POS => $title
		);
		ksort($dumpData);
		$dumpString = vsprintf(static::DUMP_FORMAT, $dumpData);
		
		return $dumpString;
	}

	/**
	 * Not static version of dump($node) method
	 * @return string
	 */
	public function dumpThis()
	{
		// Use late static binding
		$dumpString = static::dump($this);
		
		return $dumpString;
	}

	/**
	 * @return NodeInterface
	 */
	public function getMasterNode()
	{
		return $this->masterNode;
	}
}