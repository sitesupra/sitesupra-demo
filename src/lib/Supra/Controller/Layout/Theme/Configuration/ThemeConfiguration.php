<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\Entity\Theme\Theme;
use Supra\Configuration\Exception;
use Supra\Configuration\Loader\LoaderRequestingConfigurationInterface;
use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationLoader;
use Supra\Controller\Layout\Theme\ThemeProvider;
use Supra\Controller\Pages\Entity\Theme\ThemeParameterSet;
use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Layout\Theme\Configuration\Parameter\GroupConfiguration;

class ThemeConfiguration extends ThemeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $title = '';

	/**
	 * @var string
	 */
	public $description = '';

	/**
	 * @var boolean
	 */
	public $enabled = true;

	/**
	 * @var string
	 */
	public $parameters;

	/**
	 * @var array
	 */
	public $parameterSets;

	/**
	 * @var string
	 */
	public $urlBase;
	
	/**
	 * @var string
	 */
	public $previewUrl;

	/**
	 * @var array
	 */
	public $tags;

	/**
	 * @var string
	 */
	public $smallListImage;

	/**
	 * @var string
	 */
	public $largeListImage;

	/**
	 * @var array
	 */
	public $overviewImages;

	/**
	 * @var string
	 */
	public $author;

	/**
	 * @var string
	 */
	public $category;

	/**
	 * @var array
	 */
	public $groupLayouts;
	
	/**
	 * @var array
	 */
	public $layouts;
	
	/**
	 * @var array
	 */
	protected $storableParameters;
	
	/**
	 * @var array
	 */
	protected $iconConfiguration;
	/**
	 * 
	 */
	protected function fetchConfiguration()
	{
		$theme = $this->getTheme();
		$theme->setConfiguration($this);
		
		$this->loader->setThemeConfiguration($this);
	}

	/**
	 * 
	 */
	protected function readConfiguration()
	{
		$this->loader->setThemeConfiguration($this);
		
		$theme = $this->getTheme();

		$theme->setTitle($this->title);
		$theme->setDescription($this->description);
		$theme->setEnabled((boolean) $this->enabled);

		if ( ! empty($this->urlBase)) {
			$theme->setUrlBase($this->urlBase);
		}

		$this->processParameters();

		$this->processPlaceholderGroupLayouts();
		$this->processLayouts();

		$this->processParameterSets();
	}
	
	/**
	 * @param array $configurations
	 */
	private function collectStorableParameterConfiguration($configurations)
	{
		if (empty($configurations)) {
			return array();
		}
		
		$storableParameters = array();
		
		foreach ($configurations as $configuration) {	
			if ($configuration instanceof ThemeParameterConfigurationAbstraction) {
				$storableParameters[$configuration->id] = $configuration;
			}
			else if ($configuration instanceof GroupConfiguration) {
				$subParameters = $this->collectStorableParameterConfiguration($configuration->parameters);
				$storableParameters = $storableParameters + $subParameters;
			}
		}
		
		return $storableParameters;
	}
	
//	/**
//	 * @return array
//	 */
//	public function getFontList()
//	{
//		$list = array();
//		
//		$configurations = $this->getStorableParameterConfigurations();
//	
//		foreach ($configurations as $configuration) {
//			if ($configuration instanceof Parameter\FontParameterConfiguration) {
//				foreach($configuration->values as $value) {
//					$list[$value['id']] = $value;
//				}
//			}
//		}
//		
//		return $list;
//	}

	/**
	 * @param string $name
	 * @return ThemeStorableParameterConfiguration
	 */
	public function getConfigurationForParameter($name)
	{
		$configurations = $this->getStorableParameterConfigurations();
		
		if (isset($configurations[$name])) {
			return $configurations[$name];
		}
		
		return null;
	}
	
	public function getStorableParameterConfigurations()
	{
		if ($this->storableParameters === null) {
			$this->storableParameters = $this->collectStorableParameterConfiguration($this->parameters);
		}
		
		return $this->storableParameters;
	}
	
	/**
	 * 
	 */
	protected function processParameters()
	{
		$theme = $this->getTheme();

		$parametersBefore = $theme->getParameters();
		$parameterNamesBefore = $parametersBefore->getKeys();

		$parametersAfter = new ArrayCollection();
		
		$parameterConfigurations = $this->getStorableParameterConfigurations();
		
		if ( ! empty($parameterConfigurations)) {

			foreach ($parameterConfigurations as $parameterConfiguration) {
				/* @var $parameterConfiguration ThemeParameterConfigurationAbstraction */

				$parameter = $parameterConfiguration->getParameter();

				$parametersAfter[$parameter->getName()] = $parameter;
			}
		}

		$activeParameterSet = $theme->getActiveParameterSet();

		if (empty($activeParameterSet) || $activeParameterSet->getType() == ThemeParameterSet::TYPE_PRESET) {
			$theme->setActiveParameterSet(null);
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

	/**
	 * 
	 */
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

			if ($parameterSetsBefore[$nameToRemove]->getType() != ThemeParameterSet::TYPE_PRESET) {
				continue;
			}

			$theme->removeParameterSet($parameterSetsBefore[$nameToRemove]);
		}

		$namesToAdd = array_diff($parameterSetNamesAfter, $parameterSetNamesBefore);
		foreach ($namesToAdd as $nameToAdd) {
			$theme->addParameterSet($parameterSetsAfter[$nameToAdd]);
		}

		// Add undefined parameter values to sets, using default values from default parameter set (it must exist or this will fail).

		if ($theme->getParameterSets()->containsKey(Theme::DEFAULT_PARAMETER_SET_NAME)) {

			$parameters = $theme->getParameters();

			$parameterSets = $theme->getParameterSets();

			$defaultParameterSet = $theme->getDefaultParameterSet();

			foreach ($parameterSets as $parameterSet) {
				/* @var $parameterSet ThemeParameterSet */

				foreach ($parameters as $parameter) {
					/* @var $parameter \Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction */

					$parameterSetValues = $parameterSet->getValues();

					if (empty($parameterSetValues[$parameter->getName()])) {

						$parameterValue = $parameterSet->addNewValueForParameter($parameter);

						/* @var $parameterValueFromDefaultParameterSet \Supra\Controller\Pages\Entity\Theme\ThemeParameterValue */
						$parameterValueFromDefaultParameterSet = $defaultParameterSet->getValues()->get($parameter->getName());

						$parameterValue->setValue($parameterValueFromDefaultParameterSet->getValue());
					}
				}
			}
		}
	}

	/**
	 * 
	 */
	protected function processPlaceholderGroupLayouts()
	{
		$theme = $this->getTheme();
		$layoutsBefore = $theme->getPlaceholderGroupLayouts();
		
		$layoutNamesBefore = $layoutsBefore->getKeys();
		
		$layoutNamesNow = array();

		if ( ! empty($this->groupLayouts)) {
			foreach ($this->groupLayouts as $layoutConfiguration) {
				/* @var $layoutConfiguration \Supra\Controller\Layout\Theme\Configuration\ThemePlaceholderGroupLayoutConfiguration */

				$layout = $layoutConfiguration->getLayout();
				$layoutNamesNow[] = $layout->getName();
			}
		}

		$layoutNamesToRemove = array_diff($layoutNamesBefore, $layoutNamesNow);
		foreach($layoutNamesToRemove as $nameToRemove) {
			$layout = $layoutsBefore->get($nameToRemove);
			$theme->removePlaceholderGroupLayout($layout);
		}
	}
	
	/**
	 * 
	 */
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
				
				$layoutConfiguration->processPlaceholders();
				
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

	/**
	 * @param string $name
	 * @return ThemeLayoutConfiguration
	 * @throws \InvalidArgumentException
	 */
	public function getLayoutConfiguration($name)
	{
		if (! $this->hasLayoutConfiguration($name)) {
			throw new \InvalidArgumentException(
					"Missing layout configuration for [{$name}]."
			);
		}

		return $this->layouts[$name];
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasLayoutConfiguration($name)
	{
		return isset($this->layouts[$name]);
	}

	/**
	 * @param ThemeIconSetConfiguration $configuration
	 */
	public function setIconConfiguration($configuration)
	{
		$this->iconConfiguration = $configuration;
	}
	
	/**
	 * @return ThemeIconSetConfiguration
	 */
	public function getIconConfiguration()
	{
		return $this->iconConfiguration;
	}
	
	/**
	 * @return array
	 */
	public function getParameterConfigurationDataArray()
	{
		$parametersData = array();
		
		foreach ($this->parameters as $parameterConfiguration) {
			$parametersData[] = $this->convertConfigurationToArray($parameterConfiguration);
		}
		
		return $parametersData;
	}
	
	/**
	 * 
	 * @param mixed $configuration
	 */
	private function convertConfigurationToArray(ThemeConfigurationAbstraction $configuration)
	{
		$data = array();
		
		if ($configuration instanceof Parameter\GroupConfiguration) {

			$data = array(
				'id' => $configuration->id,
				'labelButton' => $configuration->label,
				'buttonStyle' => $configuration->buttonStyle,
				'type' => 'Group',
				'visibleFor' => $configuration->visibleFor,
				'icon' => $configuration->icon,
				'properties' => array(),
				'highlightElementSelector' => $configuration->highlightElement
			);
			
			foreach ($configuration->parameters as $parameterConfiguration) {
				$data['properties'][] = $this->convertConfigurationToArray($parameterConfiguration);
			}
		} 
		else if ($configuration instanceof Parameter\ParameterPresetGroupConfiguration) {
			
			$data = array(
				'id' => $configuration->id,
				'label' => $configuration->label,
				'type' => 'Patch',
				'values' => array(),
			);
			
			foreach ($configuration->presets as $presetConfiguration) {
				$data['values'][] = $this->convertConfigurationToArray($presetConfiguration);
			}
					
		} else {
			$data = $configuration->toArray()
					+ $configuration->getAdditionalProperties();
		}
		
		return $data;
	}
}
