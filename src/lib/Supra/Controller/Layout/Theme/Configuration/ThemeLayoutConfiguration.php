<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Layout\Theme\Theme;
use Supra\Configuration\Exception;
use Supra\Controller\Pages\Entity\Theme\ThemeLayout;
use Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholder;
use Supra\Controller\Layout\Processor\TwigProcessor;
use Doctrine\Common\Collections\ArrayCollection;

use Supra\Controller\Pages\Entity\Theme as ThemeEntity;

class ThemeLayoutConfiguration extends ThemeConfigurationAbstraction
{

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $filename;

	/**
	 * @var ThemeLayout
	 */
	protected $layout;
	
	/**
	 * @var array
	 */
	public $placeHolderContainers = array();

	
	/**
	 * @return ThemeLayout
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * 
	 */
	function readConfiguration()
	{
		$theme = $this->getTheme();

		$layouts = $theme->getLayouts();

		$layout = null;

		if (empty($layouts[$this->name])) {
			$layout = new ThemeLayout();
			$layout->setName($this->name);
		} else {
			$layout = $layouts[$this->name];
		}

		$layout->setTitle($this->title);

		$layout->setFilename($this->filename);

		$this->layout = $layout;

		$this->processPlaceholders();
	}

	protected function processPlaceholders()
	{
		$layout = $this->getLayout();

		$placeholders = $layout->getPlaceholders();
		$currentPlaceholderNames = $placeholders->getKeys();

		$theme = $this->getTheme();
		$rootDir = $theme->getRootDir();

		$twigProcessor = new TwigProcessor();
		$twigProcessor->setLayoutDir($rootDir);
		$twigProcessor->setTheme($theme);
		
		$placeHolderContainers = $twigProcessor->getPlaceContainers($this->filename);
		//$containersConfiguration = $theme->getPlaceholderContainerConfiguration();
		
		$themePlaceHolderSets = $theme->getPlaceholderSets();
		
		$containersPlaceHolders = array();
		$placeContainerMap = array();
		
		if ( ! empty($placeHolderContainers)) {
			
			$setsPlaces = array();
			
			foreach ($placeHolderContainers as $containerName) {
				
				foreach ($themePlaceHolderSets as $placeHolderSet) {
					
					$setLayout = $placeHolderSet->getLayoutFilename();
					$currentSetPlaces = $twigProcessor->getPlaces($setLayout);
					
					$setsPlaces = array_merge($setsPlaces, $currentSetPlaces);
					
				}
				
				foreach($setsPlaces as $placeName) {
					$finalPlaceName = $containerName . '_' . $placeName;
					if ( ! in_array($finalPlaceName, $containersPlaceHolders)) {
						$containersPlaceHolders[] = $finalPlaceName;
						$placeContainerMap[$finalPlaceName] = $containerName;
					}
				}
			}
		}

		$placeholderNamesInTemplate = $twigProcessor->getPlaces($this->filename);
		
		$placeholderNamesInTemplate = array_merge($placeholderNamesInTemplate, $containersPlaceHolders);

		$namesToRemove = array_diff($currentPlaceholderNames, $placeholderNamesInTemplate);
		foreach ($namesToRemove as $nameToRemove) {
			$layout->removePlaceholder($placeholders[$nameToRemove]);
		}

		$namesToAdd = array_diff($placeholderNamesInTemplate, $currentPlaceholderNames);
		foreach ($namesToAdd as $nameToAdd) {
			
			$containerName = null;
			if (isset($placeContainerMap[$nameToAdd])) {
				$containerName = $placeContainerMap[$nameToAdd];
			}

			$placeholder = new ThemeLayoutPlaceholder($containerName);
			$placeholder->setName($nameToAdd);

			$layout->addPlaceholder($placeholder);
		}
		
		// @FIXME
		if ( ! is_null($themePlaceHolderSets)) {
			$defaultSet = $themePlaceHolderSets->first();
			if ( ! empty($defaultSet)) {
				$defaultSetName = $defaultSet->getName();
			}

			foreach($containersPlaceHolders as $placeholderName) {
				$placeholder = $placeholders->get($placeholderName);
				$placeholder->setContainer($placeContainerMap[$placeholderName]);
				$placeholder->setDefaultSetName($defaultSetName);
			}
		}
	}

}
