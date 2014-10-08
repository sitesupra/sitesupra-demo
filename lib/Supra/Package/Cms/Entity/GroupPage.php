<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * So called "Virtual Folder"
 * @Entity
 */
class GroupPage extends Page
{
	const DISCRIMINATOR = self::GROUP_DISCR;
	
	/**
	 * Not localized group title
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;
	
	/**
	 * Creates fake localization
	 * @param string $locale
	 * @return GroupLocalization
	 */
	public function getLocalization($locale)
	{
		$localization = parent::getLocalization($locale);
		
		// Create fake localization if not persisted
		if (is_null($localization)) {
			$localization = $this->createLocalization($locale);
		}
		
		return $localization;
	}
	
	/**
	 * Creates new group localization with the same ID the master has
	 * @param string $locale
	 * @return GroupLocalization
	 */
	public function createLocalization($locale)
	{
		$localization = new GroupLocalization($locale, $this);
		
		return $localization;
	}
	
	/**
	 * Force localization persisting
	 * @param GroupLocalization $localization
	 */
	public function persistLocalization(GroupLocalization $localization)
	{
		if ( ! $localization->isPersistent()) {
			// Reset ID because for not persisted object it is equal with master ID
			$localization->regenerateId();
			$this->setLocalization($localization);
			$localization->setPersistent();
		}
	}

//	/**
//	 * @return ArrayCollection
//	 */
//	public function getLocalizations()
//	{
//		$emptyCollection = new ArrayCollection();
//		
//		return $emptyCollection;
//	}
//	
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * Groups are inside the same repository as the pages
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		return Abstraction\AbstractPage::CN();
	}
}
