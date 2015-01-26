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

namespace Supra\Package\Cms\Pages\Request;

use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;

/**
 * Request object for edit mode requests
 */
class PageRequestEdit extends PageRequest
{
	public function getPlaceHolderSet()
	{
		if ($this->placeHolderSet) {
			return $this->placeHolderSet;
		}

		parent::getPlaceHolderSet();

		$this->createMissingPlaceHolders();

		return $this->placeHolderSet;
	}

	/**
	 * @return void
	 * @throws \LogicException
	 */
	private function createMissingPlaceHolders()
	{
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();

		if (empty($layoutPlaceHolderNames)) {
			return null;
		}

		if ($this->placeHolderSet === null) {
			throw new \LogicException('Expecting place holder set to be created already.');
		}

		$entityManager = $this->getEntityManager();
		$localization = $this->getLocalization();

		$finalPlaceHolders = $this->placeHolderSet->getFinalPlaceHolders();
		$parentPlaceHolders = $this->placeHolderSet->getParentPlaceHolders();

		$isDirty = false;

		foreach ($layoutPlaceHolderNames as $name) {

			if ($finalPlaceHolders->offsetExists($name)) {
				continue;
			}

			$placeHolder = null;
			$parentPlaceHolder = null;

			// Check if page doesn't have it already set locally
			$knownPlaceHolders = $localization->getPlaceHolders();

			if ($knownPlaceHolders->offsetExists($name)) {
				$placeHolder = $knownPlaceHolders->offsetGet($name);
			} else {
				
				$parentPlaceHolder = $parentPlaceHolders->getLastByName($name);

				// Creates with unlocked blocks copy
				$placeHolder = PlaceHolder::factory($localization, $name, $parentPlaceHolder);
				$placeHolder->setMaster($localization);

				$entityManager->persist($placeHolder);

				$isDirty = true;
			}

			$this->placeHolderSet->append($placeHolder);
		}

		if ($isDirty) {
			$this->getEntityManager()->flush();
		}
	}
}
