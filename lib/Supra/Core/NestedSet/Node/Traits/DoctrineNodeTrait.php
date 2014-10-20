<?php

namespace Supra\Core\NestedSet\Node\Traits;

use Supra\NestedSet\Node\DoctrineNode;
use Supra\NestedSet\Exception\BadMethodCall;

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

	public function setNestedSetNode(DoctrineNode $node)
	{
		$this->nestedSetNode = $node;
	}

	public function getNestedSetNode()
	{
		return $this->nestedSetNode;
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
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->setLeftValue($left);
        }
        return $this;
    }

	   /**
     * Set right value
     * @param int $right
     * @return AbstractPage
     */
    public function setRightValue($right)
    {
        $this->right = $right;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->setRightValue($right);
        }
        return $this;
    }

    /**
     * Set depth level
     * @param int $level
     * @return AbstractPage
     */
    public function setLevel($level)
    {
        $this->level = $level;

        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->setLevel($level);
        }
        return $this;
    }

    /**
     * Move left value by the difference
     * @param int $diff
     * @return AbstractPage
     */
    public function moveLeftValue($diff)
    {
        $this->left += $diff;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->moveLeftValue($diff);
        }
        return $this;
    }

    /**
     * Move right value by the difference
     * @param int $diff
     * @return AbstractPage
     */
    public function moveRightValue($diff)
    {
        $this->right += $diff;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->moveRightValue($diff);
        }
        return $this;
    }

    /**
     * Move depth level by the difference
     * @param int $diff
     * @return AbstractPage
     */
    public function moveLevel($diff)
    {
        $this->level += $diff;

        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->moveLevel($diff);
        }
        return $this;
    }

    /**
     * Nested node title
     * @return string
     */
    public function getNodeTitle()
    {
        return $this->__toString();
    }

    /**
     * Try the unknown method against the nested set node
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $node = $this->nestedSetNode;
        if ($this->nestedSetNode === null) {
            throw new BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized.");
        }

        if ( ! method_exists($node, $method)) {
            throw new BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
        }
		
        $callable = array($node, $method);
        $result = call_user_func_array($callable, $arguments);

        // Compare the result with $node and return $this on match to keep method chaining
        if ($result === $node) {
            $result = $this;
        }

        return $result;
    }

    /**
     * Free the node unsetting the pointers to other objects.
     * MUST clear entity manager after doing this!
     */
    public function free()
    {
        if ($this->nestedSetNode !== null) {
            $this->nestedSetNode->free($this);
            $this->nestedSetNode = null;
        }
    }

	/**
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		return $this->CN();
	}
}