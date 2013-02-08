<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class TemplatePlaceHolderGroup extends Abstraction\Entity implements Abstraction\AuditedEntityInterface
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
	 * @OneToMany(targetEntity="Supra\Controller\Pages\Entity\Abstraction\PlaceHolder", mappedBy="group", cascade={"all"}, orphanRemoval=true)
	 * @var ArrayCollection
	 */
	protected $placeholders;	

	/**
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\TemplateLocalization", inversedBy="placeHolderGroups")
	 * @JoinColumn(name="localization_id", referencedColumnName="id", nullable=false)
	 * @var Localization
	 */
	protected $localization;

	
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
	public function setLocalization(\Supra\Controller\Pages\Entity\TemplateLocalization $localization)
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
	
	public function setGroupLayout(\Supra\Controller\Pages\Entity\Theme\ThemePlaceholderGroupLayout $layout)
	{
		$this->groupLayout = $layout->getName();
	}
	
	public function getGroupLayout()
	{
		return $this->groupLayout;
	}
}
