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

namespace Supra\Package\Cms\Pages\Finder\Organizer;

use Supra\Package\Cms\Uri\Path;

class PageLocalizationLevelOrganizer extends AbstractResultOrganizer
{
	public function organize($results)
	{
		$results = $this->prepareTree($results);
		$tree = $this->buildTree($results);

		return $tree;
	}

	protected function buildTree($results, $depth = 1)
	{
		$localizations = array();
		foreach ($results as $record) {
			if ( ! empty($record['children'])) {
				$depth ++;
				$record['children'] = $this->buildTree($record['children'], $depth);
				$depth --;
			}

			$localizations[] = $record;
		}

		$iterator = new Iterator\RecursiveLocalizationIterator($localizations);
		$iterator->setDepth($depth);

		return $iterator;
	}

	/**
	 * Prepares tree as array 
	 * @param array $results
	 * @return array 
	 */
	protected function prepareTree($results)
	{
		$hasRoot = false;
		$map = array();

        // prepares array $path => $localization
		foreach ($results as $localization) {
			/* @var $localization \Supra\Package\Cms\Entity\PageLocalization */

			$path = $localization->getPathEntity();

			if (empty($path)) {
				continue;
			}

			$isActive = $path->isActive() && $localization->isActive();

			if ($isActive) {
				$map[$localization->getFullPath(Path::FORMAT_NO_DELIMITERS)] = $localization;
			}
		}

		// grouping pages by path. Building array tree
		$output = array(
			'children' => array(),
		);

		foreach ($map as $path => $localization) {

			$path = trim($path, '/');

			if (empty($path)) {
				$hasRoot = true;
			}

			$pathParts = explode('/', $path);

			if ($hasRoot && ! empty($path)) {
				array_unshift($pathParts, '');
			}

			$outputReference = &$output;

			while ( ! empty($pathParts)) {

				$part = $pathParts[0];

				if (isset($outputReference['children'][$part])) {
					$outputReference = &$outputReference['children'][$part];
					array_shift($pathParts);
				} else {
					break;
				}
			}

			if (empty($pathParts)) {
				throw new \LogicException("Duplicate element under path $path");
			}
			
			$pathRemainder = implode('/', $pathParts);

			$outputReference['children'][$pathRemainder] = array(
				'localization' => $localization,
				'children' => array(),
			);
		}

		return $output['children'];
	}

}