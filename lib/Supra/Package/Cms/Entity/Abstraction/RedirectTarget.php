<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Supra\Package\Cms\Entity\PageLocalization;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"child" = "Supra\Package\Cms\Entity\RedirectTargetChild",
 *		"page"	= "Supra\Package\Cms\Entity\RedirectTargetPage",
 *		"url"	= "Supra\Package\Cms\Entity\RedirectTargetUrl"
 * })
 */
abstract class RedirectTarget extends VersionedEntity
{
	/**
	 * @OneToOne(targetEntity="Supra\Package\Cms\Entity\PageLocalization")
	 * @var PageLocalization
	 */
	protected $pageLocalization;

	/**
	 * @return string
	 */
	abstract public function getRedirectUrl();

	/**
	 * @param PageLocalization $localization
	 */
	public function setPageLocalization(PageLocalization $localization)
	{
		$this->pageLocalization = $localization;
	}

	/**
	 * @return PageLocalization
	 */
	protected function getPageLocalization()
	{
		return $this->pageLocalization;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		return $this->pageLocalization;
	}
	
}
