<?php

namespace Supra\Cms\ContentManager\Sitemaprecycle;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;

/**
 * Sitemap
 */
class SitemaprecycleAction extends PageManagerAction
{

	public function sitemapAction()
	{
		$response = $this->loadSitemapTree(Entity\PageLocalization::CN());

		$this->getResponse()
				->setResponseData($response);
	}

	public function templatesAction()
	{
		$response = $this->loadSitemapTree(Entity\TemplateLocalization::CN());

		$this->getResponse()
				->setResponseData($response);
	}

	public function restoreAction()
	{
		// Main
		$this->restorePageVersion();

		// Audit log
		$pageData = $this->getPageLocalization();
		$this->writeAuditLog('%item% restored', $pageData);
	}

	protected function loadSitemapTree($entity)
	{
		$pages = array();
		$response = array();

		$localeId = $this->getLocale()->getId();

		$auditEm = ObjectRepository::getEntityManager('#audit');

		$searchCriteria = array(
			'locale' => $localeId,
			'type' => PageRevisionData::TYPE_TRASH,
		);

		$qb = $auditEm->createQueryBuilder()
				->from($entity, 'l')
				->from(PageRevisionData::CN(), 'r')
				->select('l.id, l.title, l.revision, l.master, r.creationTime')
				->andWhere('r.type = :type')
				->andWhere('l.locale = :locale')
				->andWhere('l.revision = r.id')
				->orderBy('r.creationTime', 'DESC');

		if ($entity == Entity\PageLocalization::CN()) {
			$qb->addSelect('l.pathPart, l.template');
		}

		$localizationDataList = $qb->getQuery()
				->execute($searchCriteria, \Doctrine\ORM\Query::HYDRATE_ARRAY);

		foreach ($localizationDataList as $localizationData) {

			$pageInfo = array();
			$pathPart = null;
			$templateId = null;

			if ($entity == Entity\PageLocalization::CN()) {
				$pathPart = $localizationData['pathPart'];
				$templateId = $localizationData['template'];
			}

			$timeTrashed = $localizationData['creationTime']->format('Y-m-d');

			$pageInfo = array(
				// Sending master ID not localization ID
				'id' => $localizationData['id'],
				'master' => $localizationData['master'],
				'title' => $localizationData['title'],
				'template' => $templateId,
				'path' => $pathPart,
				'revision' => $localizationData['revision'],
				'date' => $timeTrashed,
				// TODO: do we need this?
				'icon' => 'page',
				'localized' => true,
				'published' => false,
				'scheduled' => false,
			);

			$response[] = $pageInfo;
		}

		return $response;
	}

}
