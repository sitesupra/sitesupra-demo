<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception;

/**
 * Layout class
 * @Entity
 * @Table(name="layout")
 */
class Layout extends Abstraction\Entity
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
	 * Get id
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
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

	/**
	 * Adds place holder
	 * @param LayoutPlaceHolder $placeHolder
	 */
	public function addPlaceHolder(LayoutPlaceHolder $placeHolder)
	{
		if ($this->lock('placeHolders')) {
			$this->placeHolders->add($placeHolder);
			$placeHolder->setLayout($this);
			$this->unlock('placeHolders');
		}
	}

	/**
	 * Collects place holder names
	 * @return array
	 */
	public function getPlaceHolderNames()
	{
		$names = array();

		/* @var $layoutPlaceHolder LayoutPlaceHolder */
		foreach ($this->placeHolders as $layoutPlaceHolder) {
			$names[] = $layoutPlaceHolder->getName();
		}

		return $names;
	}

	/**
	 * Get layout content
	 * @return string
	 */
	public function getContent()
	{
		$file = $this->getFile();
		if (empty($file)) {
			throw new Exception("No file defined for layout {$this}");
		}
		$filePath = \SUPRA_TEMPLATE_PATH . $file;
		if ( ! \file_exists($filePath) || ! \is_readable($filePath)) {
			throw new Exception("Layout file {$file} is not found
					or not readable for layout {$this}");
		}
		$fileContent = \file_get_contents($filePath);
		return $fileContent;

	}

}