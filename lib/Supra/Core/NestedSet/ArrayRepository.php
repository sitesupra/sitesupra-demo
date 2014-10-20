<?php

namespace Supra\Core\NestedSet;

/**
 * Array nested set repository
 */
class ArrayRepository extends RepositoryAbstraction
{
	/**
	 * List of managed nodes
	 * @var array
	 */
	protected $array = array();

	/**
	 * Maximal value of interval index
	 * @var int
	 */
	protected $max = 0;

	/**
	 * Get maximal interval value used by nodes
	 * @return int
	 */
	protected function getMax()
	{
		$max = $this->max;
		$this->max += 2;
		return $max;
	}

	/**
	 * Add the node to the repository
	 * @param Node\NodeInterface $node
	 */
	public function add(Node\NodeInterface $node)
	{
		$node->setRepository($this);
		parent::add($node);
		$this->array[] = $node;
	}

	/**
	 * New node factory
	 * @param string $title
	 * @return Node\ArrayNode
	 */
	public function createNode($title = null)
	{
		$node = new Node\ArrayNode();
		$node->setNodeTitle($title);
		$this->add($node);
		
		return $node;
	}

//	public function extend($offset, $size)
//	{
//		/* @var $node Node\NodeInterface */
//		foreach ($this->array as $node) {
//			if ($node->getLeftValue() >= $offset) {
//				$node->moveLeftValue($size);
//			}
//			if ($node->getRightValue() >= $offset) {
//				$node->moveRightValue($size);
//			}
//		}
//	}

	/**
	 * Remove unused space in the nested set intervals
	 * @param int $offset
	 * @param int $size
	 */
	public function truncate($offset, $size)
	{
		/* @var $node Node\NodeInterface */
		foreach ($this->array as $node) {
			if ($node->getLeftValue() >= $offset) {
				$this->moveNode($node, - $size, 0, 0);
			}
			if ($node->getRightValue() >= $offset) {
				$this->moveNode($node, 0, - $size, 0);
			}
		}
	}

//	public function oldMove(Node\NodeInterface $node, $pos, $levelDiff = 0)
//	{
//		$diff = $pos - $node->getLeftValue();
//		$left = $node->getLeftValue();
//		$right = $node->getRightValue();
//		if ($diff == 0 && $levelDiff == 0) {
//			return;
//		}
//		foreach ($this->array as $item) {
//			if ($item->getLeftValue() >= $left && $item->getRightValue() <= $right) {
//				$item->moveLeftValue($diff);
//				$item->moveRightValue($diff);
//				$item->moveLevel($levelDiff);
//			}
//		}
//	}

	/**
	 * Move the node to the new position and change level by {$levelDiff}
	 * @param Node\NodeInterface $node
	 * @param int $pos
	 * @param int $levelDiff
	 */
	public function move(Node\NodeInterface $node, $pos, $levelDiff)
	{
		// Current node's interval
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		
		// How big is the node
		$spaceUsed = $right - $left + 1;
		
		// Tells for how much index the node will be moved
		$moveA = 0;
		
		// Tells for how much index the nodes between the node's current and 
		// future position will be moved
		$moveB = 0;
		
		// Tells the interval for indeces to be moved which are not inside the 
		// node interval (usually $a <= $b)
		$a = $b = 0;
		
		// Move to the right
		if ($pos > $left) {
			$a = $right + 1;
			$b = $pos - 1;
			$moveA = $pos - $left - $spaceUsed;
			$moveB = - $spaceUsed;
			
		// Move to the left
		} else {
			$a = $pos;
			$b = $left - 1;
			$moveA = $pos - $left;
			$moveB = $spaceUsed;
		}
		
		// There is nothing to move
		if ($moveA == 0 && $levelDiff == 0) {
			return;
		}
		
		foreach ($this->array as $item) {
			/* @var $item Node\NodeInterface */
			$itemLeft = $item->getLeftValue();
			$itemRight = $item->getRightValue();
			
			// Children of the page being moved
			if (self::isBetween($itemLeft, $left, $right)) {
				
				if ( ! self::isBetween($itemRight, $left, $right)) {
					throw new Exception\InvalidStructure("Node $item left index is between $node index but the right isn't");
				}

				$this->moveNode($item, $moveA, $moveA, $levelDiff);

//				$item->moveLeftValue($moveA);
//				$item->moveRightValue($moveA);
//				$item->moveLevel($levelDiff);
				continue;
			}

			// Left index matches the interval
			if (self::isBetween($itemLeft, $a, $b)) {
				$this->moveNode($item, $moveB, 0, 0);
//				$item->moveLeftValue($moveB);
			}
			
			// Right index matches the interval
			if (self::isBetween($itemRight, $a, $b)) {
				$this->moveNode($item, 0, $moveB, 0);
//				$item->moveRightValue($moveB);
			}
		}
	}

	/**
	 * Method to move node left/right/level so could be extended.
	 * DoctrineRepositoryArrayHelper is extending this with node "refresh".
	 * @param Node\NodeInterface $item
	 * @param int $moveLeft
	 * @param int $moveRight
	 * @param int $moveLevel
	 */
	protected function moveNode(Node\NodeInterface $item, $moveLeft, $moveRight, $moveLevel)
	{
		if ( ! empty($moveLeft)) {
			$item->moveLeftValue($moveLeft);
		}
		if ( ! empty($moveRight)) {
			$item->moveRightValue($moveRight);
		}
		if ( ! empty($moveLevel)) {
			$item->moveLevel($moveLevel);
		}
	}

	/**
	 * Whether the value {$a} is between {$b} and {$c}
	 * @param int $a
	 * @param int $b
	 * @param int $c
	 * @return boolean
	 */
	private static function isBetween($a, $b, $c)
	{
		return $b <= $a && $a <= $c;
	}

	/**
	 * Delete the node
	 * @param Node\NodeInterface $node
	 */
	public function delete(Node\NodeInterface $node)
	{
		$left = $node->getLeftValue();
		$right = $node->getRightValue();
		foreach ($this->array as $key => $item) {
			if ($item->getLeftValue() >= $left && $item->getRightValue() <= $right) {
				unset($this->array[$key]);
			}
		}
	}

	/**
	 * Perform the search in the array
	 * @param SearchCondition\SearchConditionInterface $filter
	 * @param SelectOrder\SelectOrderInterface $order
	 * @return array
	 */
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

	/**
	 * Perform data select by search and order closures
	 * @param \Closure $filterClosure
	 * @param \Closure $orderClosure
	 * @return array
	 */
	public function searchByClosure(\Closure $filterClosure, \Closure $orderClosure = null)
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
	 * @return Node\ArrayNode
	 */
	public function byTitle($title)
	{
		$filter = function(Node\NodeInterface $node) use ($title) {
			if ($node->getNodeTitle() == $title) {
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
	 * Create search condition object
	 * @return SearchCondition\ArraySearchCondition
	 */
	public function createSearchCondition()
	{
		$searchCondition = new SearchCondition\ArraySearchCondition();
		return $searchCondition;
	}

	/**
	 * Create order rule object
	 * @return SelectOrder\ArraySelectOrder
	 */
	public function createSelectOrderRule()
	{
		$selectOrder = new SelectOrder\ArraySelectOrder();
		return $selectOrder;
	}
}