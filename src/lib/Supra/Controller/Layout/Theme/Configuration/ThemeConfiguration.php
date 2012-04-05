<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\Layout\Theme\Theme;
use Supra\Configuration\Exception;

class ThemeConfiguration implements ConfigurationInterface
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
	public $variants;

	/**
	 * @var Theme
	 */
	protected $theme;

	public function configure()
	{
		$theme = new Theme();

		$theme->setName($this->name);
		$theme->setDescription($this->description);
		$theme->setEnabled($this->enabled);

		$parameterConfigurations = array();

		if ( ! empty($this->parameters)) {

			foreach ($this->parameters as $parameterConfiguration) {
				/* @var $parameterConfiguration ThemeParameterConfiguration */

				$name = $parameterConfiguration->name;
				$parameterConfigurations[$name] = $parameterConfiguration;
			}
		}

		$theme->setParameterConfigurations($parameterConfigurations);

		if ( ! empty($this->variants)) {

			foreach ($this->variants as $variant) {
				/* @var $variant ThemeVariantConfiguration */

				foreach (array_keys($variant->parameterValues) as $name) {
					if ( ! isset($parameterConfigurations[$name])) {
						throw new Exception\RuntimeException('Parameter variant "' . $variant->name . '" of theme "' . $theme->getName() . '" refers to parameter "' . $name . '" that is not present in theme parameter configuration.');
					}
				}

				$theme->addVariant($variant->name, $variant->parameterValues);
			}
		}

		$this->theme = $theme;
	}

	public function getTheme()
	{
		return $this->theme;
	}

}
