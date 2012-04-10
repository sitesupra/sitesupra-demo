<?php

namespace Supra\Social\Facebook\Entity;

use Doctrine\Common\Collections\Collection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database\Entity;
use Doctrine\Common\Collections;
use Supra\Database\Doctrine\Listener\Timestampable;
use DateTime;

/**
 * User facebook page tabs
 */
class UserFacebookPageTab extends Entity implements Timestampable
{

	/**
	 * @ManyToOne(targetEntity="UserFacebookPage", cascade={"persist"}, inversedBy="tabs")
	 * @JoinColumn(name="page_id", referencedColumnName="id", nullable=false)
	 * @var UserFacebookPage
	 */
	protected $page;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $tabTitle;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string 
	 */
	protected $html;

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean 
	 */
	protected $published = false;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @return UserFacebookPage 
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param UserFacebookPage $page 
	 */
	public function setPage(UserFacebookPage $page)
	{
		$this->page = $page;
		$page->addTab($this);
	}

	/**
	 * @return string 
	 */
	public function getTabTitle()
	{
		return $this->tabTitle;
	}

	/**
	 * @param string $tabTitle 
	 */
	public function setTabTitle($tabTitle)
	{
		$this->tabTitle = $tabTitle;
	}

	/**
	 * @return string 
	 */
	public function getHtml()
	{
		return $this->html;
	}

	/**
	 * @param string $html 
	 */
	public function setHtml($html)
	{
		$this->html = $html;
	}

	/**
	 * @return boolean 
	 */
	public function isPublished()
	{
		return $this->published;
	}

	/**
	 * @param boolean $published 
	 */
	public function setPublished($published)
	{
		$this->published = $published;
	}

	/**
	 * @return DateTime 
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * @return DateTime  
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->creationTime = $time;
	}

	public function setModificationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->modificationTime = $time;
	}

}
