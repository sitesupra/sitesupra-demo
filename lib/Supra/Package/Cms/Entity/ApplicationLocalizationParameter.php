<?php

namespace Supra\Package\Cms\Entity;

use Supra\Controller\Pages\Entity\ApplicationLocalization;

/**
 * Application localization parameter
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="name_unique_idx", columns={"name", "localization_id"})}))
 */
class ApplicationLocalizationParameter extends Abstraction\Entity
{
//	/**
//	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\ApplicationLocalization", inversedBy="parameters", cascade={"persist"})
//	 * @var \Supra\Controller\Pages\Entity\ApplicationLocalization
//	 */
//	protected $localization;
	
	/**
	 * @Column(type="supraId20", name="localization_id")
	 * @var string
	 */
	protected $localizationId;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;
	
	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();
		$this->name = $name;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return string|null
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\ApplicationLocalization $page
	 */
	public function setApplicationLocalization(ApplicationLocalization $localization)
	{
//		$this->localization = $localization;
//		$localization->addParameterToCollection($this);
		
		$this->localizationId = $localization->getId();
	}
	
//	/**
//	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalization
//	 */
//	public function getApplicationLocalization()
//	{
//		return $this->localization;
//	}
	
	/**
	 * @return string
	 */
	public function getApplicationLocalizationId()
	{
		return $this->localizationId;
	}
}
