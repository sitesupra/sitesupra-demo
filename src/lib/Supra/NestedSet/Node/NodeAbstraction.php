<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\RepositoryAbstraction,
		Supra\NestedSet\Exception;

/**
 * 
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
	 * @var RepositoryAbstraction
	 */
	protected $repository;

	/**
	 * @var string
	 */
	protected $title;

	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function setRepository(RepositoryAbstraction $repository)
	{
		$this->repository = $repository;
		return $this;
	}

	public function getRepository()
	{
		return $this->repository;
	}

	public function getLeftValue()
	{
		return $this->left;
	}

	public function getRightValue()
	{
		return $this->right;
	}

	public function getLevel()
	{
		return $this->level;
	}

	public function setLeftValue($left)
	{
		$this->left = $left;
		return $this;
	}

	public function setRightValue($right)
	{
		$this->right = $right;
		return $this;
	}

	public function setLevel($level)
	{
		$this->level = $level;
		return $this;
	}

	public function moveLeftValue($diff)
	{
		$this->setLeftValue($this->getLeftValue() + $diff);
		return $this;
	}

	public function moveRightValue($diff)
	{
		$this->setRightValue($this->getRightValue() + $diff);
		return $this;
	}

	public function moveLevel($diff)
	{
		$this->setLevel($this->getLevel() + $diff);
		return $this;
	}

	public function getIntervalSize()
	{
		return $this->getRightValue() - $this->getLeftValue();
	}

	public function addChild(NodeInterface $child)
	{
		$child->moveAsLastChildOf($this);
	}

	public function delete()
	{
		$left = $this->getLeftValue();
		$spaceUsed = $this->getIntervalSize() + 1;
		$this->repository->delete($this);

		$this->repository->truncate($left, $spaceUsed);
	}

	public function hasNextSibling()
	{
		$nextSibling = $this->getNextSibling();
		return ( ! \is_null($nextSibling));
	}

	public function hasPrevSibling()
	{
		$prevSibling = $this->getPrevSibling();
		return ( ! \is_null($prevSibling));
	}

	/**
	 * @return int
	 */
	public function getNumberChildren()
	{
		$children = $this->getChildren();
		return count($children);
	}

	public function getNumberDescendants()
	{
		$intervalSize = $this->getIntervalSize();
		$intervalSize = $intervalSize - 1;
		if ($intervalSize % 2 != 0) {
			$dump = static::dump($this);
			throw new Exception\InvalidStructure("The size of node {$dump} must be odd number, even number received");
		}
		$descendantCount = $intervalSize / 2;
		return $descendantCount;
	}

	public function hasParent()
	{
		$hasParent = ( ! $this->isRoot());
		return $hasParent;
	}

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

	public function getAncestors($levelLimit = 0, $includeNode = false)
	{
		$left = $this->getLeftValue();
		$right = $this->getRightValue();
		$level = $this->getLevel();

		$searchCondition = $this->repository->createSearchCondition();
		if ($includeNode) {
			$searchCondition->leftLessThanOrEqualsTo($left)
					->rightMoreThanOrEqualsTo($right);
		} else {
			$searchCondition->leftLessThan($left)
					->rightMoreThan($right);
		}

		if ($levelLimit < 0) {
			throw new Exception\InvalidOperation("Level limit cannot be negative in getAncestors method");
		} elseif ($levelLimit > 0) {
			$searchCondition->levelMoreThanOrEqualsTo($level - $levelLimit);
		}

		$orderRule = $this->repository->createSelectOrderRule()
				->byLevelDescending();

		$ancestors = $this->repository->search($searchCondition, $orderRule);
		return $ancestors;
	}

	public function getDescendants($levelLimit = 0, $includeNode = false)
	{
		$left = $this->getLeftValue();
		$right = $this->getRightValue();
		$level = $this->getLevel();

		$searchCondition = $this->repository->createSearchCondition();
		if ($includeNode) {
			$searchCondition->leftMoreThanOrEqualsTo($left)
					->rightLessThanOrEqualsTo($right);
		} else {
			$searchCondition->leftMoreThan($left)
					->rightLessThan($right);
		}

		if ($levelLimit < 0) {
			throw new Exception\InvalidOperation("Level limit cannot be negative in getDescendants method");
		} elseif ($levelLimit > 0) {
			$searchCondition->levelLessThanOrEqualsTo($level + $levelLimit);
		}

		$orderRule = $this->repository->createSelectOrderRule()
				->byLeftAscending();

		$descendants = $this->repository->search($searchCondition, $orderRule);
		return $descendants;
	}

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

	public function getChildren()
	{
		return $this->getDescendants(1, false);
	}

	public function getSiblings($includeNode = true)
	{
		$parent = $this->getParent();
		if ( ! \is_null($parent)) {
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

	public function hasChildren()
	{
		$hasChildren = ($this->getIntervalSize() > 1);
		return $hasChildren;
	}

	public function moveAsNextSiblingOf(NodeInterface $afterNode)
	{
		$pos = $afterNode->getRightValue() + 1;
		$level = $afterNode->getLevel();
		$this->move($pos, $level);
		return $this;
	}

	public function moveAsPrevSiblingOf(NodeInterface $beforeNode)
	{
		$pos = $beforeNode->getLeftValue();
		$level = $beforeNode->getLevel();
		$this->move($pos, $level);
		return $this;
	}

	public function moveAsFirstChildOf(NodeInterface $parentNode)
	{
		$pos = $parentNode->getLeftValue() + 1;
		$level = $parentNode->getLevel() + 1;
		$this->move($pos, $level);
		return $this;
	}

	public function moveAsLastChildOf(NodeInterface $parentNode)
	{
		$pos = $parentNode->getRightValue();
		$level = $parentNode->getLevel() + 1;
		$this->move($pos, $level);
		return $this;
	}

	public function isLeaf()
	{
		$isLeaf = ( ! $this->hasChildren());
		return $isLeaf;
	}

	public function isRoot()
	{
		$isRoot = ($this->getLevel() == 0);
		return $isRoot;
	}

	public function isAncestorOf(NodeInterface $node)
	{
		if ($this->getLeftValue() < $node->getLeftValue()
				&& $this->getRightValue() > $node->getRightValue()) {
			
			return true;
		}
		return false;
	}

	public function isDescendantOf(NodeInterface $node)
	{
		$isAncestor = $node->isAncestorOf($this);
		return $isAncestor;
	}

	public function isEqualTo(NodeInterface $node)
	{
		$isEqual = ($this->getLeftValue() == $node->getLeftValue());
		return $isEqual;
	}

	protected function move($pos, $level)
	{
		$spaceNeeded = $this->getIntervalSize() + 1;
		$this->validateMove($pos);

		// I) reserve the space
		$this->repository->extend($pos, $spaceNeeded);

		$oldPos = $this->getLeftValue();
		$levelDiff = $level - $this->getLevel();

		// II) move the node to the place
		$this->repository->move($this, $pos, $levelDiff);

		// III) trim the unused space
		$this->repository->truncate($oldPos, $spaceNeeded);
		return $this;
	}

	protected function validateMove($pos)
	{
		if ($this->getLeftValue() <= $pos && $this->getRightValue() >= $pos) {
			$dump = static::dump($this);
			throw new Exception\InvalidOperation("The move not allowed for {$dump} to position $pos");
		}
	}

	public static function output(array $nodes)
	{
		$array = array();
		foreach ($nodes as $item) {
			$array[$item->getLeftValue()] = $item;
		}
		
		ksort($array);

		$tree = '';
		foreach ($array as $item) {
			$tree .= static::dump($item);
			$tree .= "\n";
		}
		return $tree;
	}

	public function __toString()
	{
		return $this->getTitle();
	}

	public static function dump(NodeInterface $node)
	{
		$prefix = \str_repeat(static::DUMP_PREFIX, $node->getLevel());
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		$level = $node->getLevel();
		$title = $node->getTitle();
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

	public function dumpThis()
	{
		return self::dump($this);
	}

}