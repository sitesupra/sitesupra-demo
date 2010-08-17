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
 * @Table(name="product", indexes={
 *		@index(name="product_lft_idx", columns={"lft"}),
 *		@index(name="product_rgt_idx", columns={"rgt"}),
 *		@index(name="product_lvl_idx", columns={"lvl"})
 * })
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

	/**
	 * Create the product
	 * @param string $title
	 */
	public function __construct($title = null)
	{
		$this->setTitle($title);
	}

	/**
	 * Get product ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get left value
	 * @return int
	 */
	public function getLeftValue()
	{
		return $this->left;
	}

	/**
	 * Get right value
	 * @return int
	 */
	public function getRightValue()
	{
		return $this->right;
	}

	/**
	 * Get depth level
	 * @return int
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * Set left value
	 * @param int $left
	 * @return Product
	 */
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
	 * @return Product
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
	 * @return Product
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
	 * @return Product
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
	 * @return Product
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
	 * @return Product
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
	 * Inernal method, called inside the Doctrine workflows only
	 * @PrePersist
	 * @PostLoad
	 */
	public function createNestedSetNode()
	{
		$this->nestedSetNode = new DoctrineNode();
		$this->nestedSetNode->belongsTo($this);
	}

	/**
	 * Converts object to string
	 * @return string
	 */
	public function __toString()
	{
		$result = $this->getTitle();
		return $result;
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
		if (\is_null($this->nestedSetNode)) {
			throw new BadMethodCallException("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized.");
		}

		if ( ! \method_exists($node, $method)) {
			throw new BadMethodCallException("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
		}
		$callable = array($node, $method);
		$result = \call_user_func_array($callable, $arguments);

		// Compare the result with $node and return $this on match to keep method chaining
		if ($result === $node) {
			$result = $this;
		}

		return $result;
	}

	// Looks that this is not needed, nested set node can do this as well
//	public function dumpThis()
//	{
//		return NodeAbstraction::dump($this);
//	}

	/**
	 * Free the node unsetting the pointers to other objects.
	 * MUST clear entity manager after doing this!
	 */
	public function free()
	{
		if ( ! is_null($this->nestedSetNode)) {
			$this->nestedSetNode->free($this);
			$this->nestedSetNode = null;
		}
	}

	/**
	 * Get product title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set product title
	 * @param string $title
	 * @return Product
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * Get product price
	 * @return float
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * Sets product price
	 * @param float $price
	 * @return Product
	 */
	public function setPrice($price)
	{
		$this->price = $price;
	}

}