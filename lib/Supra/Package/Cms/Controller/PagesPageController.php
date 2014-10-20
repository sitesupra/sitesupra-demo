<?php

namespace Supra\Package\Cms\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Exception\LayoutNotFound;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\ApplicationPage;
use Supra\Package\Cms\Entity\GroupPage;

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
	 * Returns page localization properties, inner html and placeholder contents.
	 *
	 * @return SupraJsonResponse
	 */
	public function pageAction()
	{
		$localization = $this->getPageLocalization();

		$pageRequest = $this->createPageRequest();

		$pageController = $this->getPageController();

		$templateException = $response
				= $internalHtml
				= null;
		
		try {
			$response = $pageController->execute($pageRequest);
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
		} elseif ($response instanceof Response) {
			$internalHtml = $response->getContent();
		}

		$localizationData['internal_html'] = $internalHtml;

		$placeHolders = $pageRequest->getPlaceHolderSet()
				->getFinalPlaceHolders();
		
		$blocks = $pageRequest->getBlockSet();

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
	 * Handles Page creation request.
	 */
	public function createAction()
	{
		$this->isPostRequest();

		$type = $this->getRequestParameter('type');

		$page = null;

		switch ($type) {
			case Entity::GROUP_DISCR:
				$page = new GroupPage();
				break;
			case Entity::APPLICATION_DISCR:
				$page = new ApplicationPage(
						$this->getRequestParameter('application_id')
				);
				break;
			case Entity::PAGE_DISCR:
				$page = new Page();
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Unknown page type [%s]', $type));
		}

		$localeId = $this->getCurrentLocale()->getId();

		$localization = Localization::factory($page, $localeId);
		/* @var $localization PageLocalization */

		if (! $localization instanceof PageLocalization) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting created localization to be instance of PageLocalization, [%s] received.',
					get_class($localization)
			));
		}

		$templateId = $this->getRequestParameter('template');
		
		$template = $this->getEntityManager()
				->find(Template::CN(), $templateId);
		/* @var $template Template */

		if ($template === null) {
			throw new CmsException(null, 'Template not specified or found.');
		}

		$templateLocalization = $template->getLocalization($localeId);

		if ($templateLocalization === null) {
			throw new \InvalidArgumentException(
					"Specified template has no localization for [{$localeId}] locale."
			);
		}

		$localization->setTemplate($template);

		// copy values from template
		$localization->setIncludedInSearch($templateLocalization->isIncludedInSearch());
		$localization->setVisibleInMenu($templateLocalization->isVisibleInMenu());
		$localization->setVisibleInSitemap($templateLocalization->isVisibleInSitemap());

		$title = trim($this->getRequestParameter('title', ''));

		if (empty($title)) {
			throw new CmsException(null, 'Page title cannot be empty.');
		}
		
		$localization->setTitle($title);

		$parentLocalization = $pathPart
				= null;

		$parentLocalizationId = $this->getRequestParameter('parent_id');
		
		if (! empty($parentLocalizationId)) {

			$parentLocalization = $this->getEntityManager()
					->find(PageLocalization::CN(), $parentLocalizationId);

			if ($parentLocalization === null) {
				throw new CmsException(null, sprintf(
						'Specified parent page [%s] not found.',
						$parentLocalizationId
				));
			}

			$pathPart = trim($this->getRequestParameter('path'));

			// path part cannot be empty for non-root pages.
			if (empty($pathPart)) {
				throw new CmsException(null, 'Page path can not be empty.');
			}
		}

		if ($parentLocalization && $pathPart) {
			$localization->setPathPart($pathPart);
		} else {
			$rootPath = $localization->getPathEntity();
			$rootPath->setPath('');
			$localization->setPathPart('');
		}

		$entityManager = $this->getEntityManager();

		$entityManager->transactional(function (EntityManager $entityManager) use ($page, $localization, $parentLocalization) {

			$this->lockNestedSet($page);

			$entityManager->persist($page);
			$entityManager->persist($localization);

			if ($parentLocalization) {
				$page->moveAsLastChildOf($parentLocalization->getMaster());
			}

			$this->unlockNestedSet($page);
		});

		return new SupraJsonResponse($this->loadNodeMainData($localization));
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

				$layoutName = $page->getLayoutName($this->getMedia());

				$layout = $this->getActiveTheme()
						->getLayout($layoutName);

				if ($layout !== null) {
					$layoutData = array(
						'id'	=> $layout->getName(),
						'title' => $layout->getTitle(),
					);
				}
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