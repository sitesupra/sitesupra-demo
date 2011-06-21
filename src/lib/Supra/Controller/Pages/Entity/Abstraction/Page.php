<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Entity\BlockProperty;

/**
 * Page abstraction
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\Template", "page" = "Supra\Controller\Pages\Entity\Page"})
 * @Table(name="page_abstraction")
 */
abstract class Page extends Entity
{
	/**
	 * Data class
	 * @var string
	 */
	static protected $dataClass = null;

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id = null;

	/**
	 * @var Collection
	 */
	protected $data;

	/**
	 * @var Collection
	 */
//	protected $children;

	/**
	 * @var Page
     */
//	protected $parent;

	/**
	 * Object's place holders
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * This field duplicates page and template field "level". This is done 
	 * because we need to know the depth of the master element as well when
	 * searching for place holders
	 * @Column(type="integer")
	 * @var int
	 */
	protected $depth = 1;

	/**
	 * Constructor
	 */
	public function __construct()
	{
//		$this->children = new ArrayCollection();
		$this->placeHolders = new ArrayCollection();
		$this->data = new ArrayCollection();
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
	 * Get parent page
	 * @return Page
	 */
//	public function getParent()
//	{
//		return $this->parent;
//	}

	/**
	 * Set parent page
	 * @var Page $parent
	 */
//	public function setParent(Page $parent = null)
//	{
//		$this->matchDiscriminator($parent);
//		if ( ! empty($this->parent)) {
//			$this->parent->getChildren()->remove($this);
//		}
//		$this->parent = $parent;
//		$parent->getChildren()->add($this);
//	}

	/**
	 * Get children pages
	 * @return Collection
	 */
//	public function getChildren()
//	{
//		return $this->children;
//	}

	/**
	 * @return \Doctrine\ORM\PersistentCollection
	 */
	public function getPlaceHolders()
	{
		return $this->placeHolders;
	}

	/**
	 * @return Collection
	 */
	public function getDataCollection()
	{
		return $this->data;
	}

	/**
	 * Get data item by locale
	 * @param string $locale
	 * @return Data
	 */
	public function getData($locale)
	{
		$dataCollection = $this->getDataCollection();
		/* @var $data Data */
		foreach ($dataCollection as $data) {
			if ($data->getLocale() == $locale) {
				return $data;
			}
		}
		return null;
	}

	/**
	 * @param string $locale
	 * @param Data $data
	 */
	public function setData(Data $data)
	{
		if ($this->lock('data')) {
			$this->matchDiscriminator($data);
			if ($this->addUnique($this->data, $data, 'locale')) {
				$data->setMaster($this);
			}
			$this->unlock('data');
		}
	}

	/**
	 * @param string $locale
	 * @return boolean
	 */
	public function removeData($locale)
	{
		$dataCollection = $this->getDataCollection();
		/* @var $data Data */
		foreach ($dataCollection as $key => $data) {
			if ($data->getLocale() == $locale) {
				$dataCollection->remove($key);
				self::getConnection()->remove($data);
				return true;
			}
		}
		return false;
	}

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
	 * Get element depth
	 * @return int $depth
	 */
	protected function getDepth()
	{
		return $this->depth;
	}

	/**
	 * Set element depth
	 * @param int $depth
	 */
	protected function setDepth($depth)
	{
		$this->depth = $depth;
	}
	
	public function isBlockPropertyEditable(BlockProperty $blockProperty)
	{
		$page = $blockProperty->getData()
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
	 * Loads array of page/template hierarchy
	 * @return Page[]
	 */
	abstract public function getHierarchy();
	
}