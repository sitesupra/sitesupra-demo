<?php

namespace Project\FancyBlocks\Menu\Left;

use Supra\Editable\Link;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Project\FancyBlocks\Menu\MenuBlock;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Finder\PageFinder;
use Supra\Controller\Pages\Finder\LocalizationFinder;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity\Page;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Entity;

/**
 * LeftMenuBlock
 */
class LeftMenuBlock extends MenuBlock
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $currentLocalizationIds;

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
	 * 
	 */
	protected function doExecute()
	{
		$response = $this->getResponse();

		$menuData = $this->getMenuData();

		$response->assign('menu_items', $menuData);

		$response->outputTemplate('left_menu.html.twig');
	}

	/**
	 * @return array
	 */
	protected function getCurrentLocalizationIds()
	{
		if (is_null($this->currentLocalizationIds)) {

			/* @var $page Page */
			$page = $this->getRequest()->getPageLocalization();

			$ids = array();
			do {
				$ids[] = $page->getId();
				$page = $page->getParent();
			} while ($page != null);

			$this->currentLocalizationIds = $ids;
		}

		return $this->currentLocalizationIds;
	}

	/**
	 * 
	 */
	protected function getMenuData()
	{
		$localization = $this->getRequest()
				->getPageLocalization();
		
		if ( ! $localization instanceof Entity\PageLocalization) {
			$localization = $this->getRootLocalization();
		}
		
		// this will happen when there will be no any page
		if ( ! $localization instanceof Entity\PageLocalization) {
			return array();
		}
	
		$menuData = $this->getMenuLevelData($localization);

		return $menuData;
	}

	/**
	 * @param Localization $rootLocalization 
	 */
	protected function getMenuLevelData(Localization $rootLocalization)
	{
		$em = $this->getEntityManager();

		$pageFinder = new PageFinder($em);
		$localizationFinder = $this->getLocalizationFinder($pageFinder);
		$localizationFinder->addFilterByParent($rootLocalization, 1, 1);
		$localizationFinder->addCustomCondition('l.visibleInMenu = true');

		$localizations = $localizationFinder->getResult();

		$menuLevelData = array();

		foreach ($localizations as $localization) {
			$menuLevelData[] = $this->makeMenuItemData($localization);
		}

		return $menuLevelData;
	}

	/**
	 * @param Localization $localization
	 * @return array
	 */
	protected function makeMenuItemData(Localization $localization)
	{
		$currentIds = $this->getCurrentLocalizationIds();

		$active = false;
		$items = array();

		if (in_array($localization->getId(), $currentIds)) {

			$active = true;

			if ( ! $localization instanceof ApplicationLocalization) {
				$items = $this->getMenuLevelData($localization);
			}
		}

		return array(
			'title' => $localization->getTitle(),
			'path' => $localization->getFullPath(Path::FORMAT_BOTH_DELIMITERS),
			'items' => $items,
			'active' => $active
		);
	}

	/**
	 * @return Localization
	 */
	protected function getRootLocalization()
	{
		$em = $this->getEntityManager();

//		$rootLocalization = null;
//
//		$rootLink = $this->getPropertyValue('rootLink');
//
//		if ( ! empty($rootLink)) {
//			/* @var $rootPageLink LinkReferencedElement */
//			$rootLocalization = $rootLink->getPage();
//		} else {

		$pageFinder = new PageFinder($em);
		$pageFinder->addLevelFilter(0, 0);

		$localizationFinder = new LocalizationFinder($pageFinder);

		$rootLocalizations = $localizationFinder->getResult();

		if (count($rootLocalizations) > 1) {
			throw new \RuntimeException('More than one root localization found.');
		}

		$rootLocalization = current($rootLocalizations);
//		}

//		if (empty($rootLocalization)) {
//			throw new \RuntimeException('Root localization not found.');
//		}

		return $rootLocalization;
	}
}
