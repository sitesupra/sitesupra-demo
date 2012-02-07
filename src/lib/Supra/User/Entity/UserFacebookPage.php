<?php

namespace Supra\User\Entity;

use Doctrine\Common\Collections\Collection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database\Entity;
use Doctrine\Common\Collections;

/**
 * User facebook pages
 * @Entity
 * @Table(name="user_facebook_page")
 */
class UserFacebookPage extends Entity
{

	/**
	 * @OneToOne(targetEntity="UserFacebookData")
	 * @JoinColumn(name="facebook_data_id", referencedColumnName="id")
	 * @var UserFacebookData
	 */
	protected $userData;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string 
	 */
	protected $pageId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string 
	 */
	protected $pageTitle;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string 
	 */
	protected $pageIcon;
	
	/**
	 * @Column(type="string", nullable=false)
	 * @var string 
	 */
	protected $pageLink;

	/**
	 * @OneToMany(targetEntity="UserFacebookPageTab", mappedBy="page", cascade={"persist", "remove"}, indexBy="id")
	 * @var Collections\Collection
	 */
	protected $tabs;

	public function __construct()
	{
		parent::__construct();
		$this->tabs = new Collections\ArrayCollection();
	}

	/**
	 * @return string 
	 */
	public function getPageId()
	{
		return $this->pageId;
	}

	/**
	 * @param string $pageId 
	 */
	public function setPageId($pageId)
	{
		$this->pageId = $pageId;
	}

	/**
	 * @return \Doctrine\ORM\PersistentCollection 
	 */
	public function getTabs()
	{
		return $this->tabs;
	}

	/**
	 * Use UserFacebookPageTab->setPage() method instead
	 * @param UserFacebookPageTab $tab 
	 */
	public function addTab(UserFacebookPageTab $tab)
	{
		$this->tabs->add($tab);
	}

	/**
	 * @return string 
	 */
	public function getPageTitle()
	{
		return $this->pageTitle;
	}

	/**
	 * @param string $pageTitle 
	 */
	public function setPageTitle($pageTitle)
	{
		$this->pageTitle = $pageTitle;
	}

	/**
	 * @return string 
	 */
	public function getPageIcon()
	{
		return $this->pageIcon;
	}

	/**
	 * @param string $pageIcon 
	 */
	public function setPageIcon($pageIcon)
	{
		$this->pageIcon = $pageIcon;
	}

	/**
	 * @return UserFacebookData 
	 */
	public function getUserData()
	{
		return $this->userData;
	}

	public function setUserData(UserFacebookData $userData)
	{
		$this->userData = $userData;
	}

	/**
	 * @return string
	 */
	public function getPageLink()
	{
		return $this->pageLink;
	}

	/**
	 * @param string $pageLink 
	 */
	public function setPageLink($pageLink)
	{
		$this->pageLink = $pageLink;
	}
	
	
}