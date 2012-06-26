<?php

use Supra\Upgrade\Script\UpgradeScriptAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\TemplateLayout;
use Supra\Controller\Pages\Entity\ThemeLayout;
use Supra\Controller\Pages\Entity\Layout;
use Supra\Controller\Pages\Entity\Theme;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Layout\Theme\ThemeProviderAbstraction;
use Supra\Upgrade\Script\SkippableOnError;
use Supra\Upgrade\Plugin\DependencyValidationPlugin;

class S001_AddThemes extends UpgradeScriptAbstraction
{

	const THEME_NAME_DEFAULT = 'default';

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 *
	 * @var ThemeProviderAbstraction
	 */
	protected $themeProvider;
	
	/**
	 * @return boolean
	 */
	public function validate()
	{
		$dependencies = array(
			Theme::CN()
		);

		$em = ObjectRepository::getEntityManager($this);

		$validator = new DependencyValidationPlugin($em, $dependencies);
		$result = $validator->execute();
		
		return $result;
	}

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
	 * @return ThemeProviderAbstraction
	 */
	public function getThemeProvider()
	{
		if (empty($this->themeProvider)) {

			$em = $this->getEntityManager();

			$themeProvider = ObjectRepository::getThemeProvider($this);

			$themeProvider->setEntityManager($em);

			$this->themeProvider = $themeProvider;
		}

		return $this->themeProvider;
	}

	public function upgrade()
	{
		$this->runCommand('su:theme:add', array('name' => self::THEME_NAME_DEFAULT), array('directory' => SUPRA_TEMPLATE_PATH));
		$this->runCommand('su:theme:set_active', array('name' => self::THEME_NAME_DEFAULT));

		$this->upgradeLayouts();
	}

	protected function upgradeLayouts()
	{
		$output = $this->getOutput();

		$themeProvider = $this->getThemeProvider();

		/* @var $theme Theme */
		$theme = $themeProvider->findThemeByName(self::THEME_NAME_DEFAULT);

		$em = $this->getEntityManager();

		$templateLayoutRepository = $em->getRepository(TemplateLayout::CN());

		$templateLayouts = $templateLayoutRepository->findAll();

		$themeLayouts = $theme->getLayouts();

		$themeLayoutFilenameNameMap = array();

		/* @var $themeLayout ThemeLayout */
		foreach ($themeLayouts as $themeLayout) {
			$themeLayoutFilenameNameMap[$themeLayout->getFilename()] = $themeLayout->getName();
		}

		foreach ($templateLayouts as $templateLayout) {
			/* @var $templateLayout TemplateLayout */

			$oldLayout = $templateLayout->getOldLayout();

			if ( ! empty($oldLayout)) {

				$oldLayoutFilename = $oldLayout->getFile();

				$layoutName = $themeLayoutFilenameNameMap[$oldLayoutFilename];

				$templateLayout->setLayoutName($layoutName);

				$em->persist($templateLayout);
			}
		}

		$em->flush();

		$output->writeln('Upgrade complete.');
	}

}
