<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Controller\Pages\Entity\Theme\ThemeLayout;
use Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholder;
use Supra\Controller\Layout\Processor\TwigProcessor;
use Supra\Controller\Pages\Entity\Theme;

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
	}
	
	/**
	 * 
	 */
	public function processPlaceholders()
	{
		$layout = $this->getLayout();

		$placeholders = $layout->getPlaceholders();
		$namesBefore = $placeholders->getKeys();

		$theme = $this->getTheme();
		$rootDir = $theme->getRootDir();

		$twigProcessor = new TwigProcessor();
		$twigProcessor->setLayoutDir($rootDir);
		$twigProcessor->setTheme($theme);

		$namesInLayout = $twigProcessor->getPlaces($this->filename);

		$namesNow = array();
		foreach ($namesInLayout as $nameInLayout) {
			if ( ! in_array($nameInLayout, $namesBefore)) {
				$placeholder = new ThemeLayoutPlaceholder($nameInLayout);
				$layout->addPlaceholder($placeholder);
			} else {
				$placeholder = $placeholders->get($nameInLayout);
			}
			
			$namesNow[] = $nameInLayout;
		}
		
		$groupsBefore = $layout->getPlaceholderGroups();
		$groupNamesBefore = $groupsBefore->getKeys();
		
		$groupNamesNow = array();
		
		$groupNames = $twigProcessor->getPlaceGroups($this->filename);
		
		if ( ! empty($groupNames)) {
			
			$groupLayouts = $theme->getPlaceholderGroupLayouts();
			
			$layoutPlaces = array();
			foreach($groupLayouts as $groupLayout) {
				/* @var $groupLayout \Supra\Controller\Pages\Entity\Theme\ThemePlaceholderGroupLayout */
				$layoutFile = $groupLayout->getFileName();
				$places = $twigProcessor->getPlaces($layoutFile);
				
				foreach ($places as $place) {
					if ( ! in_array($place, $layoutPlaces)) {
						$layoutPlaces[] = $place;
					}
				}
			}
			
			if (empty($layoutPlaces)) {
				\Log::warn('PlaceholderGroup layouts contains no places, check layout files');
			}
			
			foreach ($groupNames as $groupName) {
				$name = null;
				$title = null;
				
				if (($pos = mb_strpos($groupName, '|')) !== false) {
					$name = trim(mb_substr($groupName, 0, $pos));
					$title = trim(mb_substr($groupName, $pos+1));
				} else {
					$name = $groupName;
				}
				
				$title = (empty($title) ? ucfirst($name) : $title);
				
				if ( ! in_array($name, $groupNamesBefore)) {
					$group = new Theme\ThemeLayoutPlaceholderGroup($name);
					$layout->addPlaceholderGroup($group);
				} else {
					$group = $groupsBefore->get($name);
				}
				
				$groupNamesNow[] = $name;
				
				$group->setTitle($title);
				
				foreach ($layoutPlaces as $placeName) {
					$groupPlaceholderName = $name . '_' . $placeName;
					
					if ( ! in_array($groupPlaceholderName, $namesBefore)) {
						$placeholder = new ThemeLayoutPlaceholder($groupPlaceholderName);
						$layout->addPlaceholder($placeholder);
					} else {
						$placeholder = $placeholders->get($groupPlaceholderName);
					}
					
					$group->addPlaceholder($placeholder);

					$namesNow[] = $groupPlaceholderName;
				}
			}
		}
		
		$groupNamesToRemove = array_diff($groupNamesBefore, $groupNamesNow);
		foreach ($groupNamesToRemove as $name) {
			$group = $groupsBefore->get($name);
			$layout->removePlaceholderGroup($group);
		}
		
		$namesToRemove = array_diff($namesBefore, $namesNow);
		foreach ($namesToRemove as $nameToRemove) {
			$placeholder = $placeholders->get($nameToRemove);
			$layout->removePlaceholder($placeholder);

			$group = $placeholder->getGroup();
			if ( ! is_null($group)) {
				$group->removePlaceholder($placeholder);
			}
		}
	}

}
