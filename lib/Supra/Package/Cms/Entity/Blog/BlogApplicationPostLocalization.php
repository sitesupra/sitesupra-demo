<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
