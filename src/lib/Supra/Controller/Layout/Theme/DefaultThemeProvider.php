<?php

namespace Supra\Controller\Layout\Theme;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Layout\Exception;
use Supra\Controller\Layout\Theme\Theme;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\Controller\Pages\Entity\ThemeParameterValue;
use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfiguration;
use Supra\Request\HttpRequest;

class DefaultThemeProvider extends ThemeProviderAbstraction
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var EntityRepository
	 */
	protected $themeParameterRepository;

	/**
	 * @var array
	 */
	protected $parametersLoaded = array();

	/**
	 * @var ThemeInterface
	 */
	protected $currentTheme;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return EntityRepository
	 */
	public function getThemeParameterRepository()
	{
		if (empty($this->themeParameterRepository)) {

			$this->themeParameterRepository = $this->getEntityManager()
					->getRepository(ThemeParameterValue::CN());
		}

		return $this->themeParameterRepository;
	}

	/**
	 * @param EntityRepository $themeParameterRepository 
	 */
	public function setThemeParameterRepository($themeParameterRepository)
	{
		$this->themeParameterRepository = $themeParameterRepository;
	}

	/**
	 * @return ThemeInterface
	 */
	public function getActiveTheme()
	{
		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);

		$themeName = $iniLoader->getValue('system', 'active_theme', 'default');

		return $this->getTheme($themeName);
	}

	/**
	 * @param string $themeName
	 * @return Theme
	 */
	public function getTheme($themeName)
	{
		$theme = parent::getTheme($themeName);
		/* @var $theme Theme */

		if (is_null($theme->getPreviewParameters())) {

			$previewParameters = $this->loadParameterValues($theme, ThemeParameterValue::SET_NAME_PREVIEW);
			$theme->setPreviewParameters($previewParameters);
		}

		if (is_null($theme->getActiveParameters())) {

			$activeParameters = $this->loadParameterValues($theme, ThemeParameterValue::SET_NAME_ACTIVE);
			$theme->setActiveParameters($activeParameters);
		}

		$this->storeThemeParameters($theme);

		return $theme;
	}

	/**
	  s	 * @param Theme $theme
	 * @param string $setName
	 * @return array
	 */
	protected function loadParameterValues(Theme $theme, $setName)
	{
		$parameterConfigurations = $theme->getParameterConfigurations();

		$parameters = $this->getThemeParameterValueEntities($theme->getName(), $setName);

		foreach ($parameterConfigurations as $configuration) {
			/* @val $configuration ThemeParameterConfiguration */

			if ( ! isset($parameters[$configuration->name])) {

				$parameter = $this->makeThemeParameterValueFromConfiguration($configuration);

				$parameter->setSetName($setName);
				$parameter->setThemeName($theme->getName());

				$parameters[$configuration->name] = $parameter;
			} else {

				$parameter = $parameters[$configuration->name];

				$parameter->setDefaultValue($configuration->defaultValue);
			}
		}

		return $parameters;
	}

	/**
	 * @param ThemeParameterConfiguration $configuration
	 * @return ThemeParameterValue 
	 */
	protected function makeThemeParameterValueFromConfiguration(ThemeParameterConfiguration $configuration)
	{
		$parameter = new ThemeParameterValue();

		$parameter->setName($configuration->name);
		$parameter->setDefaultValue($configuration->defaultValue);

		return $parameter;
	}

	/**
	 * @param string $themeName
	 * @param string $setName
	 * @return array
	 */
	protected function getThemeParameterValueEntities($themeName, $setName)
	{
		$parameterRepository = $this->getThemeParameterRepository();

		$criteria = array(
			'themeName' => $themeName,
			'setName' => $setName
		);

		$parameterValueEntities = $parameterRepository->findBy($criteria);

		$parameters = array();

		foreach ($parameterValueEntities as $entity) {
			/* @var $entity ThemeParameterValue */

			$parameters[$entity->getName()] = $entity;
		}

		return $parameters;
	}

	/**
	 * @param ThemeInterface $theme
	 * @throws Exception\RuntimeException 
	 */
	public function setActiveTheme(ThemeInterface $theme)
	{
		throw new Exception\RuntimeException('Theme change not implemented.');
	}

	/**
	 * @param ThemeInterface $theme 
	 */
	public function storeThemeParameters(ThemeInterface $theme)
	{
		$em = $this->getEntityManager();

		foreach ($theme->getPreviewParameters() as $parameter) {
			/* @var $parameter ThemeParameterValue */
			$em->persist($parameter);
		}

		foreach ($theme->getActiveParameters() as $parameter) {
			/* @var $parameter ThemeParameterValue */
			$em->persist($parameter);
		}

		$em->flush();
	}

	/**
	 * @return ThemeInterface
	 */
	public function getCurrentTheme()
	{
		if (empty($this->currentTheme)) {
			$this->currentTheme = $this->getActiveTheme();
		}

		return $this->currentTheme;
	}

	/**
	 * @param ThemeInterface $theme 
	 */
	public function setCurrentTheme(ThemeInterface $theme)
	{
		$this->currentTheme = $theme;
	}

}
