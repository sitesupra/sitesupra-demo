<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Pages\Entity\ThemeParameter;

class ThemeParameterConfiguration extends ThemeConfigurationAbstraction
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
	public $defaultValue = 'none';

	/**
	 * @var string
	 */
	public $type = 'custom';

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var boolean
	 */
	public $locked = false;

	/**
	 * @var boolean
	 */
	public $enabled = true;

	/**
	 * @var boolean
	 */
	public $visible = true;

	/**
	 * @var ThemeParameter
	 */
	protected $parameter;

	/**
	 * @return ThemeParameter
	 */
	public function getParameter()
	{
		return $this->parameter;
	}

	public function configure()
	{
		$theme = $this->getTheme();

		$parameters = $theme->getParameters();

		$parameter = null;

		if (empty($parameters[$this->name])) {

			$parameter = new ThemeParameter();
			$parameter->setName($this->name);
		} else {
			$parameter = $parameters[$this->name];
		}

		$parameter->setTitle($this->title);

		$parameter->setDefaultValue($this->defaultValue);

		$parameter->setType($this->type);

		$this->parameter = $parameter;
	}

	/**
	 * 
	 */
	public function makeDesignData(&$designData)
	{
	
	}
	
	public function makeCurrentParameterValues(&$currentValues)
	{
		$currentValues[$this->name] = $this->defaultValue;
	}

}
