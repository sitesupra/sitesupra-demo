<?php

namespace Supra\Package\Cms\Controller;

use Doctrine\ORM\EntityManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Entity\GroupPage;
use Supra\Package\Cms\Entity\Abstraction\Localization;

class PagesGroupController extends AbstractPagesController
{
	/**
	 * Handles Page creation request.
	 */
	public function createAction()
	{
		$this->isPostRequest();

		$page = new GroupPage();

		$localeId = $this->getCurrentLocale()->getId();

		$localization = Localization::factory($page, $localeId);
		/* @var $localization \Supra\Package\Cms\Entity\GroupLocalization */

		$title = trim($this->getRequestParameter('title', ''));

		if (empty($title)) {
			throw new CmsException(null, 'Group title cannot be empty.');
		}

		$localization->setTitle($title);

		$parentLocalizationId = $this->getRequestParameter('parent_id');

		if (empty($parentLocalizationId)) {
			throw new \UnexpectedValueException(
					'Parent ID is empty while it is not allowed Group to be root.'
			);
		}

		$parentLocalization = $this->getEntityManager()
					->find(Localization::CN(), $parentLocalizationId);

		if ($parentLocalization === null) {
			throw new CmsException(null, sprintf(
					'Specified parent page [%s] not found.',
					$parentLocalizationId
			));
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

}
