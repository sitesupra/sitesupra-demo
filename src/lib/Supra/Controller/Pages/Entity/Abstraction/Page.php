<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection;

/**
 * Page abstraction
 * @MappedSuperclass
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
	protected $children;

	/**
	 * @var Page
     */
	protected $parent;

	/**
	 * Object's place holders
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * @Column(type="integer")
	 * @var int
	 */
	protected $depth = 1;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->children = new ArrayCollection();
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
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set parent page
	 * @var Page $parent
	 */
	public function setParent(Page $parent = null)
	{
		if ( ! empty($this->parent)) {
			$this->parent->getChildren()->remove($this);
		}
		$this->parent = $parent;
		$parent->getChildren()->add($this);
	}

	/**
	 * Get children pages
	 * @return Collection
	 */
	public function getChildren()
	{
		return $this->children;
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
		$this->isInstanceOf($data, static::$dataClass, __METHOD__);
		
		if ($this->lock('data')) {
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
	 * Get page depth
	 * @return int $depth
	 */
	protected function getDepth()
	{
		return $this->depth;
	}

	/**
	 * Set page depth
	 * @param int $depth
	 */
	protected function setDepth($depth)
	{
		$this->depth = $depth;
	}

}