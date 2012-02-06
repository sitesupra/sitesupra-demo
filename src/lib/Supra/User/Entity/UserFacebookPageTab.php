<?php

namespace Supra\User\Entity;

use Doctrine\Common\Collections\Collection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database\Entity;
use Doctrine\Common\Collections;

/**
 * User facebook pages
 * @Entity
 * @Table(name="user_facebook_page_tab")
 */
class UserFacebookPageTab extends Entity
{

	/**
	 * @ManyToOne(targetEntity="UserFacebookPage", cascade={"persist"}, inversedBy="tabs")
	 * @JoinColumn(name="page_id", referencedColumnName="id", nullable="false")
	 * @var UserFacebookPage
	 */
	protected $page;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $tabTitle;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $html;

	public function getPage()
	{
		return $this->page;
	}

	public function setPage(UserFacebookPage $page)
	{
		$this->page = $page;
		$page->addTab($this);
	}

	public function getTabTitle()
	{
		return $this->tabTitle;
	}

	public function setTabTitle($tabTitle)
	{
		$this->tabTitle = $tabTitle;
	}

	public function getHtml()
	{
		return $this->html;
	}

	public function setHtml($html)
	{
		$this->html = $html;
	}

}