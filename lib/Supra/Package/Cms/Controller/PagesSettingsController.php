<?php

namespace Supra\Package\Cms\Controller;

use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\RedirectTargetChild;
use Supra\Package\Cms\Entity\RedirectTargetPage;
use Supra\Package\Cms\Entity\RedirectTargetUrl;
use Supra\Package\Cms\Exception\CmsException;

class PagesSettingsController extends AbstractPagesController
{
	/**
	 * Handles page/template settings save request.
	 * 
	 * @return SupraJsonResponse
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$localization = $this->getPageLocalization();
		$page = $localization->getMaster();
		
		$input = $this->getRequestInput();

//		$page = $this->getPage();
//		$localeId = $this->getLocale()->getId();
//		$pageData = $page->getLocalization($localeId);

		if ($input->has('global')) {

			$global = $input->filter('global', false, false, FILTER_VALIDATE_BOOLEAN);

			if ($page->isRoot() && ! $global) {
				throw new \LogicException('It is not allowed to disable translation of root page.');
			}

			$page->setGlobal($global);
		}

		//@TODO: create some simple objects for save post data with future validation implementation?
		if ($input->has('title')) {
			$title = $input->get('title');
			$localization->setTitle($title);
		}

		if ($input->has('is_visible_in_menu')) {
			$visibleInMenu = $input->filter('is_visible_in_menu', null, false, FILTER_VALIDATE_BOOLEAN);
			$localization->setVisibleInMenu($visibleInMenu);
		}

		if ($input->has('is_visible_in_sitemap')) {
			$visibleInSitemap = $input->filter('is_visible_in_sitemap', null, false, FILTER_VALIDATE_BOOLEAN);
			$localization->setVisibleInSitemap($visibleInSitemap);
		}

		if ($input->has('include_in_search')) {
			$includedInSearch = $input->filter('include_in_search', null, false, FILTER_VALIDATE_BOOLEAN);
			$localization->setIncludedInSearch($includedInSearch);
		}

		if ($input->has('page_change_frequency')) {
			$changeFrequency = $input->get('page_change_frequency');
			$pageData->setChangeFrequency($changeFrequency);
		}

		if ($input->has('page_priority')) {
			$pagePriority = $input->get('page_priority');
			$pageData->setPagePriority($pagePriority);
		}

		if ($localization instanceof TemplateLocalization) {
			if ($input->has('layout')) {

				$media = $this->getMedia();
				
				$layoutName = $input->get('layout');

				// use parent layout
				if (empty($layoutName)) {
					
					if ($page->isRoot()) {
						throw new CmsException(null, "Can not use parent layout because current page is root page");
					}

					$parentTemplate = $page->getParent();
					/* @var $parentTemplate Template */

					$parentTemplateLayout = $parentTemplate->getTemplateLayouts()
							->get($media);

					if ($parentTemplateLayout === null) {
						throw new CmsException(null, "Parent template has no layout for [{$media}] media.");
					}
					/* @var $parentTemplateLayout TemplateLayout */

					$layoutName = $parentTemplateLayout->getLayoutName();
					
				}

				$theme = $this->getActiveTheme();

				if (! $theme->hasLayout($layoutName)) {
					throw new CmsException(null, sprintf('Active theme has no [%s] layout.', $layoutName));
				}

				// Remove current layout if any
				$currentTemplateLayout = $page->getTemplateLayouts()
						->get($media);

				if ($currentTemplateLayout !== null) {
					$this->getEntityManager()
							->remove($currentTemplateLayout);
				}

				$templateLayout = $page->addLayout($media, $theme->getLayout($layoutName));

				$this->getEntityManager()
						->persist($templateLayout);
			}
		}

		if ($localization instanceof PageLocalization) {

			if ($input->has('path')) {
				//TODO: validation
				$pathPart = $input->get('path');
				$localization->setPathPart($pathPart);
			}

			if ($input->has('template')) {
				//TODO: validation
				$templateId = $input->get('template');

				/* @var $template Template */
				$template = $this->getEntityManager()
						->find(Template::CN(), $templateId);

				$currentTemplate = $localization->getTemplate();

				if ( ! $template->equals($currentTemplate)) {

					$localization->setTemplate($template);

					// @FIXME: copy template blocks should happen' here
//					$request = $this->getPageRequest();
//					$request->createMissingPlaceHolders(true);
				}
			}

			if ($input->has('active')) {
				$active = $input->filter('active', null, false, FILTER_VALIDATE_BOOLEAN);
				$localization->setActive($active);
			}

			if ($input->has('description')) {
				$metaDescription = $input->get('description');
				$localization->setMetaDescription($metaDescription);
			}

			if ($input->has('keywords')) {
				$metaKeywords = $input->get('keywords');
				$localization->setMetaKeywords($metaKeywords);
			}

			if ($input->has('scheduled_date')) {

//				try {
					//TODO: validation
					$date = $input->get('scheduled_date');
					$time = $input->get('scheduled_time', '00:00');

					if (empty($date)) {
						$localization->unsetScheduleTime();
					} else {
						if (empty($time)) {
							$time = '00:00';
						}

						$dateTime = "{$date}T{$time}";

						$scheduleTime = new \DateTime($dateTime);

						if ($scheduleTime instanceof \DateTime) {
							$localization->setScheduleTime($scheduleTime);
						} else {
							throw new CmsException(null, "Schedule time provided in unrecognized format.");
						}
					}
//				} catch (EntityAccessDeniedException $e) {
//
//					$this->getResponse()
//							->addWarningMessage('Scheduled publish date is not saved. You must have Supervise permission to use scheduling functionality.');
//				}
			}

			if ($input->has('created_date')) {

				$date = $input->get('created_date');
				$time = $input->get('created_time', '00:00');

				// Set manually only if both elements are received
				if ( ! empty($date) && ! empty($time)) {
					$dateTime = "{$date}T{$time}";

					$creationTime = new \DateTime($dateTime);

					if ($creationTime instanceof \DateTime) {
						$localization->setCreationTime($creationTime);
					} else {
						throw new CmsException(null, "Creation time provided in unrecognized format.");
					}
				}
			}

			if ($localization->hasRedirectTarget()) {
				$this->getEntityManager()
						->remove($localization->getRedirectTarget());
			}

			if ($input->has('redirect')) {
				$redirectTarget = $this->createRedirectTargetFromData(
						$input->get('redirect', array())
				);

				$this->getEntityManager()
						->persist($redirectTarget);

				$localization->setRedirectTarget($redirectTarget);
			}
		}

		try {
			$this->getEntityManager()->flush();
		} catch (DuplicatePagePathException $e) {
			throw new CmsException(null, $e->getMessage());
		}

//		$this->savePostTrigger();
	}

	/**
	 * List of templates
	 */
	public function templatesAction()
	{
		$localeId = $this->getLocale()->getId();
		$templateArray = array();
		$templateTitles = array();

		$templateDataDao = $this->entityManager->getRepository(Entity\TemplateLocalization::CN());
		$templateDataList = $templateDataDao->findByLocale($localeId);

		$iniLoader = ObjectRepository::getIniConfigurationLoader($this);

		$doNotUseAsDefaultTemplateIds = explode(';', $iniLoader->getValue('system', 'dont_use_as_default_template_ids', ''));

//		\Log::error('$doNotUseAsDefaultTemplateIds: ', $doNotUseAsDefaultTemplateIds);

		/* @var $templateData Entity\TemplateLocalization */
		foreach ($templateDataList as $templateData) {

			$previewPath = $templateData->getPreviewFilename();

			if (file_exists($previewPath)) {
				$previewUrl = $templateData->getPreviewUrl();
			} else {
				$previewUrl = '/cms/lib/supra/img/sitemap/preview/blank.jpg';
			}

			if (file_exists($previewPath)) {
				$previewUrl = $templateData->getPreviewUrl();
			} else {
				$previewUrl = '/cms/lib/supra/img/sitemap/preview/blank.jpg';
			}

			$templateArray[] = array(
				'id' => $templateData->getMaster()->getId(),
				'title' => $templateData->getTitle(),
				'icon' => $previewUrl,
				'dont_use_as_default' => in_array($templateData->getMaster()->getId(), $doNotUseAsDefaultTemplateIds)
			);

			$templateTitles[] = $templateData->getTitle();
		}

		array_multisort($templateTitles, $templateArray);

		$this->getResponse()->setResponseData($templateArray);
	}

	/**
	 * @param array $data
	 * @return RedirectTarget
	 */
	private function createRedirectTargetFromData(array $data)
	{
		$type = isset($data['type']) ? $data['type'] : null;

		switch ($type) {
			case 'page':
				$redirectTarget = new RedirectTargetPage();
				break;
			case 'child':
				$redirectTarget = new RedirectTargetChild();
				$redirectTarget->setPage(
						$this->getPageLocalization()->getPage()
				);
				break;
			case 'url':
				$redirectTarget = new RedirectTargetUrl();
				break;
			default:
				throw new \UnexpectedValueException(sprintf(
						'Unknown redirect target type [%s].',
						$type
				));
		}

		return $redirectTarget;
	}
}