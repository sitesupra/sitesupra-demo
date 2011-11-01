<?php

namespace Supra\FileStorage\Entity\Abstraction;

use Supra\NestedSet;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\Permission\Permission;
use Supra\User\Entity\Abstraction\User;
use Supra\Authorization\AuthorizationProvider;
use Supra\FileStorage\Entity\SlashFolder;
use Supra\Database\Doctrine\Listener\Timestampable;

/**
 * File abstraction
 * @Entity(repositoryClass="Supra\FileStorage\Repository\FileNestedSetRepository")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"file" = "Supra\FileStorage\Entity\File", "folder" = "Supra\FileStorage\Entity\Folder", "image" = "Supra\FileStorage\Entity\Image"})
 * @Table(name="file_abstraction", indexes={
 * 		@index(name="file_abstraction_lft_idx", columns={"lft"}),
 * 		@index(name="file_abstraction_rgt_idx", columns={"rgt"}),
 * 		@index(name="file_abstraction_lvl_idx", columns={"lvl"})
 * })
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
abstract class File extends Entity implements NestedSet\Node\EntityNodeInterface, 
		AuthorizedEntityInterface, Timestampable
{
	const PERMISSION_UPLOAD_NAME = 'file_upload';
	const PERMISSION_UPLOAD_MASK = 256;
	const PERMISSION_DELETE_NAME = 'file_delete';
	const PERMISSION_DELETE_MASK = 512;
	
	/**
	 * Integer object type ID
	 */
	const TYPE_ID = 0;

	/**
	 * @var NestedSet\Node\DoctrineNode
	 */
	protected $nestedSetNode;

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
	 * @var \DateTime
	 */
	
	protected $creationTime;

	/**
	 * @Column(type="datetime", name="modified_at")
	 * @var \DateTime
	 */
	protected $modificationTime;

	/**
	 * @Column(type="boolean", name="public")
	 * @var integer
	 */
	protected $public = true;

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
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * Sets creation time
	 * @param \DateTime $time
	 */
	public function setCreationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime('now');
		}
		$this->creationTime = $time;
	}

	/**
	 * Returns last modification time
	 * @return \DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time
	 * @param \DateTime $time
	 */
	public function setModificationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime('now');
		}
		$this->modificationTime = $time;
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
		if (is_null($this->nestedSetNode)) {
			throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized. Try persisting object first.");
		}

		if ( ! method_exists($node, $method)) {
			throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
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
		if ( ! is_null($this->nestedSetNode)) {
			$this->nestedSetNode->free($this);
			$this->nestedSetNode = null;
		}
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		// One nested set repository for folders, files, images
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

	/**
	 * {@inheritdoc}
	 * @param NestedSet\Node\DoctrineNode $nestedSetNode
	 */
	public function setNestedSetNode(NestedSet\Node\DoctrineNode $nestedSetNode)
	{
		$this->nestedSetNode = $nestedSetNode;
	}

	/**
	 * Loads item info array
	 * @param string $locale
	 * @return array
	 */
	public function getInfo($locale) {
		$info = array(
				'id' => $this->getId(),
				'filename' => $this->getFileName(),
				'type' => static::TYPE_ID
		);

		return $info;
	}

	/**
	 * @param User $user
	 * @param Permission $permission
	 * @param boolean $grant
	 * @return boolean
	 */
	public function authorize(User $user, $permission, $grant) 
  {
		return $grant;
	}

	/**
	 * @return string
	 */
	public function getAuthorizationId() 
	{
		return $this->getId();
	}

	/**
	 * @return string
	 */
	public function getAuthorizationClass() 
	{
		return __CLASS__;
	}
	
	/**
	 * @return array
	 */
	public function getAuthorizationAncestors() 
	{
		$ancestors = $this->getAncestors(0, false);
		
		// Append synthetic "slash" folder to the beginng of ancestors list.
		$ancestors[] = new SlashFolder();
		
		return $ancestors;
	}	
	
	public static function registerPermissions(AuthorizationProvider $ap) 
	{
		$ap->registerGenericEntityPermission(self::PERMISSION_DELETE_NAME, self::PERMISSION_DELETE_MASK, __CLASS__);
		$ap->registerGenericEntityPermission(self::PERMISSION_UPLOAD_NAME, self::PERMISSION_UPLOAD_MASK, __CLASS__);
	}
	
}
