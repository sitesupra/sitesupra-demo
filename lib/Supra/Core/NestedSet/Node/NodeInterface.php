<?php

namespace Supra\Core\NestedSet\Node;

/**
 * Node interface for nested set nodes and Doctrine entities
 */
interface NodeInterface
{
	/**
	 * Get node's left value of interval
	 * @return int
	 */
	public function getLeftValue();

	/**
	 * Get node's right value of interval
	 * @return int
	 */
	public function getRightValue();

	/**
	 * Get node's depth level
	 * @return int
	 */
	public function getLevel();

	/**
	 * Set node's left value of interval
	 * @param int $left
	 * @return NodeInterface
	 */
	public function setLeftValue($left);

	/**
	 * Set node's right value of interval
	 * @param int $right
	 * @return NodeInterface
	 */
	public function setRightValue($right);

	/**
	 * Set node's depth level
	 * @param int $level
	 * @return NodeInterface
	 */
	public function setLevel($level);

	/**
	 * Increase left value
	 * @param int $diff
	 * @return NodeInterface
	 */
	public function moveLeftValue($diff);

	/**
	 * Increase right value
	 * @param int $diff
	 * @return NodeInterface
	 */
	public function moveRightValue($diff);

	/**
	 * Increase level value
	 * @param int $diff
	 * @return NodeInterface
	 */
	public function moveLevel($diff);
	
	/**
	 * Nested node title
	 * @return string
	 */
	public function getNodeTitle();
}