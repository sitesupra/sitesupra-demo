<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\BlockProperty;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplateData", "page" = "Supra\Controller\Pages\Entity\PageData"})
 * @Table(name="data")
 */
abstract class Data extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * @OneToMany(targetEntity="Supra\Controller\Pages\Entity\BlockProperty", mappedBy="data", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blockProperties;
	
	/**
	 * Duplicate FK, still needed for DQL when it's not important what type the entity is
	 * @ManyToOne(targetEntity="Page", cascade={"persist", "remove"})
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
	 * @var Page
	 */
	protected $master;

	/**
	 * Construct
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		$this->blockProperties = new ArrayCollection();
		$this->setLocale($locale);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $locale
	 */
	protected function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set master object (page/template)
	 * @param Page $master
	 */
	public function setMaster(Page $master)
	{
		$this->matchDiscriminator($master);
		
		if ($this->writeOnce($this->master, $master)) {
			$this->master = $master;
			$master->setData($this);
		}
	}
	
	/**
	 * Get master object (page/template)
	 * @return Page
	 */
	public function getMaster()
	{
		return $this->master;
	}

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function addBlockProperty(BlockProperty $blockProperty)
	{
		if ($this->lock('blockProperties')) {
			if ($this->addUnique($this->blockProperties, $blockProperty)) {
				$blockProperty->setData($this);
			}
			$this->unlock('blockProperties');
		}
	}


}