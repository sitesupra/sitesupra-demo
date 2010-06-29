<?php

namespace Supra\Controller\Pages;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Layout class
 * @Entity
 */
class Layout extends EntityAbstraction
{

	/**
	 * Layout ID
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * Layout template file
	 * @Column(type="string")
	 * @var string
	 */
	protected $file;

	/**
	 * @OneToMany(targetEntity="LayoutPlaceHolder", mappedBy="layout", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->placeHolders = new ArrayCollection();
	}

	/**
	 * Set layout template file
	 * @param string $file
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	 * Get layout file
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Get layout place holders
	 * @return Collection
	 */
	public function getPlaceHolders()
	{
		return $this->placeHolders;
	}

}