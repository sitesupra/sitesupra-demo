<?php

namespace Supra\Package\Cms\Entity\Blog;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Pages\Application\BlogPageApplication;

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
	 * @param BlogPageApplication $application
	 */
	public function __construct(BlogPageApplication $application)
	{
		parent::__construct();
		
		$this->applicationLocalizationId = $application->getApplicationLocalization()
				->getId();
	}

	/**
	 * @param PageLocalization $localization
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
	 * @param \Supra\Package\Cms\Entity\Blog\BlogApplicationUser $user
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
