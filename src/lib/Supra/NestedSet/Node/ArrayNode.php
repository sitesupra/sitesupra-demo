<?php

namespace Supra\NestedSet\Node;

use Supra\NestedSet\Repository\RepositoryInterface;

/**
 * 
 */
class ArrayNode extends Node
{
	protected $start;

	protected $end;

	protected $depth;

	protected $repository;

	protected $title;

	public function  __construct($start = null, $end = null, $depth = null) {
		$this->start = $start;
		$this->end = $end;
		$this->depth = $depth;
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

	public function getStart()
	{
		return $this->start;
	}

	public function getEnd()
	{
		return $this->end;
	}

	public function getDepth()
	{
		return $this->depth;
	}

	public function setStart($start)
	{
		$this->start = $start;
		return $this;
	}

	public function setEnd($end)
	{
		$this->end = $end;
		return $this;
	}

	public function setDepth($depth)
	{
		$this->depth = $depth;
		return $this;
	}

	public function moveStart($diff)
	{
		$this->start += $diff;
		return $this;
	}

	public function moveEnd($diff)
	{
		$this->end += $diff;
		return $this;
	}

	public function moveDepth($diff)
	{
		$this->depth += $diff;
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

	public function getAncestors($depthLimit = 0, $includeNode = false)
	{
		$start = $this->getStart();
		$end = $this->getEnd();
		$depth = $this->getDepth();
		$filter = function(NodeInterface $node) use ($start, $end, $depth, $depthLimit, $includeNode) {
			if ($node->getStart() > $start) {
				return false;
			}
			if ($node->getEnd() < $end) {
				return false;
			}
			if ($node->getDepth() == $depth && ! $includeNode) {
				return false;
			}
			if ($depthLimit != 0 && $depth - $node->getDepth() > $depthLimit) {
				return false;
			}
			return true;
		};

		$order = function($nodeA, $nodeB) {
			return $nodeB->getDepth() - $nodeA->getDepth();
		};

		$ancestors = $this->repository->search($filter, $order);
		return $ancestors;
	}

	public function getDescendants($depthLimit = 0, $includeNode = false)
	{
		$start = $this->getStart();
		$end = $this->getEnd();
		$depth = $this->getDepth();
		$filter = function(NodeInterface $node) use ($start, $end, $depth, $depthLimit, $includeNode) {
			if ($node->getStart() < $start) {
				return false;
			}
			if ($node->getEnd() > $end) {
				return false;
			}
			if ($node->getDepth() == $depth && ! $includeNode) {
				return false;
			}
			if ($depthLimit != 0 && $node->getDepth() - $depth > $depthLimit) {
				return false;
			}
			return true;
		};

		$order = function($nodeA, $nodeB) {
			return $nodeA->getStart() - $nodeB->getStart();
		};

		$descendants = $this->repository->search($filter, $order);
		return $descendants;
	}

	public function getFirstChild()
	{
		if ( ! $this->hasChildren()) {
			return null;
		}
		$start = $this->start + 1;
		$filter = function(NodeInterface $node) use ($start) {
			if ($node->getStart() == $start) {
				return true;
			}
			return false;
		};

		$firstChild = $this->repository->search($filter);
		if ( ! isset($firstChild[0])) {
			throw new \Exception("Could not find the first child of {$this->dump()} but it must exist");
		}
		$firstChild = $firstChild[0];
		return $firstChild;
	}

	public function getLastChild()
	{
		if ( ! $this->hasChildren()) {
			return null;
		}
		$end = $this->end - 1;
		$filter = function(NodeInterface $node) use ($end) {
			if ($node->getEnd() == $end) {
				return true;
			}
			return false;
		};

		$lastChild = $this->repository->search($filter);
		if ( ! isset($lastChild[0])) {
			throw new \Exception("Could not find the last child of {$this->dump()} but it must exist");
		}
		$lastChild = $lastChild[0];
		return $lastChild;
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

	public function getNextSibling()
	{
		$start = $this->end + 1;
		$filter = function(NodeInterface $node) use ($start) {
			if ($node->getStart() == $start) {
				return true;
			}
			return false;
		};

		$nextSibling = $this->repository->search($filter);
		if ( ! isset($nextSibling[0])) {
			return null;
		}
		$nextSibling = $nextSibling[0];
		return $nextSibling;
	}

	public function getPrevSibling()
	{
		$end = $this->start - 1;
		if ($end < 0) {
			return null;
		}
		$filter = function(NodeInterface $node) use ($end) {
			if ($node->getEnd() == $end) {
				return true;
			}
			return false;
		};

		$prevSibling = $this->repository->search($filter);
		if ( ! isset($prevSibling[0])) {
			return null;
		}
		$prevSibling = $prevSibling[0];
		return $prevSibling;
	}

	public function getNumberChildren()
	{
		$children = $this->getChildren();
		return count($children);
	}

	public function getNumberDescendants()
	{
		$intervalSize = $this->end - $this->start;
		$intervalSize = $intervalSize - 1;
		if ($intervalSize % 2 != 0) {
			throw new \Exception("The size of node {$this->dump()} must be odd number, even number received");
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
			throw new \Exception("Parent node was not found for {$this->dump()} but must exist");
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

	public function getLevel()
	{
		return $this->depth;
	}

	public function hasChildren()
	{
		return ($this->end - $this->start > 1);
	}

	public function moveAsNextSiblingOf(NodeInterface $afterNode)
	{
		$pos = $afterNode->getEnd() + 1;
		$depth = $afterNode->getDepth();
		$this->move($pos, $depth);
		return $this;
	}
	
	public function moveAsPrevSiblingOf(NodeInterface $beforeNode)
	{
		$pos = $beforeNode->getStart();
		$depth = $beforeNode->getDepth();
		$this->move($pos, $depth);
		return $this;
	}

	public function moveAsFirstChildOf(NodeInterface $parentNode)
	{
		$pos = $parentNode->getStart() + 1;
		$depth = $parentNode->getDepth() + 1;
		$this->move($pos, $depth);
		return $this;
	}
	
	public function moveAsLastChildOf(NodeInterface $parentNode)
	{
		$pos = $parentNode->getEnd();
		$depth = $parentNode->getDepth() + 1;
		$this->move($pos, $depth);
		return $this;
	}

	public function isLeaf()
	{
		$isLeaf = ( ! $this->hasChildren());
		return $isLeaf;
	}

	public function isRoot()
	{
		$isRoot = ($this->depth == 0);
		return $isRoot;
	}

	public function isAncestorOf(NodeInterface $node)
	{
		if ($this->start < $node->getStart() && $this->end > $node->getEnd()) {
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
		$isEqual = ($this->getStart() == $node->getStart());
		return $isEqual;
	}

	protected function move($pos, $depth)
	{
		$spaceNeeded = $this->end - $this->start + 1;
		$this->allowMove($pos);

		// I) reserve the space
		$this->repository->extend($pos, $spaceNeeded);

		$oldPos = $this->start;
		$depthDiff = $depth - $this->depth;

		// II) move the node to the place
		$this->repository->move($this, $pos, $depthDiff);

		// III) trim the unused space
		$this->repository->truncate($oldPos, $spaceNeeded);
		return $this;
	}

	protected function allowMove($pos)
	{
		if ($this->start <= $pos && $this->end >= $pos) {
			throw new \Exception("The move not allowed");
		}
		return $this;
	}

}