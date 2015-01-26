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

namespace Supra\Package\Cms\Html;

abstract class HtmlTagAbstraction
{
	/**
	 * @var string
	 */
	protected $tagName;
	
	/**
	 * @param string $tagName
	 * @param string $content
	 */
	function __construct($tagName)
	{
		$this->setTagName($tagName);
	}
	
	/**
	 * @param string $tagName
	 */
	public function setTagName($tagName)
	{
		$this->tagName = $this->normalizeName($tagName);
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getTagName()
	{
		return $this->tagName;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	protected function normalizeName($name)
	{
		return strtolower($name);
	}	
	
	abstract function toHtml();
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->toHtml();
	}
}
