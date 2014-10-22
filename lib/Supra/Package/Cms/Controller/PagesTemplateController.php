<?php

namespace Supra\Package\Cms\Controller;

use Doctrine\ORM\EntityManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\Abstraction\Localization;

class PagesTemplateController extends AbstractPagesController
{
	/**
	 * @return SupraJsonResponse
	 */
	public function createAction()
	{
		$this->isPostRequest();

		$localeId = $this->getCurrentLocale()
				->getId();
		
		$template = new Template();
		$localization = Localization::factory($template, $localeId);

		$title = trim($this->getRequestParameter('title', ''));
		
		if (empty($title)) {
			throw new CmsException(null, 'Template title cannot be empty.');
		}

		$localization->setTitle($title);

		$entityManager = $this->getEntityManager();

		$parentLocalization = null;
		$parentLocalizationId = $this->getRequestParameter('parent_id');
		
		$layoutName = $this->getRequestParameter('layout');

		if (empty($parentLocalizationId) && empty($layoutName)) {
			throw new CmsException(null, 'Root template must have layout specified.');
		}

		if (! empty($parentLocalizationId)) {

			$parentLocalization = $this->getEntityManager()
					->find(TemplateLocalization::CN(), $parentLocalizationId);

			if ($parentLocalization === null) {
				throw new CmsException(null, sprintf(
						'Specified parent template [%s] not found.',
						$parentLocalizationId
				));
			}
		}

		if (! empty($layoutName)) {

			$themeTemplateLayout = $this->getActiveTheme()
					->getLayout($layoutName);

			if ($themeTemplateLayout === null) {
				throw new CmsException(null, sprintf('Layout [%s] not found.', $themeTemplateLayout));
			}

			$template->addLayout($this->getMedia(), $themeTemplateLayout);
		}

		$entityManager->transactional(function (EntityManager $entityManager) use ($template, $localization, $parentLocalization) {

			$this->lockNestedSet($template);

			$entityManager->persist($template);
			$entityManager->persist($localization);

			$entityManager->flush();

			if ($parentLocalization) {
				$template->moveAsLastChildOf($parentLocalization->getMaster());
			}

			$this->unlockNestedSet($template);
		});

//		@FIXME
//		// Decision in #2695 to publish the template right after creating it
//		$this->pageData = $templateData;
//		$this->publish();

		return new SupraJsonResponse($this->loadNodeMainData($localization));		
	}

	/**
	 * Handles template list request.
	 *
	 * @return SupraJsonResponse
	 */
	public function templatesListAction()
	{
		$localeId = $this->getCurrentLocale()
				->getId();

		$templateLocalizations = $this->getEntityManager()
				->getRepository(TemplateLocalization::CN())
				->findBy(array('locale' => $localeId), array('title' => 'asc'));

		/* @var $templateLocalizations TemplateLocalization[] */

		$responseData = array();

		foreach ($templateLocalizations as $templateLocalization) {
			$responseData[] = array(
				'id'		=> $templateLocalization->getMaster()->getId(),
				'title'		=> $templateLocalization->getTitle(),
			);
		}

		return new SupraJsonResponse($responseData);
	}

	/**
	 * @return SupraJsonResponse
	 */
	public function saveSettingsAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$localization = $this->getPageLocalization();

		if (! $localization instanceof TemplateLocalization) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting TemplateLocalization instance, [%s] received.',
					get_class($localization)
			));
		}

		$this->saveLocalizationCommonSettingsAction();

		$template = $localization->getMaster();

		$media = $this->getMedia();

		$layoutName = $this->getRequestParameter('layout');

		// use parent layout
		if (empty($layoutName)) {

			if ($template->isRoot()) {
				throw new CmsException(null, "Can not use parent layout because current page is root page");
			}

			$parentTemplate = $template->getParent();
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
		$currentTemplateLayout = $template->getTemplateLayouts()
				->get($media);

		if ($currentTemplateLayout !== null) {
			$this->getEntityManager()
					->remove($currentTemplateLayout);
		}

		$templateLayout = $template->addLayout($media, $theme->getLayout($layoutName));

		$this->getEntityManager()
				->persist($templateLayout);


		$this->getEntityManager()
				->flush();

		return new SupraJsonResponse();
	}

}
