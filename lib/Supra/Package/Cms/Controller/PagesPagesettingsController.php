<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Entity\TemplateLocalization;

class PagesPagesettingsController extends AbstractPagesController
{
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
}