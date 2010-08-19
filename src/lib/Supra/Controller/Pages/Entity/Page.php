<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception,
		Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Node\NodeAbstraction,
		Supra\NestedSet\Node\DoctrineNode,
		BadMethodCallException;

/**
 * Page controller page object
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\PageRepository")
 * @Table(name="page", indexes={
 *		@index(name="page_lft_idx", columns={"lft"}),
 *		@index(name="page_rgt_idx", columns={"rgt"}),
 *		@index(name="page_lvl_idx", columns={"lvl"})
 * })
 * @HasLifecycleCallbacks
 * @method int getNumberChildren()
 * @method NodeAbstraction addChild(NodeInterface $child)
 * @method void delete()
 * @method boolean hasNextSibling()
 * @method boolean hasPrevSibling()
 * @method int getNumberDescendants()
 * @method boolean hasParent()
 * @method NodeAbstraction getParent()
 * @method string getPath(string $separator, boolean $includeNode)
 * @method array getAncestors(int $levelLimit, boolean $includeNode)
 * @method array getDescendants(int $levelLimit, boolean $includeNode)
 * @method NodeAbstraction getFirstChild()
 * @method NodeAbstraction getLastChild()
 * @method NodeAbstraction getNextSibling()
 * @method NodeAbstraction getPrevSibling()
 * @method array getChildren()
 * @method array getSiblings(boolean $includeNode)
 * @method boolean hasChildren()
 * @method NodeAbstraction moveAsNextSiblingOf(NodeInterface $afterNode)
 * @method NodeAbstraction moveAsPrevSiblingOf(NodeInterface $beforeNode)
 * @method NodeAbstraction moveAsFirstChildOf(NodeInterface $parentNode)
 * @method NodeAbstraction moveAsLastChildOf(NodeInterface $parentNode)
 * @method boolean isLeaf()
 * @method boolean isRoot()
 * @method boolean isAncestorOf(NodeInterface $node)
 * @method boolean isDescendantOf(NodeInterface $node)
 * @method boolean isEqualTo(NodeInterface $node)
 */
class Page extends Abstraction\Page implements NodeInterface
{
	/**
	 * @var DoctrineNode
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
	 * Data class
	 * @var string
	 */
	static protected $dataClass = 'Supra\Controller\Pages\Entity\PageData';

	/**
	 * @OneToMany(targetEntity="PageData", mappedBy="page", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $data;

	/**
	 * @ManyToOne(targetEntity="Template", cascade={"persist"}, fetch="EAGER")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
	 * @var Template
	 */
	protected $template;

	/**
	 * @OneToMany(targetEntity="Page", mappedBy="parent")
	 * @var Collection
	 */
	protected $children;

	/**
     * @ManyToOne(targetEntity="Page", inversedBy="children")
	 * @var Page
     */
	protected $parent;

	/**
	 * Page place holders
	 * @OneToMany(targetEntity="PagePlaceHolder", mappedBy="page", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->pageData = new ArrayCollection();
	}

	/**
	 * Set page template
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->template = $template;
	}

	/**
	 * Get page template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Sets page as parent of this page
	 * @param Abstraction\Page $page
	 */
	public function setParent(Abstraction\Page $page = null)
	{
		parent::setParent($page);

		// Change full path for all data items
		$pageDatas = $this->getDataCollection();
		foreach ($pageDatas as $pageData) {
			/* @var $pageData PageData */
			$pageData->setPathPart($pageData->getPathPart());
		}

		$this->setDepth($page->depth + 1);
	}

	/**
	 * Get page template hierarchy starting with the root template
	 * @return Template[]
	 * @throws Exception
	 */
	public function getTemplates()
	{
		$template = $this->getTemplate();

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception("No template assigned to the page {$page->getId()}");
		}

		$templates = $template->getTemplatesHierarchy();

		return $templates;
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

}