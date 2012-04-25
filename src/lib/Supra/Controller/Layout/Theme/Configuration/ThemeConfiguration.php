<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\Entity\Theme;
use Supra\Configuration\Exception;
use Supra\Configuration\Loader\LoaderRequestingConfigurationInterface;
use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationLoader;
use Supra\Controller\Layout\Theme\ThemeProvider;
use Supra\Controller\Pages\Entity\ThemeParameterSet;
use Supra\Controller\Pages\Entity\ThemeParameter;
use Doctrine\Common\Collections\ArrayCollection;

class ThemeConfiguration extends ThemeConfigurationAbstraction
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
	public $description;

	/**
	 * @var boolean
	 */
	public $enabled;

	/**
	 * @var string
	 */
	public $parameters;

	/**
	 * @var array
	 */
	public $parameterSets;

	/**
	 * @var array
	 */
	public $layouts;

	/**
	 * 
	 */
	public function configure()
	{
		$theme = $this->getTheme();

		$theme->setTitle($this->title);
		$theme->setDescription($this->description);
		$theme->setEnabled((boolean) $this->enabled);

		$this->processParameters();

		$this->processLayouts();

		$this->processParameterSets();
	}

	protected function processParameters()
	{
		$theme = $this->getTheme();

		$parametersBefore = $theme->getParameters();
		$parameterNamesBefore = $parametersBefore->getKeys();

		$parametersAfter = new ArrayCollection();
		if ( ! empty($this->parameters)) {

			foreach ($this->parameters as $parameterConfiguration) {
				/* @var $parameterConfiguration ThemeParameterConfiguration */

				$parameter = $parameterConfiguration->getParameter();

				$parametersAfter[$parameter->getName()] = $parameter;
			}
		}

		$parameterNamesAfter = $parametersAfter->getKeys();

		$namesToRemove = array_diff($parameterNamesBefore, $parameterNamesAfter);
		foreach ($namesToRemove as $nameToRemove) {
			$theme->removeParameter($parametersBefore[$nameToRemove]);
		}

		$namesToAdd = array_diff($parameterNamesAfter, $parameterNamesBefore);
		foreach ($namesToAdd as $nameToAdd) {
			$theme->addParameter($parametersAfter[$nameToAdd]);
		}
	}

	protected function processLayouts()
	{
		$theme = $this->getTheme();

		$layoutsBefore = $theme->getLayouts();
		$layoutNamesBefore = $layoutsBefore->getKeys();

		$layoutsAfter = new ArrayCollection();
		if ( ! empty($this->layouts)) {

			foreach ($this->layouts as $layoutConfiguration) {
				/* @var $layoutConfiguration ThemeLayoutConfiguration */

				$layout = $layoutConfiguration->getLayout();

				$layoutsAfter[$layout->getName()] = $layout;
			}
		}

		$layoutNamesAfter = $layoutsAfter->getKeys();

		$namesToRemove = array_diff($layoutNamesBefore, $layoutNamesAfter);
		foreach ($namesToRemove as $nameToRemove) {
			$theme->removeLayout($layoutsBefore[$nameToRemove]);
		}

		$namesToAdd = array_diff($layoutNamesAfter, $layoutNamesBefore);
		foreach ($namesToAdd as $nameToAdd) {
			$theme->addLayout($layoutsAfter[$nameToAdd]);
		}
	}

	protected function processParameterSets()
	{
		$theme = $this->getTheme();

		$parameterSetsBefore = $theme->getParameterSets();
		$parameterSetNamesBefore = $parameterSetsBefore->getKeys();

		$parameterSetsAfter = new ArrayCollection();
		if ( ! empty($this->parameterSets)) {

			foreach ($this->parameterSets as $parameterSetConfiguration) {
				/* @var $parameterSetConfiguration ThemeParameterSetConfiguration */

				$parameterSet = $parameterSetConfiguration->getParameterSet();

				$parameterSetsAfter[$parameterSet->getName()] = $parameterSet;
			}
		}

		$parameterSetNamesAfter = $parameterSetsAfter->getKeys();

		$namesToRemove = array_diff($parameterSetNamesBefore, $parameterSetNamesAfter);
		foreach ($namesToRemove as $nameToRemove) {
			$theme->removeParameterSet($parameterSetsBefore[$nameToRemove]);
		}

		$namesToAdd = array_diff($parameterSetNamesAfter, $parameterSetNamesBefore);
		foreach ($namesToAdd as $nameToAdd) {
			$theme->addParameterSet($parameterSetsAfter[$nameToAdd]);
		}

		// Add undefined parameter values to sets, using default values fomr parameters.

		$parameters = $theme->getParameters();

		$parameterSets = $theme->getParameterSets();

		foreach ($parameterSets as $parameterSet) {
			
			foreach ($parameters as $parameter) {
				/* @var $parameter ThemeParameter */
				
				$parameterSetValues = $parameterSet->getValues();

				if (empty($parameterSetValues[$parameter->getName()])) {

					$value = $parameter->getThemeParameterValue();
					$parameterSet->addValue($value);
				}
			}
		}
	}

}
