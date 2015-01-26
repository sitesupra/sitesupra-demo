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

use Symfony\Component\Security\Core\User\UserInterface;
use Supra\Package\Cms\Entity\Abstraction\Entity;

/**
 * Blog application user class
 * Exists as storage for Blog's "About author" field
 * 
 * @Entity
 */
class BlogApplicationUser extends Entity
{
	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $userId;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $name;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $about;
		
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $avatar;
	
	/**
	 * @param UserInterface $user
	 */
	public function __construct(UserInterface $user)
	{
		parent::__construct();

		$this->userId = $user->getUsername();
		$this->name = $user->getUsername();
	}

	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Getter for "About author" text
	 * @return string
	 */
	public function getAboutText()
	{
		return $this->about;
	}
	
	/**
	 * Setter for "About author" text
	 * @param string $aboutText
	 */
	public function setAboutText($aboutText)
	{
		$this->about = $aboutText;
	}
	
	/**
	 * @return string
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}
	
	/**
	 * @param string $avatar
	 */
	public function setAvatar($avatar)
	{
		$this->avatar = $avatar;
	}
}
