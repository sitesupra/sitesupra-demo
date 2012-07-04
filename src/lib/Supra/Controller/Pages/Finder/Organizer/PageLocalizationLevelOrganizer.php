<?php

namespace Supra\Controller\Pages\Finder\Organizer;

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
		$map = array();

        // prepares array $path => $localization
		foreach ($results as $localization) {

			$path = $localization->getPathEntity();

			if (empty($path)) {
				continue;
			}

			$visibleInSitemap = $path->isVisibleInSitemap() && $localization->isVisibleInSitemap();
			$isActive = $path->isActive() && $localization->isActive();

			if ($visibleInSitemap && $isActive) {
				$map[$localization->getFullPath(\Supra\Uri\Path::FORMAT_NO_DELIMITERS)] = $localization;
			}
		}

		// grouping pages by path. Building array tree
		$output = array();
		$root = false;
		foreach ($map as $path => $localization) {
			$pathParts = explode('/', $path);
			$partsCount = count($pathParts);
			if ($partsCount <= 1 && ! $root) {
				if (empty($path)) {
					$root = true;
					$path = '/';
				}

				$output[$path] = array(
					'localization' => $localization,
					'children' => array(),
				);

				continue;
			}


			if ($root) {
				array_unshift($pathParts, '/');
			}

			$outputReference = &$output;
			$foundPart = false;
			foreach ($pathParts as $part) {
				if ($foundPart) {
					if (isset($outputReference['children'][$part])) {
						$outputReference = &$outputReference['children'][$part];
						continue;
					}
				} else {
					if (isset($outputReference[$part])) {
						$outputReference = &$outputReference[$part];
						$foundPart = true;
						continue;
					}
				}

				$outputReference['children'][$part] = array(
					'localization' => $localization,
					'children' => array(),
				);
			}
		}

		return $output;
	}

}