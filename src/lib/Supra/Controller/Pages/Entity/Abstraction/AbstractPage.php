<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Set\PageSet;
use Supra\NestedSet;
use Supra\Controller\Pages\Exception;

/**
 * Page abstraction
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"template" = "Supra\Controller\Pages\Entity\Template", 
 *		"page" = "Supra\Controller\Pages\Entity\Page",
 *		"application" = "Supra\Controller\Pages\Entity\ApplicationPage"
 * })
 * @Table(indexes={
 *		@index(name="page_abstraction_lft_idx", columns={"lft"}),
 *		@index(name="page_abstraction_rgt_idx", columns={"rgt"}),
 *		@index(name="page_abstraction_lvl_idx", columns={"lvl"})
 * })
 * @HasLifecycleCallbacks
 * @method int getNumberChildren()
 * @method AbstractPage addChild(AbstractPage $child)
 * @method void delete()
 * @method boolean hasNextSibling()
 * @method boolean hasPrevSibling()
 * @method int getNumberDescendants()
 * @method boolean hasParent()
 * @method AbstractPage getParent()
 * @method string getPath(string $separator, boolean $includeNode)
 * @method array getAncestors(int $levelLimit, boolean $includeNode)
 * @method array getDescendants(int $levelLimit, boolean $includeNode)
 * @method AbstractPage getFirstChild()
 * @method AbstractPage getLastChild()
 * @method AbstractPage getNextSibling()
 * @method AbstractPage getPrevSibling()
 * @method array getChildren()
 * @method array getSiblings(boolean $includeNode)
 * @method boolean hasChildren()
 * @method AbstractPage moveAsNextSiblingOf(AbstractPage $afterNode)
 * @method AbstractPage moveAsPrevSiblingOf(AbstractPage $beforeNode)
 * @method AbstractPage moveAsFirstChildOf(AbstractPage $parentNode)
 * @method AbstractPage moveAsLastChildOf(AbstractPage $parentNode)
 * @method boolean isLeaf()
 * @method boolean isRoot()
 * @method boolean isAncestorOf(AbstractPage $node)
 * @method boolean isDescendantOf(AbstractPage $node)
 * @method boolean isEqualTo(AbstractPage $node)
 */
abstract class AbstractPage extends Entity implements NestedSet\Node\EntityNodeInterface
{
	/**
	 * Filled by NestedSetListener
	 * @var NestedSet\Node\DoctrineNode
	 */
	protected $nestedSetNode;
	
	/**
	 * @OneToMany(targetEntity="Localization", mappedBy="master", cascade={"persist", "remove"}, indexBy="locale")
	 * @var Collection
	 */
	protected $localizations;

	/**
	 * Object's place holders
	 * @OneToMany(targetEntity="PlaceHolder", mappedBy="master", cascade={"persist", "remove"}, indexBy="name")
	 * @var Collection
	 */
	protected $placeHolders;

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
	 * Cache for getAuthorizedActions()
	 * @var array
	 */
	protected $authorizedActions;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->placeHolders = new ArrayCollection();
		$this->localizations = new ArrayCollection();
	}

	/**
	 * @return Collection
	 */
	public function getPlaceHolders()
	{
		return $this->placeHolders;
	}

	/**
	 * @return Collection
	 */
	public function getLocalizations()
	{
		return $this->localizations;
	}

	/**
	 * Get data item by locale
	 * @param string $locale
	 * @return Localization
	 */
	public function getLocalization($locale)
	{
		$dataCollection = $this->getLocalizations();
		$data = $dataCollection->get($locale);
		
		return $data;
	}

	/**
	 * @param string $locale
	 * @param Localization $data
	 */
	public function setLocalization(Localization $data)
	{
		if ($this->lock('localizations')) {
			$this->matchDiscriminator($data);
			if ($this->addUnique($this->localizations, $data, 'locale')) {
				$data->setMaster($this);
			}
			$this->unlock('localizations');
		}
	}

	/**
	 * Adds placeholder
	 * @param PlaceHolder $placeHolder
	 */
	public function addPlaceHolder(PlaceHolder $placeHolder)
	{
		if ($this->lock('placeHolder')) {
			if ($this->addUnique($this->placeHolders, $placeHolder, 'name')) {
				$placeHolder->setMaster($this);
			}
			$this->unlock('placeHolder');
		}
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
	 * @return AbstractPage
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
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		throw new Exception\LogicException("Method getNestedSetRepositoryClassName shouldn't be called from abstract");
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
	
	public function isBlockPropertyEditable(BlockProperty $blockProperty)
	{
		$page = $blockProperty->getLocalization()
				->getMaster();
		
		$editable = $page->equals($this);

		return $editable;
	}
	
	private function containsBlock(Block $block)
	{
		$page = $block->getPlaceHolder()
				->getMaster();
		
		$contains = $page->equals($this);
		
		return $contains;
	}
	
	public function isBlockEditable(Block $block)
	{
		// Contents are editable if block belongs to the page
		if ($this->containsBlock($block)) {
			return true;
		}
		
		// Also if it's not locked
		if ( ! $block->getLocked()) {
			return true;
		}
		
		return false;
	}
	
	public function isBlockManageable(Block $block)
	{
		// Contents are editable if block belongs to the page
		if ($this->containsBlock($block)) {
			return true;
		}
		
		return false;
	}
	
	public function isPlaceHolderEditable(PlaceHolder $placeHolder)
	{
		// Place holder can be ediable if it belongs to the page
		$page = $placeHolder->getMaster();
		
		if ($page->equals($this)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * {@inheritdoc}
	 * @param NestedSet\Node\DoctrineNode $nestedSetNode
	 */
	public function setNestedSetNode(NestedSet\Node\DoctrineNode $nestedSetNode)
	{
		$this->nestedSetNode = $nestedSetNode;
	}
}
