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

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;

/**
 * Set of place holders
 */
class PlaceHolderSet extends AbstractSet
{
	/**
	 * @var Localization
	 */
	private $localization;
	
	/**
	 * @var PlaceHolderSet 
	 */
	private $finalPlaceHolderSet;
	
	/**
	 * @var PlaceHolderSet
	 */
	private $parentPlaceHolderSet;

	/**
	 * @param Localization $localization
	 */
	public function __construct(Localization $localization = null)
	{
		$this->localization = $localization;
		
		if (isset($localization)) {
			$this->finalPlaceHolderSet = new PlaceHolderSet();
			$this->parentPlaceHolderSet = new PlaceHolderSet();
		}
	}
	
	/**
	 * Use only append to fill this object, must start with top elements
	 * @param PlaceHolder $placeHolder
	 */
	public function append($placeHolder)
	{
		if ( ! $placeHolder instanceof PlaceHolder) {
			throw new \LogicException(__METHOD__ . " accepts PlaceHolder arguments only");
		}
		
		if (isset($this->localization)) {
			
			$placeHolderName = $placeHolder->getName();
			
			// Add to final in cases when it's the page place or locked one
			if ($placeHolder->getLocked() || $placeHolder->getMaster()->equals($this->localization)) {
				
				// Ignore if already set locked place with higher hierarchy level
				if ($this->finalPlaceHolderSet->offsetExists($placeHolderName)) {
					return;
				}
				
				$this->finalPlaceHolderSet->offsetSet($placeHolderName, $placeHolder);
			} else {
				
				// collect not matched template place holders to search for locked blocks
				$this->parentPlaceHolderSet->append($placeHolder);
			}
		}
		
		parent::append($placeHolder);
	}
	
	/**
	 * The list of final (locked or belongs to the final master) placeHolders.
	 * The block list will be taken from these placeHolders.
	 * @return PlaceHolderSet
	 */
	public function getFinalPlaceHolders()
	{
		return $this->finalPlaceHolderSet;
	}
	
	/**
	 * The list of placeHolders which are parents of final placeHolders.
	 * The locked blocks will be searched within these placeHolders.
	 * @return PlaceHolderSet
	 */
	public function getParentPlaceHolders()
	{
		return $this->parentPlaceHolderSet;
	}
	
	/**
	 * Loads the last place holder in the set by the name provided
	 * @param string $name
	 * @return PlaceHolder
	 */
	public function getLastByName($name)
	{
		$placeHolder = null;
		
		/* @var $placeHolder PlaceHolder */
		foreach ($this as $placeHolderTest) {
			if ($placeHolderTest->getName() == $name) {
				$placeHolder = $placeHolderTest;
			}
		}
		
		return $placeHolder;
	}
}
