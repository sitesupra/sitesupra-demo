<?php

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