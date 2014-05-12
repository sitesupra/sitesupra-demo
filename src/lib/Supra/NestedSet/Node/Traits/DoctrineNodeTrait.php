<?php

namespace Supra\NestedSet\Node\Traits;

use Supra\NestedSet\Node\DoctrineNode;

/**
 * Nested Set Node Entity trait
 * Requires PHP >= 5.4
 */
trait DoctrineNodeTrait
{
	/**
	 * @var DoctrineNode
	 */
	private $nestedSetNode;

    /**
     * @Column(type="integer", name="lft", nullable=true)
     * @var integer
     */
    protected $left;

    /**
     * @Column(type="integer", name="rgt", nullable=true)
     * @var integer
     */
    protected $right;

    /**
     * @Column(type="integer", name="lvl", nullable=true)
     * @var integer
     */
    protected $level;

	/**
	 * @return integer|null
	 */
	public function getLeft()
	{
		return $this->left;
	}

	/**
	 * @return integer|null
	 */
	public function getRight()
	{
		return $this->right;
	}

	/**
	 * @return integer|null
	 */
	public function getLevel()
	{
		return $this->level;
	}

	public function setNestedSetNode(DoctrineNode $node)
	{
		$this->nestedSetNode = $node;
	}

	public function getChildren()
	{
		if ($this->nestedSetNode === null) {
			throw new \RuntimeException('Nested Set Node is not initialized.');
		}

		$this->nestedSet->getChildren();
	}

	public function getParent()
	{
		return $this->parent;
	}
}