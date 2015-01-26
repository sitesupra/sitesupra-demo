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

namespace Supra\Package\Cms\Entity;

/**
 * Temporary group page object, used for not automatically generated page
 * grouping inside the sitemap (usually in CMS)
 */
class TemporaryGroupPage extends GroupPage
{
	/**
	 * Children to show
	 * @var array
	 */
	private $children;
	
	/**
	 * @var integer
	 */
	private $numberChildren;

	/**
	 * @var \DateTime
	 */
	private $date;
	
	/**
	 * @return array
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param array $children
	 */
	public function setChildren(array $children)
	{
		$this->children = $children;
	}
	
	public function setNumberChildren($numberChildren)
	{
		$this->numberChildren = $numberChildren;
	}
	
	public function getNumberChildren()
	{
		return is_null($this->numberChildren) ? count($this->children) : $this->numberChildren;
	}

	public function hasCalculatedNumberChildren()
	{
		return ! is_null($this->numberChildren);
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @param \DateTime $date
	 */
	public function setGroupDate(\DateTime $date)
	{
		$this->date = $date;
	}
	
	/**
	 * @return \DateTime | null
	 */
	public function getGroupDate()
	{
		return $this->date;
	}
}
