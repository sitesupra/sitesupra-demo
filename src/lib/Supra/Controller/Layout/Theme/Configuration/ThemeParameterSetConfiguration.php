<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Controller\Pages\Entity\ThemeParameterSet;
use Supra\Controller\Pages\Entity\ThemeParameter;
use Supra\Controller\Pages\Entity\ThemeParameterValue;
use Doctrine\Common\Collections\ArrayCollection;

class ThemeParameterSetConfiguration extends ThemeConfigurationAbstraction
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
	 * @var array
	 */
	public $values;

	/**
	 * @var boolean
	 */
	public $locked;

	/**
	 * @var ThemeParameterSet;
	 */
	protected $parameterSet;

	/**
	 * @return ThemeParameterSet
	 */
	public function getParameterSet()
	{
		return $this->parameterSet;
	}

	public function configure()
	{
		$theme = $this->getTheme();

		$parameterSets = $theme->getParameterSets();

		$parameterSet = null;

		if (empty($parameterSets[$this->name])) {
			$parameterSet = new ThemeParameterSet();
			$parameterSet->setName($this->name);
		} else {
			$parameterSet = $parameterSets[$this->name];
		}

		$parameterSet->setLocked(true);
		$parameterSet->setTitle($this->title);

		$this->parameterSet = $parameterSet;

		$this->processParameterValues();
	}

	protected function processParameterValues()
	{
		$parameterSet = $this->getParameterSet();

		$valuesBefore = $parameterSet->getValues();

		$valueNamesBefore = $valuesBefore->getKeys();

		$valuesAfter = new ArrayCollection();

		if ( ! empty($this->values)) {

			foreach ($this->values as $parameterName => $valueValue) {

				if (empty($valuesBefore[$parameterName])) {
					$value = new ThemeParameterValue();
					$value->setParameterName($parameterName);
				} else {
					$value = $valuesBefore[$parameterName];
				}

				$value->setValue($valueValue);
				
				$valuesAfter[$parameterName] = $value;
			}
		}
		
		$valueNamesAfter = $valuesAfter->getKeys();

		//$namesToRemove = array_diff($valueNamesBefore, $valueNamesAfter);
		//foreach ($namesToRemove as $nameToRemove) {
		//	$parameterSet->removeValue($valuesBefore[$nameToRemove]);
		//}

		$namesToAdd = array_diff($valueNamesAfter, $valueNamesBefore);
		foreach ($namesToAdd as $nameToAdd) {
			$parameterSet->addValue($valuesAfter[$nameToAdd]);
		}
	}

}
