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
	 * @Column(type="integer", name="lft")
	 * @var integer
	 */
	protected $left;

	/**
	 * @Column(type="integer", name="rgt")
	 * @var integer
	 */
	protected $right;

	/**
	 * @Column(type="integer", name="lvl")
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

	public function setLeftValue($left)
	{
		$this->left = $left;
	}

	public function getRightValue()
	{
		return $this->right;
	}

	public function setRightValue($right)
	{
		$this->right = $right;
	}

	public function getLevel()
	{
		return $this->level;
	}

	public function setLevel($level)
	{
		$this->level = $level;
	}

	public function setNestedSetNode(DoctrineNode $nestedSetNode)
	{
		$this->nestedSetNode = $nestedSetNode;
	}

	/**
	 * @PrePersist
	 * @PostLoad
	 * @return DoctrineNode
	 */
	public function getNestedSetNode()
	{
		if ( ! isset($this->nestedSetNode)) {
			$node = new DoctrineNode($this);
			$this->nestedSetNode = $node;
		}
		return $this->nestedSetNode;
	}

	public function __toString()
	{
		$result = $this->getTitle();
		return $result;
	}

	public function __call($method, $arguments)
	{
		$node = $this->getNestedSetNode();

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
		$this->getNestedSetNode()->free();
		$this->nestedSetNode = null;
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