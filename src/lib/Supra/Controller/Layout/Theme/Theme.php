<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Pages\Entity\ThemeParameterValue;
use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;
use Supra\Request\HttpRequest;
use Supra\Controller\Pages\ThemePreviewPreFilterController;

class Theme implements ThemeInterface
{

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var boolean
	 */
	protected $enabled;

	/**
	 * @var array
	 */
	protected $activeParameters = null;

	/**
	 * @var array
	 */
	protected $previewParameters = null;

	/**
	 * @var array
	 */
	protected $parameterConfigurations = array();

	/**
	 * @var array
	 */
	protected $currentParameters = array();

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getLayoutRoot()
	{
		return SUPRA_THEMES_PATH . $this->name;
	}

	public function isEnabled()
	{
		return $this->enabled;
	}

	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	public function getActiveParameters()
	{
		return $this->activeParameters;
	}

	public function setActiveParameters($activeParameters)
	{
		$this->activeParameters = $activeParameters;
	}

	public function getPreviewParameters()
	{
		return $this->previewParameters;
	}

	public function setPreviewParameters($previewParameters)
	{
		$this->previewParameters = $previewParameters;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	public function makePreviewParametersActive()
	{
		$this->activeParameters = array();

		foreach ($this->previewParameters as $previewParameter) {
			/* @var $previewParameter ThemeParameterValue */

			$activeParameter = clone $previewParameter;

			$activeParameter->setSetName(ThemeParameterValue::SET_NAME_ACTIVE);

			$this->activeParameters[$activeParameter->getName()] = $activeParameter;
		}
	}

	/**
	 * @return array
	 */
	public function getParameterConfigurations()
	{
		return $this->parameterConfigurations;
	}

	/**
	 * @param array $parameterConfigurations 
	 */
	public function setParameterConfigurations($parameterConfigurations)
	{
		$this->parameterConfigurations = $parameterConfigurations;
	}

	/**
	 * @param array $parameters
	 * @return array 
	 */
	protected function getParameterValues($parameters)
	{
		$values = array();

		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterValue */
			$value = $parameter->getValue();

			if (empty($value)) {
				$value = $parameter->getDefaultValue();
			}

			$values[$parameter->getName()] = $value;
		}

		$values['name'] = $this->getName();

		return $values;
	}

	/**
	 * @return array
	 */
	protected function getCurrentParameters()
	{
		if (empty($this->currentParameters)) {
			$this->currentParameters = $this->activeParameters;
		}

		return $this->currentParameters;
	}

	/**
	 * @return array
	 */
	public function getCurrentParameterValues()
	{
		$currentParameters = $this->getCurrentParameters();

		return $this->getParameterValues($currentParameters);
	}

	/**
	 * 
	 */
	public function setPreviewParametersAsCurrentParameters()
	{
		$this->currentParameters = $this->previewParameters;
	}

	/**
	 * @param HttpRequest $request 
	 */
	public function setPreviewThemeCookie(HttpRequest $request)
	{
		$cookies = $request->getCookies();

		$cookies[ThemePreviewPreFilterController::COOKIE_NAME_PREVIEW_THEME_NAME] = $this->getName();

		$request->setCookies($cookies);
	}

	public function removePreviewThemeCookie(HttpRequest $request)
	{
		$cookies = $request->getCookies();

		unset($cookies[ThemePreviewPreFilterController::COOKIE_NAME_PREVIEW_THEME_NAME]);

		$request->setCookies($cookies);
	}

}