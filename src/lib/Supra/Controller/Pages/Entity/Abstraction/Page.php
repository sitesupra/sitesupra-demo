<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Entity\BlockProperty,
		Supra\Controller\Pages\Set\PageSet;

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
	 * Object's place holders
	 * @OneToMany(targetEntity="PlaceHolder", mappedBy="master", cascade={"persist", "remove"}, indexBy="name")
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
		$data = $dataCollection->get($locale);
		
		return $data;
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
		$data = $dataCollection->remove($locale);
		
		if ( ! empty($data)) {
			self::getConnection()->remove($data);
			
			return true;
		}
		
		return false;
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
	 * Loads array of page/template template hierarchy
	 * @return PageSet
	 */
	abstract public function getTemplateHierarchy();
	
}