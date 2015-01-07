<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 * @Table(indexes={
 *		@index(name="name_idx", columns={"name"}),
 *		@index(name="localization_name_idx", columns={"localization_id", "name"})
 * })
 */
class LocalizationTag extends Abstraction\Entity
{
	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\Localization", inversedBy="tags")
	 * @var Abstraction\Localization
	 */
	protected $localization;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $name;

	/**
	 * @param Abstraction\Localization $localization
	 */
	public function setLocalization(Abstraction\Localization $localization)
	{
		$this->localization = $localization;
	}
	
	/**
	 * @return Abstraction\Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
}
