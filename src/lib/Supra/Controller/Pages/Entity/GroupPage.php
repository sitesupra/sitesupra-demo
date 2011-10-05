<?php

namespace Supra\Controller\Pages\Entity;

/**
 * So called "Virtual Folder"
 * @Entity
 */
class GroupPage extends Abstraction\AbstractPage
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
	 */
	public function getLocalization($locale)
	{
		$fakeLocalization = new GroupLocalization($locale, $this);
		
		return $fakeLocalization;
	}

	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getLocalizations()
	{
		$emptyCollection = new \Doctrine\Common\Collections\ArrayCollection();
		
		return $emptyCollection;
	}
	
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
