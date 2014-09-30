<?php

namespace Supra\Package\Cms\Entity\Blog;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity\PageLocalization;

/**
 * @Entity
 */
class BlogApplicationPostLocalization extends Entity
{
	/**
	 * Blog Application localization id
	 * 
	 * @Column(type="supraId20")
	 * @var string 
	 */
	protected $applicationLocalizationId;
	
	/**
	 * Related page localization id
	 * 
	 * @Column(type="supraId20")
	 * @var string 
	 */
	protected $pageLocalizationId;
	
	/**
	 * Post author Supra User id
	 * 
	 * @Column(type="supraId20")
	 * @var string 
	 */
	protected $authorSupraUserId;
	

	/**
	 * @param \Supra\Controller\Pages\Blog\BlogApplication $application
	 */
	public function __construct(BlogApplication $application) 
	{
		parent::__construct();
		
		$this->applicationLocalizationId = $application->getApplicationLocalization()
				->getId();
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\PageLocalization $localization
	 */
	public function setPageLocalization(PageLocalization $localization)
	{
		$this->pageLocalizationId = $localization->getId();
	}
	
	/**
	 * @return string
	 */
	public function getPageLocalizationId()
	{
		return $this->pageLocalizationId;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\Blog\BlogApplicationUser $user
	 */
	public function setAuthor(BlogApplicationUser $user)
	{
		$supraUserId = $user->getSupraUserId();
		$this->authorSupraUserId = $supraUserId;
	}
	
	/**
	 * @return string
	 */
	public function getAuthorSupraUserId()
	{
		return $this->authorSupraUserId;
	}
}
