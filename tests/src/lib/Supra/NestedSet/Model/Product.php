<?php

namespace Supra\Tests\NestedSet\Model;

use Supra\Controller\Pages\Entity\Abstraction\Entity,
		Supra\NestedSet\Exception,
		Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Node\NodeAbstraction,
		Supra\NestedSet\Node\DoctrineNode,
		BadMethodCallException;

/**
 * @Entity(repositoryClass="Supra\Tests\NestedSet\Model\ProductRepository")
 * @Table(name="product")
 * @HasLifecycleCallbacks
 */
class Product extends Entity implements NodeInterface
{
	/**
	 * @var DoctrineNode
	 */
	protected $nestedSetNode;

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id = null;

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
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="decimal", scale=2, nullable=true)
	 * @var float
	 */
	protected $price;

	public function __construct($title = null)
	{
		$this->setTitle($title);
	}

	public function getId()
	{
		return $this->id;
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
	}

	public function setRightValue($right)
	{
		$this->right = $right;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->setRightValue($right);
		}
	}

	public function setLevel($level)
	{
		$this->level = $level;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->setLevel($level);
		}
	}

	public function moveLeftValue($left)
	{
		$this->left += $left;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->moveLeftValue($left);
		}
	}

	public function moveRightValue($right)
	{
		$this->right += $right;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->moveRightValue($right);
		}
	}

	public function moveLevel($level)
	{
		$this->level += $level;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->moveLevel($level);
		}
	}

	/**
	 * @PrePersist
	 * @PostLoad
	 */
	public function createNestedSetNode()
	{
		$this->nestedSetNode = new DoctrineNode();
		$this->nestedSetNode->belongsTo($this);
	}

	public function __toString()
	{
		$result = $this->getTitle();
		return $result;
	}

	public function __call($method, $arguments)
	{
		$node = $this->nestedSetNode;
		if (\is_null($this->nestedSetNode)) {
			throw new BadMethodCallException("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized.");
		}

		if ( ! \method_exists($node, $method)) {
			throw new BadMethodCallException("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
		}
		$callable = array($node, $method);
		$result = \call_user_func_array($callable, $arguments);

		return $result;
	}

	public function dumpThis()
	{
		return NodeAbstraction::dump($this);
	}

	public function free()
	{
		if ( ! is_null($this->nestedSetNode)) {
			$this->nestedSetNode->free($this);
			$this->nestedSetNode = null;
		}
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function setPrice($price)
	{
		$this->price = $price;
	}

}