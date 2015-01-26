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

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Package\Cms\Entity\Abstraction\TimestampableInterface;

/**
 * Group object
 * @Entity
 * @Table(name="group") 
 */
class Group extends AbstractUser implements TimestampableInterface
{

	/**
	 * @Column(type="boolean", name="is_super")
	 * @var boolean
	 */
	protected $isSuper = false;

	/**
	 * {@inheritDoc}
	 */
	public function isSuper()
	{

		return $this->isSuper;
	}

	/**
	 * Sets groups SUPER privilege status
	 * @param boolean $isSuper
	 */
	public function setIsSuper($isSuper)
	{

		$this->isSuper = $isSuper;
	}

	/**
	 * Returns itself.
	 * @return Group
	 */
	public function getGroup()
	{
		return $this;
	}
	
	/**
	 * @param array $groupData
	 */
	public function fillFromArray(array $groupData) 
	{
		$this->id = $groupData['id'];
		$this->creationTime = $groupData['creationTime'];
		$this->modificationTime = $groupData['modificationTime'];
		$this->isSuper = $groupData['isSuper'];
		$this->name = $groupData['name'];
	}

	public static function getAlias()
	{
		return 'user';
	}

}
