<?php

namespace Supra\Controller\Layout\Theme;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Theme\Theme;
use Supra\Controller\Pages\Entity\Theme\ThemeParameterSet;
use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;
use Supra\Controller\Pages\Entity\Theme\ThemeLayout;
use Supra\Controller\Pages\Entity\Theme\ThemeLayoutPlaceholder;
use Supra\Controller\Layout\Exception;
use Supra\Controller\Pages\Entity\TemplateLayout;
use Supra\Controller\Pages\Entity\Template;

class DefaultThemeProvider extends ThemeProviderAbstraction
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var EntityRepository
	 */
	protected $themeRepository;

	/**
	 * @var Theme
	 */
	protected $currentTheme;

	/**
	 * @var string
	 */
	protected $rootDir;

	/**
	 * @var string
	 */
	protected $urlBase;

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
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return EntityRepository
	 */
	public function getThemeRepository()
	{
		if (empty($this->themeRepository)) {

			$this->themeRepository = $this->getEntityManager()
					->getRepository(Theme::CN());
		}

		return $this->themeRepository;
	}

	/**
	 * @param type $themeRepository 
	 */
	public function setThemeRepository(EntityRepository $themeRepository)
	{
		$this->themeRepository = $themeRepository;
	}

	/**
	 * @return string
	 */
	public function getRootDir()
	{
		if (empty($this->rootDir)) {
			throw new Exception\RuntimeException('Theme provider does not have root directory configured.');
		}

		return $this->rootDir;
	}

	/**
	 * @param string $rootDir 
	 */
	public function setRootDir($rootDir)
	{
		$this->rootDir = $rootDir;
	}

	/**
	 * @return string
	 */
	public function getUrlBase()
	{
		return $this->urlBase;
	}

	/**
	 * @param string $urlBase 
	 */
	public function setUrlBase($urlBase)
	{
		$this->urlBase = $urlBase;
	}

	/**
	 * @return Theme
	 */
	public function getActiveTheme()
	{
		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);

		$themeName = $iniLoader->getValue('system', 'active_theme', 'default');

		return $this->getThemeByName($themeName);
	}

	/**
	 * @param Theme $theme 
	 */
	public function setActiveTheme(Theme $theme)
	{
		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);

		$iniLoader->setValue('system', 'active_theme', $theme->getName());
	}

	/**
	 * @return Theme
	 */
	public function getCurrentTheme()
	{
		if (empty($this->currentTheme)) {
			$this->currentTheme = $this->getActiveTheme();
		}

		return $this->currentTheme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setCurrentTheme(Theme $theme)
	{
		$this->currentTheme = $theme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function storeTheme(Theme $theme)
	{
		$em = $this->getEntityManager();

		$activeParameterSet = $theme->getActiveParameterSet();

		if ( ! empty($activeParameterSet)) {
			$em->persist($activeParameterSet);
		}

		$em->persist($theme);

		$parameterSets = $theme->getParameterSets();

		if ( ! empty($parameterSets)) {

			foreach ($parameterSets as $parameterSet) {
				/* @var $parameterSet ThemeParameterSet */

				$em->persist($parameterSet);
				$values = $parameterSet->getValues();

				foreach ($values as $value) {
					/* @var $parameter ThemeParameterValue */
					$em->persist($value);
				}
			}
		}

		$parameters = $theme->getParameters();

		if ( ! empty($parameters)) {

			foreach ($parameters as $parameter) {
				$em->persist($parameter);
			}
		}

		$layouts = $theme->getLayouts();

		if ( ! empty($layouts)) {

			foreach ($layouts as $layout) {
				/* @var $layout ThemeLayout */

				$em->persist($layout);

				$placeholders = $layout->getPlaceholders();

				foreach ($placeholders as $placeholder) {
					/* @var $placeholder ThemeLayoutPlaceholder */

					$em->persist($placeholder);
				}
			}
		}

		$em->flush();

		$theme->generateCssFiles();
	}

	/**
	 * @param string $name
	 * @return Theme
	 * @throws Exception\RuntimeException 
	 */
	public function getThemeByName($name)
	{
		$repo = $this->getThemeRepository();

		$theme = $repo->findOneBy(array('name' => $name));

		if (empty($theme)) {
			throw new Exception\RuntimeException('Theme named "' . $name . '" is not found.');
		}

		return $theme;
	}

	/**
	 * @param string $name
	 * @return Theme | null
	 */
	public function findThemeByName($name)
	{
		$repo = $this->getThemeRepository();

		$theme = $repo->findOneBy(array('name' => $name));

		return $theme;
	}

	/**
	 * @param string $themeName
	 * @return string
	 */
	public function getThemeConfigurationFilename($themeName)
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'theme.yml';
	}

	/**
	 * @param Theme $theme
	 */
	public function removeTheme(Theme $theme)
	{
		$em = $this->getEntityManager();

		$parameterSets = $theme->getParameterSets();
		
		$theme->setActiveParameterSet(null);
		$em->persist($theme);
		$em->flush();

		if ( ! empty($parameterSets)) {

			foreach ($parameterSets as $parameterSet) {
				/* @var $parameterSet ThemeParameterSet */
				
				$values = $parameterSet->getValues();

				foreach ($values as $value) {
					/* @var $parameter ThemeParameterValue */
					$parameterSet->removeValue($value);
				}

				$theme->removeParameterSet($parameterSet);
			}
		}
		$em->persist($theme);
		$em->flush();

		$parameters = $theme->getParameters();

		if ( ! empty($parameters)) {

			foreach ($parameters as $parameter) {
				$theme->removeParameter($parameter);
			}
		}
		$em->persist($theme);
		$em->flush();

		$layouts = $theme->getLayouts();

		if ( ! empty($layouts)) {

			foreach ($layouts as $layout) {
				/* @var $layout ThemeLayout */

				$placeholders = $layout->getPlaceholders();

				foreach ($placeholders as $placeholder) {
					/* @var $placeholder ThemeLayoutPlaceholder */

					$layout->removePlaceholder($placeholder);
				}

				$theme->removeLayout($layout);
			}
		}
		$em->remove($theme);
		$em->flush();
	}

	/**
	 * @param \Supra\Controller\Pages\Entity\Template $template
	 * @param string $media
	 * @return ThemeLayout
	 */
	public function getCurrentThemeLayoutForTemplate(Template $template, $media = TemplateLayout::MEDIA_SCREEN)
	{
		$currentTheme = $this->getCurrentTheme();

		$templateLayouts = $template->getTemplateLayouts();

		/* @var $templateLayout TemplateLayout */
		$templateLayout = $templateLayouts->get($media);

		$themeLayout = $currentTheme->getLayout($templateLayout->getLayoutName());

		return $themeLayout;
	}

	/**
	 * @return array
	 */
	public function getAllThemes()
	{
		$allThemes = $this->getThemeRepository()->findAll();

		return $allThemes;
	}

	/**
	 * @return Theme
	 */
	public function makeNewTheme()
	{
		return new Theme();
	}

}
