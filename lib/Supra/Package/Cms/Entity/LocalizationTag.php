<?php

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;

/**
 * @Entity
 * @Table(indexes={
 *		@index(name="name_idx", columns={"name"}),
 *		@index(name="localization_name_idx", columns={"localization_id", "name"})
 * })
 */
class LocalizationTag extends VersionedEntity
{
	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\Localization", inversedBy="tags")
	 * @var \Supra\Controller\Pages\Entity\Abstraction\Localization
	 */
	protected $localization;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $name;

	
	/**
	 * @param \Supra\Controller\Pages\Entity\Abstraction\Localization $localization
	 */
	public function setLocalization(Abstraction\Localization $localization)
	{
		$this->localization = $localization;
	}
	
	/**
	 * @return \Supra\Controller\Pages\Entity\Abstraction\Localization
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
