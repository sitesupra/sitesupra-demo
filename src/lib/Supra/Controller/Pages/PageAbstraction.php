<?php

namespace Supra\Controller\Pages;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Page abstraction
 * @MappedSuperclass
 */
abstract class PageAbstraction extends EntityAbstraction
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
	 * @var Collection
	 */
	protected $children;

	/**
	 * @var PageAbstraction
     */
	protected $parent;

	/**
	 * Object's place holders
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->children = new ArrayCollection();
		$this->placeHolders = new ArrayCollection();
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
	 * @return PageAbstraction
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set parent page
	 * @var PageAbstraction $parent
	 */
	public function setParent(PageAbstraction $parent = null)
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
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param string $locale
	 * @param PageDataAbstraction $data
	 */
	public function setData($locale, PageDataAbstraction $data)
	{
		$this->removeData($locale);
		$data->setLocale($locale);
		$this->data->add($data);
	}

	/**
	 * @param string $locale
	 * @return boolean
	 */
	public function removeData($locale)
	{
		$dataCollection = $this->getData();
		/* @var $data PageDataAbstraction */
		foreach ($dataCollection as $key => $data) {
			if ($data->getLocale() == $locale) {
				$dataCollection->remove($key);
				self::getConnection()->remove($data);
				return true;
			}
		}
		return false;
	}

}