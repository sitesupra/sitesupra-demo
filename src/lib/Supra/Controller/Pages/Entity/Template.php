<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception,
		Supra\NestedSet,
		Supra\Controller\Pages\Set\PageSet;

/**
 * Page controller template class
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\TemplateRepository")
 * @Table(name="template", indexes={
 *		@index(name="template_lft_idx", columns={"lft"}),
 *		@index(name="template_rgt_idx", columns={"rgt"}),
 *		@index(name="template_lvl_idx", columns={"lvl"})
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
class Template extends Abstraction\Page implements NestedSet\Node\NodeInterface
{
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
	 * @OneToMany(targetEntity="TemplateData", mappedBy="template", cascade={"persist", "remove"}, indexBy="locale")
	 * @var Collection
	 */
	protected $data;

	/**
	 * @OneToMany(targetEntity="TemplateLayout", mappedBy="template", cascade={"persist", "remove"}, indexBy="media")
	 * @var Collection
	 */
	protected $templateLayouts;

	/**
	 * Template place holders
	 * @OneToMany(targetEntity="TemplatePlaceHolder", mappedBy="master", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->templateLayouts = new ArrayCollection();
		parent::__construct();
	}

	/**
	 * Set templateLayout
	 * @param TemplateLayout $templateLayout
	 */
	public function addTemplateLayout(TemplateLayout $templateLayout)
	{
		if ($this->hasParent()) {
			throw new Exception\RuntimeException("Template layout can be set to root template only");
		}
		if ($this->lock('templateLayout')) {
			if ($this->addUnique($this->templateLayouts, $templateLayout, 'media')) {
				$templateLayout->setTemplate($this);
			}
			$this->unlock('templateLayout');
		}
	}

	/**
	 * Get template templateLayout
	 * @return Collection
	 */
	public function getTemplateLayouts()
	{
		return $this->templateLayouts;
	}

	/**
	 * Add layout for specific media
	 * @param string $media
	 * @param Layout $layout
	 * @throws Exception\RuntimeException if layout for this media already exists
	 */
	public function addLayout($media, Layout $layout)
	{
		$templateLayout = new TemplateLayout($media);
		$templateLayout->setLayout($layout);
		$templateLayout->setTemplate($this);
	}

	/**
	 * Removes layout of specific media
	 * @param string $media
	 * @return boolean
	 */
	public function removeLayout($media)
	{
		$templateLayouts = $this->getTemplateLayouts();
		/* @var $templateLayout TemplateLayout */
		$templateLayout = $templateLayouts->remove($media);
		
		if ( ! is_null($templateLayout)) {
			self::getConnection()->remove($templateLayout);
			
			return true;
		}
		
		return false;
	}

	/**
	 * Get layout object by
	 * @param string $media
	 * @return Layout
	 */
	public function getLayout($media = Layout::MEDIA_SCREEN)
	{
		$templateLayouts = $this->getTemplateLayouts();
		/* @var $templateLayout TemplateLayout */
		foreach ($templateLayouts as $key => $templateLayout) {
			if ($templateLayout->getMedia() == $media) {
				return $templateLayout->getLayout();
			}
		}
		throw new Exception\RuntimeException("No layout found for template #{$this->getId()} media '{$media}'");
	}

	/**
	 * Get array of template hierarchy starting from the root
	 * @return PageSet
	 */
	public function getTemplateHierarchy()
	{
		/* @var $templates Template[] */
		$templates = $this->getAncestors(0, true);
		$templates = array_reverse($templates);
		
		$pageSet = new PageSet($templates);
		
		return $pageSet;
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
		$this->setDepth($level);
		
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
		$this->setDepth($this->level);
		
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
			throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized.");
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
	 * @PreRemove
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

}