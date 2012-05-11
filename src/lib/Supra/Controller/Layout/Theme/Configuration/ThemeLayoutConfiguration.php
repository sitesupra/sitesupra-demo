<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Layout\Theme\Theme;
use Supra\Configuration\Exception;
use Supra\Controller\Pages\Entity\ThemeLayout;
use Supra\Controller\Pages\Entity\ThemeLayoutPlaceholder;
use Supra\Controller\Layout\Processor\TwigProcessor;
use Doctrine\Common\Collections\ArrayCollection;

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
	 * @return ThemeLayout
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	function configure()
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

		$placeholderNamesInTemplate = $twigProcessor->getPlaces($this->filename);

		$namesToRemove = array_diff($currentPlaceholderNames, $placeholderNamesInTemplate);
		foreach ($namesToRemove as $nameToRemove) {
			$layout->removePlaceholder($placeholders[$nameToRemove]);
		}

		$namesToAdd = array_diff($placeholderNamesInTemplate, $currentPlaceholderNames);
		foreach ($namesToAdd as $nameToAdd) {

			$placeholder = new ThemeLayoutPlaceholder();
			$placeholder->setName($nameToAdd);

			$layout->addPlaceholder($placeholder);
		}
	}

}
