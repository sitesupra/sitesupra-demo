<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;

/**
 * @Entity
 */
class PlaceHolderGroup extends VersionedEntity
{
	/**
	 * @Column(name="name", type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="string")
	 * @var 
	 */
	protected $title;
	
	/**
	 * @Column(type="string")
	 * @var
	 */
	protected $groupLayout;
	
	/**
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\Abstraction\PlaceHolder", mappedBy="group", cascade={"all"}, orphanRemoval=true)
	 * @var ArrayCollection
	 */
	protected $placeholders;	

	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\Localization", inversedBy="placeHolderGroups")
	 * @JoinColumn(name="localization_id", referencedColumnName="id", nullable=false)
	 * @var Localization
	 */
	protected $localization;
	
	/**
	 * @Column(type="boolean")
	 * @var
	 */
	protected $locked = false;

	/**
	 * Constructor
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->placeholders = new ArrayCollection();
	}

	/**
	 * Set layout place holder name
	 * @param string $Name
	 */
	protected function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get layout place holder name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * @param Localization $localization
	 */
	public function setLocalization(Localization $localization)
	{
//		$this->matchDiscriminator($localization);
//		if ($this->writeOnce($this->localization, $localization)) {
//			$this->localization->addPlaceHolder($this);
//		}
		
		$this->localization = $localization;
	}

	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}

	public function getPlaceholders()
	{
		return $this->placeholders;
	}
	
	public function addPlaceholder($placeHolder)
	{
		$this->placeholders->set($placeHolder->getName(), $placeHolder);
	}
	
	public function setGroupLayout($layout)
	{
		$this->groupLayout = $layout->getName();
	}
	
	/**
	 * @param string $name
	 */
	public function setGroupLayoutName($name)
	{
		$this->groupLayout = $name;
	}
	
	/**
	 * @return string
	 */
	public function getGroupLayout()
	{
		return $this->getGroupLayoutName();
	}
	
	/**
	 * @return string
	 */
	public function getGroupLayoutName()
	{
		return $this->groupLayout;
	}
	
	/**
	 * 
	 * @param boolean $locked
	 */
	public function setLocked($locked)
	{
		$this->locked = $locked;
	}
	
	/**
	 * @var boolean
	 */
	public function getLocked()
	{
		return (bool) $this->locked;
	}
	
	public static function factory($sourceGroup)
	{
		$layoutName = null;
		$locked = false;
		
		if ($sourceGroup instanceof Theme\ThemeLayoutPlaceholderGroup) {
			$layoutName = $sourceGroup->getDefaultLayout()
					->getName();
		}
		else if ($sourceGroup instanceof PlaceHolderGroup) {
			$layoutName = $sourceGroup->getGroupLayoutName();
			$locked = $sourceGroup->getLocked();
		}
		else {
			throw new \InvalidArgumentException("Source group must be an instance of ThemeLayoutPlaceHolderGroup or PlaceHolderGroup object");
		}
		
		$group = new self($sourceGroup->getName());
		
		$group->setGroupLayoutName($layoutName);
		$group->setLocked($locked);
		$group->setTitle($sourceGroup->getTitle());
		
		return $group;
	}
}
