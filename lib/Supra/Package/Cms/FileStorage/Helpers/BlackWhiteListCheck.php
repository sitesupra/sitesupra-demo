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

namespace Supra\Package\Cms\FileStorage\Helpers;

class BlackWhiteListCheck
{
	
	const MODE_WHITELIST = 1;
	
	const MODE_BLACKLIST = 2;
	
	/**
	 * Current mode WL/BL
	 * @var type $mode
	 */
	private $mode = self::MODE_WHITELIST;
	
	/**
	 * List of allowed/denied properties
	 * @var type array
	 */
	private $list = array();
	
	/**
	 * Sets current mode to WL/BL
	 * @param type $mode 
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}
	
	/**
	 * Adds item to list of allowed/denied properties
	 * @param type $item 
	 */
	public function addItem($item)
	{
		$this->list[] = strtolower($item);
	}
	
	/**
	 * Adds items to list of allowed/denied properties
	 * @param array $item 
	 */
	public function addItems(array $items)
	{
		foreach ($items as $item) {
			$this->list[] = strtolower($item);
		}
	}
	
	/**
	 * Checks if item is in list
	 * @param type $item
	 * @return boolean $allow
	 */
	protected function checkList($item)
	{
		$inList = in_array($item, $this->list);
		
		$allow = null;
		
		if ($this->mode == self::MODE_BLACKLIST) {
			$allow = ! $inList;
		} else {
			$allow = $inList;
		}
		
		return $allow;
	}
}