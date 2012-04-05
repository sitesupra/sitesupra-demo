<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Pages\Entity\ThemeParameterValue;
use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;
use Supra\Request\HttpRequest;
use Supra\Controller\Pages\ThemePreviewPreFilterController;
use Supra\Less\SupraLessC;

class Theme implements ThemeInterface
{

	const FILE_NAME_CSS_TEMPLATE = 'theme.css.twig';

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

	/**
	 * @var string
	 */
	protected $assetsPath;

	/**
	 * @var string
	 */
	protected $currentParameterSetName;

	/**
	 * @var array
	 */
	protected $variants;

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

	/**
	 * @param string $description 
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getAssetsPath()
	{
		return $this->assetsPath;
	}

	/**
	 * @param string $assetsPath 
	 */
	public function setAssetsPath($assetsPath)
	{
		$this->assetsPath = $assetsPath;
	}

	public function getCurrentParameterSetName()
	{
		return $this->currentParameterSetName;
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
	 * @param string $variantName
	 * @return array
	 */
	public function getVariant($variantName)
	{
		if (empty($this->variants[$variantName])) {
			throw new Exception\RuntimeException('Theme "' . $this->getName() . '" does not have parameter variant "' . $variantName . '" defined.');
		}

		$variant = $this->variants[$variantName];

		return $variant;
	}

	/**
	 * @param string $variantName
	 * @param array $parameterValues 
	 */
	public function addVariant($variantName, $parameterValues)
	{
		$this->variants[$variantName] = $parameterValues;
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
	protected function getParameterValues($parameters, $addQuotes = false)
	{
		$values = array();

		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterValue */

			$value = $parameter->getValue();

			if (empty($value)) {
				$value = $parameter->getDefaultValue();
			}

			// FIXME - it kinda makes sense to make this type of transformation 
			// a responsibility of parameter value itself.
			if ($parameter->getConfiguration()->type == 'url') {
				$value = "'" . $value . "'";
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
			$this->currentParameterSetName = ThemeParameterValue::SET_NAME_ACTIVE;
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

		$parameterValues = $this->getParameterValues($currentParameters);

		$parameterValues['cssUrl'] = $this->getCurrentCssUrl();

		return $parameterValues;
	}

	/**
	 * 
	 */
	public function setPreviewParametersAsCurrentParameters()
	{
		$this->currentParameterSetName = ThemeParameterValue::SET_NAME_PREVIEW;
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

	/**
	 * @param HttpRequest $request 
	 */
	public function removePreviewThemeCookie(HttpRequest $request)
	{
		$cookies = $request->getCookies();

		unset($cookies[ThemePreviewPreFilterController::COOKIE_NAME_PREVIEW_THEME_NAME]);

		$request->setCookies($cookies);
	}

	/**
	 * @param string $variantName 
	 */
	public function useVariantForPreviewParmeters($variantName)
	{
		$parameterValues = $this->getVariant($variantName);

		$previewParameters = $this->getPreviewParameters();

		foreach ($parameterValues as $name => $value) {
			$previewParameters[$name]->setValue($value);
		}
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	protected function getCssContent($parameters)
	{
		$loader = new \Twig_Loader_Filesystem(array($this->getLayoutRoot()));

		$twigResponse = new \Supra\Response\TwigResponse();

		$twigResponse->setLoader($loader);

		$parameterValues = $this->getParameterValues($parameters);

		foreach ($parameterValues as $name => $value) {
			$twigResponse->assign($name, $value);
		}

		$twigResponse->outputTemplate(self::FILE_NAME_CSS_TEMPLATE);

		return $twigResponse->getOutputString();
	}

	/**
	 * @param string $parameterSetName
	 * @return array
	 */
	protected function getParameters($parameterSetName)
	{
		if ($parameterSetName == ThemeParameterValue::SET_NAME_ACTIVE) {
			return $this->getActiveParameters();
		} else {
			return $this->getPreviewParameters();
		}
	}

	public function generateCssFiles()
	{
		$this->generateCssFileFromLess(ThemeParameterValue::SET_NAME_ACTIVE);
		$this->generateCssFileFromLess(ThemeParameterValue::SET_NAME_PREVIEW);
	}

	/**
	 * @param string $parameterSetName 
	 */
	protected function generateCssFileFromLess($parameterSetName)
	{
		$parameters = $this->getParameters($parameterSetName);

		$lessc = new SupraLessC($this->getLayoutRoot() . DIRECTORY_SEPARATOR . 'theme.less');

		$lessc->setRootDir($this->getLayoutRoot());

		$values = $this->getParameterValues($parameters, true);

		//$cssContent = $lessc->parse(null, array('headerBackgroundUrl' => 'url(/trololo.gif)'));
		$cssContent = $lessc->parse(null, $values);

		$this->writeToCssFile($parameterSetName, $cssContent);
	}

	protected function writeToCssFile($parameterSetName, $content)
	{
		$cssFilename = $this->getCssFilename($parameterSetName);

		$result = file_put_contents($cssFilename, $content);

		if ($result === false) {
			throw new Execption\RuntimeException('Could not write theme CSS file to "' . $cssFilename . '".');
		}
	}

	/**
	 * @param string $parameterSetName
	 * @throws Execption\RuntimeException 
	 */
	protected function generateCssFileFromTemplate($parameterSetName)
	{
		$parameters = $this->getParameters($parameterSetName);

		$cssContent = $this->getCssContent($parameters);

		$this->writeToCssFile($parameterSetName, $cssContent);
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getCssBasename($parameterSetName)
	{
		return $this->getName() . '_' . $parameterSetName . '.css';
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getCssFilename($parameterSetName)
	{
		$assetsPath = $this->getAssetsPath();

		return SUPRA_WEBROOT_PATH . DIRECTORY_SEPARATOR . $assetsPath . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . $this->getCssBasename($parameterSetName);
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getCssUrl($parameterSetName)
	{
		return $this->getAssetsPath() . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . $this->getCssBasename($parameterSetName);
	}

	/**
	 * @return string
	 */
	protected function getCurrentCssUrl()
	{
		return $this->getCssUrl($this->getCurrentParameterSetName());
	}

}