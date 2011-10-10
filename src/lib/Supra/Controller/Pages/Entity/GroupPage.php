<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * So called "Virtual Folder"
 * @Entity
 */
class GroupPage extends Page
{
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
		$localization = null;
		
		if ($this->localizations->offsetExists($locale)) {
			$localization = $this->localizations->offsetGet[$locale];
		} else {
			// Create fake localization
			$localization = new GroupLocalization($locale, $this);
		}
		
		return $localization;
	}
	
	/**
	 * Force localization persisting
	 * @param GroupLocalization $localization
	 */
	public function persistLocalization(GroupLocalization $localization)
	{
		$this->setLocalization($localization);
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
