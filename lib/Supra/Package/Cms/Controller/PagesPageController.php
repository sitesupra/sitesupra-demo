<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Exception\LayoutNotFound;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Pages\Exception\ObjectLockedException;

class PagesPageController extends AbstractPagesController
{
	/**
	 * Gets available template list action.
	 *
	 * @internal Called by plugin-page-add.js,
	 * to render list of available layouts on new Template creation.
	 *
	 * @return SupraJsonResponse
	 */
	public function layoutsListAction()
	{
		return new SupraJsonResponse($this->getCurrentThemeLayoutsData());
	}

	/**
	 * Called on page editing start.
	 *
	 * @return SupraJsonResponse
	 */
	public function lockAction()
	{
		return $this->lockPage();
	}

	/**
	 * Called on page editing end.
	 * 
	 * @return SupraJsonResponse
	 */
	public function unlockAction()
	{
		try {
			$this->checkLock();

			$this->unlockPage();
			
		} catch (ObjectLockedException $e) {
			// @TODO: check why it were made to ignore errors if locked.
		}

		return new SupraJsonResponse();
	}

	/**
	 * Returns page localization properties, inner html and placeholder contents.
	 *
	 * @return SupraJsonResponse
	 */
	public function pageAction()
	{
		$localization = $this->getPageLocalization();

		$pageRequest = $this->getPageRequest();

		$pageRequest->setPageLocalization($localization);

		$pageController = $this->getPageController();
		$pageController->setPageRequest($pageRequest);

//		$page = $pageRequest->getPage();

//		$response = $controller->createResponse($request);
//		$controller->prepare($request, $response);
//
//		$this->setInitialPageId($localization->getId());

		$templateException = $response
				= $internalHtml
				= null;
		try {
			$response = $pageController->execute();
			/* @var $response \Supra\Package\Cms\Pages\Response\PageResponse */
		} catch (\Twig_Error_Loader $e) {
			$templateException = $e;
		} catch (LayoutNotFound $e) {
			$templateException = $e;
		} catch (\Exception $e) {
			throw $e;
		}

		$localizationData = $this->getLocalizationData($localization);

		if ($templateException) {
			$internalHtml = '<h1>Page template or layout not found.</h1>
				<p>Please make sure the template is assigned and the template is published in this locale and it has layout assigned.</p>';
		} elseif ($response) {
			$internalHtml = $response->getContent();
		}

		$localizationData['internal_html'] = $internalHtml;

		$placeHolders = $pageRequest->getPlaceHolderSet()
				->getFinalPlaceHolders();
		
		$blocks = $pageRequest->getBlockSet();

//		// Collecting locked blocks
//		$lockedBlocks = array();
//		foreach ($blockSet as $block) {
//
//			if ($block->getLocked()) {
//				$holderName = $block->getPlaceHolder()->getName();
//
//				if ( ! isset($lockedBlocks[$holderName])) {
//					$lockedBlocks[$holderName] = array();
//				}
//
//				$lockedBlocks[$holderName][] = $block;
//			}
//		}

		$placeHoldersData = &$localizationData['contents'];

		foreach ($placeHolders as $placeHolder) {

			$blocksData = array();

			foreach ($blocks->getPlaceHolderBlockSet($placeHolder) as $block) {
				/* @var $block Block */
				$blocksData[] = $this->getBlockData($block);
			}

			$placeHolderData = array(
				'id'		=> $placeHolder->getName(),
				'title'		=> $placeHolder->getTitle(),
				'locked'	=> $placeHolder->isLocked(),
				'closed'	=> ! $localization->isPlaceHolderEditable($placeHolder),
				'contents'	=> $blocksData,
				// @TODO: if this one is hardcoded, why not to hardcode in UI?
				'type'		=> 'list',
				// @TODO: list of blocks that are allowed to be insterted
//				'allow' => array(
//						0 => 'Project_Text_TextController',
//				),
			);

			$placeHoldersData[] = $placeHolderData;
		}

		$jsonResponse = new SupraJsonResponse($localizationData);

		// @FIXME: dummy. when needed, move to prefilter.
		$jsonResponse->setPermissions(array(array(
			'edit_page' => true,
			'supervise_page' => true
		)));
		
		return $jsonResponse;
	}

	/**
	 * @param Localization $localization
	 * @return array
	 */
	private function getLocalizationData(Localization $localization)
	{
		$page = $localization->getMaster();

		$allLocalizationData = array();
		foreach ($page->getLocalizations() as $locale => $pageLocalization) {
			$allLocalizationData[$locale] = $pageLocalization->getId();
		}

		$ancestorIds = Entity::collectIds($localization->getAncestors());

		// abstract localization data
		$localizationData = array(
			'root'				=> $page->isRoot(),
			'tree_path'			=> $ancestorIds,
			'locale'			=> $localization->getLocaleId(),

			// All available localizations
			'localizations'		=> $allLocalizationData,

			// Editing Lock info
			'lock'				=> $this->getLocalizationLockData($localization),

			// Common properties
			'is_visible_in_menu'	=> $localization->isVisibleInMenu(),
			'is_visible_in_sitemap' => $localization->isVisibleInSitemap(),
			'include_in_search'		=> $localization->isIncludedInSearch(),
			
			// Common SEO properties
			'page_change_frequency' => $localization->getChangeFrequency(),
			'page_priority'			=> $localization->getPagePriority(),

// @TODO: check, if is used
//			'allow_edit'			=> @TODO, //$this->isAllowedToEditLocalization($localization),

			// Content defaults
			'internal_html' => null,
			'contents' => array(),

// @TODO: check, must be returned by parent method
//			'path_prefix'		=> ($localization->hasParent() ? $localization->getParent()->getPath() : null),
		);

		if ($localization instanceof PageLocalization) {

			$creationTime = $localization->getCreationTime();
			$publicationSchedule = $localization->getScheduleTime();

			$localizationData = array_replace($localizationData, array(
				// Page properties
				'keywords'			=> $localization->getMetaKeywords(),
				'description'		=> $localization->getMetaDescription(),
				// @TODO: return in one piece
				'created_date'		=> $creationTime->format('Y-m-d'),
				'created_time'		=> $creationTime->format('H:i:s'),
				// @TODO: return in one piece
				'scheduled_date'	=> $publicationSchedule ? $publicationSchedule->format('Y-m-d') : null,
				'scheduled_time'	=> $publicationSchedule ? $publicationSchedule->format('H:i:s') : null,

				'active'			=> $localization->isActive(),

				// Used template info
				'template'			=> array(
					'id'	=> $localization->getTemplate()->getId(),
					'title' => $localization->getTemplateLocalization()->getTitle(),
				),
			));
			
		} elseif ($localization instanceof TemplateLocalization) {

			$layoutData = null;
			
			if ($page->hasLayout($this->getMedia())) {

				$layout = $page->getLayout($this->getMedia());

				$layoutData = array(
					'id'	=> $layout->getName(),
					'title' => $layout->getTitle(),
				);
			}
			
			$localizationData = array_replace($localizationData, array(
				'layouts' => $this->getCurrentThemeLayoutsData(),
				'layout' => $layoutData,
			));
		}

		return array_replace(
				$this->loadNodeMainData($localization),
				$localizationData
		);
	}

	/**
	 * @param Block $block
	 * @return array
	 */
	private function getBlockData(Block $block)
	{
		return array(
			'id'			=> $block->getId(),
			'type'			=> $block->getComponentName(),
			'closed'		=> false,//@fixme
			'locked'		=> $block->isLocked(),
			'properties'	=> $this->getBlockPropertyData($block),
			// @TODO: check if this still is used somewhere, remove if not.
			'owner_id'	=> $block->getPlaceHolder()
								->getLocalization()->getId()
		);
	}

	/**
	 * @param Block $block
	 * @return array
	 */
	private function getBlockPropertyData(Block $block)
	{
		$blockCollection = $this->getBlockCollection();

		$controller = $blockCollection->createController($block);

		// @TODO: avoid somehow all this
		$pageRequest = $this->getPageRequest();
		$pageController = $this->getPageController();

		$responseContext = $pageController->getPageResponse()->getContext();

		$block->prepareController($controller, $pageRequest, $responseContext);

		$configuration = $controller->getConfiguration();

		$propertyData = array();

		foreach ($configuration->getProperties() as $propertyConfiguration) {

			$name = $propertyConfiguration->getName();

			$propertyData[$name] = array(
				'value' => $controller->getPropertyValue($name)
			);
		}

		return $propertyData;
	}

	/**
	 * @return array
	 */
	private function getCurrentThemeLayoutsData()
	{
		$themeProvider = $this->getThemeProvider();

		$theme = $themeProvider->getActiveTheme();

		$layoutsData = array();

		foreach ($theme->getLayouts() as $layout) {

			$layoutName = $layout->getName();

			$layoutsData[] = array(
				'id'	=> $layoutName,
				'title' => $layout->getTitle(),
				'icon'	=> $layout->getIcon(),
			);
		}

		return $layoutsData;
	}
}