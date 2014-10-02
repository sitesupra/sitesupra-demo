<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;

/**
 * Layout class
 * 
 * @Entity
 */
class Layout extends Abstraction\Entity
{
	/**
	 * @TODO: move to separate media classes?
	 */
	const MEDIA_SCREEN = 'screen';
	
	/**
	 * Layout template file
	 * @Column(type="string")
	 * @var string
	 */
	protected $file;

	/**
	 * @OneToMany(targetEntity="LayoutPlaceHolder", mappedBy="layout", cascade={"persist", "remove"}, indexBy="name")
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
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

	/**
	 * Adds place holder
	 * @param LayoutPlaceHolder $placeHolder
	 */
	public function addPlaceHolder(LayoutPlaceHolder $placeHolder)
	{
		if ($this->lock('placeHolders')) {
			if ($this->addUnique($this->placeHolders, $placeHolder, 'name')) {
				$placeHolder->setLayout($this);
			}
			$this->unlock('placeHolders');
		}
	}

	/**
	 * Collects place holder names
	 * @return array
	 */
	public function getPlaceHolderNames()
	{
		$names = $this->placeHolders->getKeys();

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
			throw new Exception\RuntimeException("No file defined for layout {$this}");
		}	
		$filePath = \SUPRA_TEMPLATE_PATH . $file;
		if ( ! \file_exists($filePath) || ! \is_readable($filePath)) {
			throw new Exception\RuntimeException("Layout file {$file} is not found
					or not readable for layout {$this}");
		}
		$fileContent = \file_get_contents($filePath);
		return $fileContent;

	}

}