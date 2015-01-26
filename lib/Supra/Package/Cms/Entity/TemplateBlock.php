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
 * @Entity
 */
class TemplateBlock extends Abstraction\Block
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::TEMPLATE_DISCR;
	
	/**
	 * Used when block must be created on public scheme triggered by page publish.
	 * Objects with temporary flag are ignored when page is generated.
	 * @Column(name="temporary", type="boolean")
	 * @var boolean
	 */
	protected $temporary = false;
	
	/**
	 * Get locked value
	 * @return boolean
	 */
	public function getLocked()
	{
		return $this->locked;
	}
	
	/**
	 * Set locked value
	 * @param boolean $locked
	 */
	public function setLocked($locked = true)
	{
		$this->locked = $locked;
	}

	/**
	 * @return boolean
	 */
	public function getTemporary()
	{
		return $this->temporary;
	}

	/**
	 * @param boolean $temporary 
	 */
	public function setTemporary($temporary)
	{
		$this->temporary = $temporary;
	}

}
