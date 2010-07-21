<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;

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
	 * @OneToMany(targetEntity="BlockProperty", mappedBy="data", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blockProperties;

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
	abstract public function setMaster(Page $master);

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