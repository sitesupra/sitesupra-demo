<?php

namespace Supra\FileStorage\Entity\Abstraction;

use	Supra\NestedSet;

/**
 * File abstraction
 * @Entity(repositoryClass="Supra\FileStorage\Repository\FileNestedSetRepository")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"file" = "Supra\FileStorage\Entity\File", "folder" = "Supra\FileStorage\Entity\Folder", "image" = "Supra\FileStorage\Entity\Image"})
 * @Table(name="su_file_abstraction", indexes={
 *		@index(name="file_abstraction_lft_idx", columns={"lft"}),
 *		@index(name="file_abstraction_rgt_idx", columns={"rgt"}),
 *		@index(name="file_abstraction_lvl_idx", columns={"lvl"})
 * })
 * @HasLifecycleCallbacks
 * @method int getNumberChildren()
 * @method NestedSet\Node\NodeAbstraction addChild(NestedSet\Node\NodeInterface $child)
 * @method void delete()
 * @method boolean hasNextSibling()
 * @method boolean hasPrevSibling()
 * @method int getNumberDescendants()
 * @method boolean hasParent()
 * @method NestedSet\Node\NodeAbstraction getParent()
 * @method string getPath(string $separator, boolean $includeNode)
 * @method array getAncestors(int $levelLimit, boolean $includeNode)
 * @method array getDescendants(int $levelLimit, boolean $includeNode)
 * @method NestedSet\Node\NodeAbstraction getFirstChild()
 * @method NestedSet\Node\NodeAbstraction getLastChild()
 * @method NestedSet\Node\NodeAbstraction getNextSibling()
 * @method NestedSet\Node\NodeAbstraction getPrevSibling()
 * @method array getChildren()
 * @method array getSiblings(boolean $includeNode)
 * @method boolean hasChildren()
 * @method NestedSet\Node\NodeAbstraction moveAsNextSiblingOf(NestedSet\Node\NodeInterface $afterNode)
 * @method NestedSet\Node\NodeAbstraction moveAsPrevSiblingOf(NestedSet\Node\NodeInterface $beforeNode)
 * @method NestedSet\Node\NodeAbstraction moveAsFirstChildOf(NestedSet\Node\NodeInterface $parentNode)
 * @method NestedSet\Node\NodeAbstraction moveAsLastChildOf(NestedSet\Node\NodeInterface $parentNode)
 * @method boolean isLeaf()
 * @method boolean isRoot()
 * @method boolean isAncestorOf(NestedSet\Node\NodeInterface $node)
 * @method boolean isDescendantOf(NestedSet\Node\NodeInterface $node)
 * @method boolean isEqualTo(NestedSet\Node\NodeInterface $node)
 */
class File extends Entity implements NestedSet\Node\NodeInterface
{
	/**
	 * @var NestedSet\Node\DoctrineNode
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
	 * @Column(type="string", name="file_name", nullable=false)
	 * @var string
	 */
	protected $fileName;
	
	/**
	 * @Column(type="datetime", name="created_at")
	 * @var string
	 */
	
	protected $createdTime;
	
	/**
	 * @Column(type="datetime", name="modified_at")
	 * @var string
	 */
	protected $modifiedTime;

	/**
	 * @Column(type="boolean", name="public")
	 * @var integer
	 */
	protected $public = true;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
	 * Get page id
	 * @return integer
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
	 * Returns creation time
	 * @return \DateTime
	 */
	public function getCreatedTime()
	{
		return $this->createdTime;
	}
	
	/**
	 * Sets creation time to now
	 * @PrePersist
	 */
	public function setCreatedTime()
	{
		$this->createdTime = new \DateTime('now');
	}

	/**
	 * Returns last modification time
	 * @return \DateTime
	 */
	public function getModifiedTime()
	{
		return $this->modifiedTime;
	}

	/**
	 * Sets modification time to now 
	 * @PreUpdate
	 * @PrePersist
	 */
	public function setModifiedTime()
	{
		$this->modifiedTime = new \DateTime('now');
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
		$this->nestedSetNode = new NestedSet\Node\DoctrineNode();
		$this->nestedSetNode->belongsTo($this);
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
			throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized. Try persisting object first.");
		}

		if ( ! \method_exists($node, $method)) {
			throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
		}
		$callable = array($node, $method);
		$result = \call_user_func_array($callable, $arguments);

		// Compare the result with $node and return $this on match to keep method chaining
		if ($result === $node) {
			$result = $this;
		}

		return $result;
	}

	/**
	 * @PostRemove
	 */
	public function removeTrigger()
	{
		$this->delete();
	}

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
	 * Get class name to get the repository for
	 * @return string
	 */
	protected function getRepositoryClassName()
	{
		$className = __CLASS__;
		
		return $className;
	}
	
	public function setFileName($fileName) 
	{
		$result = preg_replace('/\s+/i', ' ', $fileName);
		$this->fileName = trim($result);
	}
	
	public function getFileName() 
	{
		return $this->fileName;
	}
	
	public function __toString()
	{
		return $this->getFileName();
	}
	
	/**
	 * Trigger on tree changes, called on move action
	 */
	public function treeChangeTrigger()
	{
		
	}
	
	/**
	 * Get public state
	 *
	 * @return boolean
	 */
	public function isPublic()
	{
		return $this->public;
	}

	/**
	 * Set public state
	 *
	 * @param boolean $public 
	 */
	public function setPublic($public)
	{
		$this->public = $public;
	}

}
