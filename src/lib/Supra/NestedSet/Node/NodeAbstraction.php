<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\RepositoryInterface,
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
	protected $lft;

	/**
	 * @var int
	 */
	protected $rgt;

	/**
	 * @var int
	 */
	protected $lvl;

	/**
	 * @var RepositoryInterface
	 */
	protected $repository;

	/**
	 * @var string
	 */
	protected $title;

	public function  __construct($lft = null, $rgt = null, $lvl = null) {
		$this->lft = $lft;
		$this->rgt = $rgt;
		$this->lvl = $lvl;
	}

	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function setRepository(RepositoryInterface $repository)
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
		return $this->lft;
	}

	public function getRightValue()
	{
		return $this->rgt;
	}

	public function getLevel()
	{
		return $this->lvl;
	}

	public function setLeftValue($lft)
	{
		$this->lft = $lft;
		return $this;
	}

	public function setRightValue($rgt)
	{
		$this->rgt = $rgt;
		return $this;
	}

	public function setLevel($lvl)
	{
		$this->lvl = $lvl;
		return $this;
	}

	public function moveLeftValue($diff)
	{
		$this->lft += $diff;
		return $this;
	}

	public function moveRightValue($diff)
	{
		$this->rgt += $diff;
		return $this;
	}

	public function moveLevel($diff)
	{
		$this->lvl += $diff;
		return $this;
	}

	public function addChild(NodeInterface $child)
	{
		$child->moveAsLastChildOf($this);
	}

	public function delete()
	{
		$this->repository->delete($this);
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
	 * FIXME: do another count for Doctrine node by the COUNT(*) query
	 * @return int
	 */
	public function getNumberChildren()
	{
		$children = $this->getChildren();
		return count($children);
	}

	public function getNumberDescendants()
	{
		$intervalSize = $this->rgt - $this->lft;
		$intervalSize = $intervalSize - 1;
		if ($intervalSize % 2 != 0) {
			throw new Exception\InvalidStructure("The size of node {$this->dump()} must be odd number, even number received");
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
			throw new Exception\InvalidStructure("Parent node was not found for {$this->dump()} but must exist");
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
		$lft = $this->getLeftValue();
		$rgt = $this->getRightValue();
		$lvl = $this->getLevel();

		$searchCondition = $this->createSearchCondition();
		if ($includeNode) {
			$searchCondition->leftLessThanOrEqualsTo($lft)
					->rightMoreThanOrEqualsTo($rgt);
		} else {
			$searchCondition->leftLessThan($lft)
					->rightMoreThan($rgt);
		}

		if ($levelLimit < 0) {
			throw new Exception\InvalidOperation("Level limit cannot be negative in getAncestors method");
		} elseif ($levelLimit > 0) {
			$searchCondition->levelMoreThanOrEqualsTo($lvl - $levelLimit);
		}

		$order = function($nodeA, $nodeB) {
			return $nodeB->getLevel() - $nodeA->getLevel();
		};

		$ancestors = $this->repository->search($searchCondition, $order);
		return $ancestors;
	}

	public function getDescendants($levelLimit = 0, $includeNode = false)
	{
		$lft = $this->getLeftValue();
		$rgt = $this->getRightValue();
		$lvl = $this->getLevel();

		$searchCondition = $this->createSearchCondition();
		if ($includeNode) {
			$searchCondition->leftMoreThanOrEqualsTo($lft)
					->rightLessThanOrEqualsTo($rgt);
		} else {
			$searchCondition->leftMoreThan($lft)
					->rightLessThan($rgt);
		}

		if ($levelLimit < 0) {
			throw new Exception\InvalidOperation("Level limit cannot be negative in getDescendants method");
		} elseif ($levelLimit > 0) {
			$searchCondition->levelLessThanOrEqualsTo($lvl + $levelLimit);
		}

		$order = function($nodeA, $nodeB) {
			return $nodeA->getLeftValue() - $nodeB->getLeftValue();
		};

		$descendants = $this->repository->search($searchCondition, $order);
		return $descendants;
	}

	public function getFirstChild()
	{
		if ( ! $this->hasChildren()) {
			return null;
		}
		$lft = $this->lft + 1;

		$searchCondition = $this->createSearchCondition();
		$searchCondition->leftEqualsTo($lft);

		$firstChild = $this->repository->search($searchCondition);
		if ( ! isset($firstChild[0])) {
			throw new Exception\InvalidStructure("Could not find the first child of {$this->dump()} but it must exist");
		}
		$firstChild = $firstChild[0];
		return $firstChild;
	}

	public function getLastChild()
	{
		if ( ! $this->hasChildren()) {
			return null;
		}
		$rgt = $this->rgt - 1;

		$searchCondition = $this->createSearchCondition()
				->rightEqualsTo($rgt);

		$lastChild = $this->repository->search($searchCondition);
		if ( ! isset($lastChild[0])) {
			throw new Exception\InvalidStructure("Could not find the last child of {$this->dump()} but it must exist");
		}
		$lastChild = $lastChild[0];
		return $lastChild;
	}

	public function getNextSibling()
	{
		$lft = $this->rgt + 1;
		$searchCondition = $this->createSearchCondition()
				->leftEqualsTo($lft);

		$nextSibling = $this->repository->search($searchCondition);
		if ( ! isset($nextSibling[0])) {
			return null;
		}
		$nextSibling = $nextSibling[0];
		return $nextSibling;
	}

	public function getPrevSibling()
	{
		$rgt = $this->lft - 1;
		if ($rgt < 0) {
			return null;
		}
		$searchCondition = $this->createSearchCondition()
				->rightEqualsTo($rgt);

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
		$hasChildren = ($this->rgt - $this->lft > 1);
		return $hasChildren;
	}

	public function moveAsNextSiblingOf(NodeInterface $afterNode)
	{
		$pos = $afterNode->getRightValue() + 1;
		$lvl = $afterNode->getLevel();
		$this->move($pos, $lvl);
		return $this;
	}

	public function moveAsPrevSiblingOf(NodeInterface $beforeNode)
	{
		$pos = $beforeNode->getLeftValue();
		$lvl = $beforeNode->getLevel();
		$this->move($pos, $lvl);
		return $this;
	}

	public function moveAsFirstChildOf(NodeInterface $parentNode)
	{
		$pos = $parentNode->getLeftValue() + 1;
		$lvl = $parentNode->getLevel() + 1;
		$this->move($pos, $lvl);
		return $this;
	}

	public function moveAsLastChildOf(NodeInterface $parentNode)
	{
		$pos = $parentNode->getRightValue();
		$lvl = $parentNode->getLevel() + 1;
		$this->move($pos, $lvl);
		return $this;
	}

	public function isLeaf()
	{
		$isLeaf = ( ! $this->hasChildren());
		return $isLeaf;
	}

	public function isRoot()
	{
		$isRoot = ($this->lvl == 0);
		return $isRoot;
	}

	public function isAncestorOf(NodeInterface $node)
	{
		if ($this->lft < $node->getLeftValue() && $this->rgt > $node->getRightValue()) {
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

	protected function move($pos, $lvl)
	{
		$spaceNeeded = $this->rgt - $this->lft + 1;
		$this->allowMove($pos);

		// I) reserve the space
		$this->repository->extend($pos, $spaceNeeded);

		$oldPos = $this->lft;
		$levelDiff = $lvl - $this->lvl;

		// II) move the node to the place
		$this->repository->move($this, $pos, $levelDiff);

		// III) trim the unused space
		$this->repository->truncate($oldPos, $spaceNeeded);
		return $this;
	}

	protected function allowMove($pos)
	{
		if ($this->lft <= $pos && $this->rgt >= $pos) {
			throw new Exception\InvalidOperation("The move not allowed for {$this->dump()} to position $pos");
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
			$tree .= $item->dump();
			$tree .= "\n";
		}
		return $tree;
	}

	public function __toString()
	{
		return $this->getTitle();
	}

	public function dump()
	{
		$prefix = \str_repeat(static::DUMP_PREFIX, $this->getLevel());
		$lft = $this->getLeftValue();
		$rgt = $this->getRightValue();
		$lvl = $this->getLevel();
		$title = $this->getTitle();
		$dumpData = array(
			static::DUMP_PREFIX_POS => $prefix,
			static::DUMP_LEFT_POS => $lft,
			static::DUMP_RIGHT_POS => $rgt,
			static::DUMP_LEVEL_POS => $lvl,
			static::DUMP_TITLE_POS => $title
		);
		ksort($dumpData);
		$dumpString = vsprintf(static::DUMP_FORMAT, $dumpData);
		return $dumpString;
	}

}