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

namespace Supra\Package\Cms\Pages\Finder\Organizer\Iterator;

class RecursiveLocalizationIterator implements \RecursiveIterator
{

	protected $data;
	protected $position = 0;
	protected $depth = 0;

	/**
	 * @param array $data 
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}

	/**
	 * @return boolean 
	 */
	public function valid()
	{
		return isset($this->data[$this->position]);
	}

	/**
	 * @return boolean 
	 */
	public function hasChildren()
	{
		return ($this->data[$this->position]['children'] instanceof RecursiveLocalizationIterator);
	}

	public function next()
	{
		$this->position ++;
	}

	/**
	 * @return \Supra\Package\Cms\Entity\PageLocalization
	 */
	public function current()
	{
		return $this->data[$this->position]['localization'];
	}

	/**
	 * @return RecursiveLocalizationIterator 
	 */
	public function getChildren()
	{
		return $this->data[$this->position]['children'];
	}

	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * @return integer 
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * @return integer 
	 */
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	 * @param integer $depth 
	 */
	public function setDepth($depth)
	{
		$this->depth = $depth;
	}

}