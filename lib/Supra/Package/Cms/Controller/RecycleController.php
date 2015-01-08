<?php

namespace Supra\Package\Cms\Controller;

use SimpleThings\EntityAudit\AuditManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Core\NestedSet\Node\DoctrineNode;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Pages\Exception\DuplicatePagePathException;
use Symfony\Component\HttpFoundation\Response;

class RecycleController extends AbstractPagesController
{
	/**
	 * @return SupraJsonResponse
	 */
	public function loadPagesAction()
	{
		return new SupraJsonResponse($this->loadData('Cms:PageLocalization'));
	}

	/**
	 * @return SupraJsonResponse
	 */
	public function loadTemplatesAction()
	{
		return new SupraJsonResponse($this->loadData('Cms:TemplateLocalization'));
	}

	/**
	 * @param string $entityName
	 * @return array
	 */
	protected function loadData($entityName)
	{
		$response = array();

		$locale = $this->container->getLocaleManager()->getCurrentLocale();

		$auditManager = $this->container['entity_audit.manager'];
		/* @var $auditManager AuditManager */

		$auditConfiguration = $auditManager->getConfiguration();

		$reader = $auditManager->createAuditReader($this->getEntityManager());

		$localizationMeta = $this->container->getDoctrine()->getManager()->getClassMetadata($entityName);
		/* @var $localizationMeta \Doctrine\ORM\Mapping\ClassMetadataInfo */

		$abstractPageMeta = $this->container->getDoctrine()->getManager()->getClassMetadata('Cms:Abstraction\AbstractPage');
		/* @var $abstractPageMeta \Doctrine\ORM\Mapping\ClassMetadataInfo */

		$query = 'SELECT l.id, l.title, l.'.$auditConfiguration->getRevisionFieldName().', l.template_id, l.path_part, '.
			'r.username, r.timestamp '.
			'FROM '.$auditConfiguration->getTablePrefix().$localizationMeta->table['name'].$auditConfiguration->getTableSuffix().' l '.
			'INNER JOIN '.$auditConfiguration->getRevisionTableName().' r '.
			'ON r.id = l.'.$auditConfiguration->getRevisionFieldName().' '.
			'WHERE l.'.$auditConfiguration->getRevisionTypeFieldName().' = ? '.
				'AND l.locale = ?'.
				'AND l.discr = ?'.
				'AND NOT EXISTS ('.
					'SELECT * FROM '.
					$abstractPageMeta->table['name'].' ap '.
					'WHERE ap.id = (SELECT master_id FROM '.
						$auditConfiguration->getTablePrefix().$localizationMeta->table['name'].$auditConfiguration->getTableSuffix().' p '.
						'WHERE p.'.$auditConfiguration->getRevisionFieldName().' < l.'.$auditConfiguration->getRevisionFieldName().' '.
							'AND p.id = l.id '.
						'ORDER BY p.'.$auditConfiguration->getRevisionFieldName().' DESC '.
						'LIMIT 1'.
					')'.
				')'
		;

		print $query;

		$params = array(
			'DEL',
			$locale->getId(),
			$localizationMeta->discriminatorValue
		);

		$typeFilter = $this->getRequestParameter('filter');
		if ( ! empty($typeFilter)) {
			$query .= 'AND l.parentPageApplicationId = ? ';
			$params[] = $typeFilter;
		}

		$query .= 'ORDER BY l.' . $auditConfiguration->getRevisionFieldName() .' DESC';

		foreach ($reader->getConnection()->fetchAll($query, $params) as $row) {
			$response[] = array(
				'id' => $row['id'],
				'revision' => $row['rev'],
				'date' => \DateTime::createFromFormat('Y-m-d H:i:s', $row['timestamp'])->format(DATE_ATOM),
				'title' => $row['title'],
				'author' => $row['username'],
			);
		}

		return $response;
	}


	public function restoreAction()
	{
		$this->lockNestedSet('Cms:Page');

		$response = $this->restorePageVersion();

		$this->unlockNestedSet('Cms:Page');

		return $response;
	}

	protected function restorePageVersion()
	{
		//$this->isPostRequest();

		$revisionId = $this->getRequestParameter('revision_id');
		$localizationId = $this->getRequestParameter('page_id');

		$em = $this->container->getDoctrine()->getManager();

		$locale = $this->container->getLocaleManager()->getCurrentLocale();

		$auditManager = $this->container['entity_audit.manager'];
		/* @var $auditManager AuditManager */

		$auditReader = $auditManager->createAuditReader($em);

		//TODO: this is hacky (revisionId - 1) because EA does not take into account DEL revisions
		$localization = $auditReader->find(PageLocalization::CN(), $localizationId, $revisionId - 1);
		/* @var PageLocalization $localization */

		$page = $localization->getMaster();
		/* @var Page $page */

		foreach ($page->getLocalizations() as $localization) {
			//reload templates
			$template = $localization->getTemplate();

			$realTemplate = $em->getRepository('Cms:Template')->find($template->getId());

			if ($realTemplate) {
				$localization->setTemplate($realTemplate);
			} else {
				$em->persist($template);
			}

			//reload localization path
			$localizationPath = $localization->getPathEntity();

			$realLocalizationPath = $em->getRepository('Cms:PageLocalizationPath')->find($localizationPath->getId());

			if ($realLocalizationPath) {
				$localization->setPathEntity($realLocalizationPath);
			} else {
				$em->persist($localizationPath);
			}

			$em->persist($localization);
		}

		//reset nested set node
		$page->setLevel(null);
		$page->setLeftValue(null);
		$page->setRightValue(null);

		$node = new DoctrineNode($em->getRepository($page->getNestedSetRepositoryClassName()));
		$page->setNestedSetNode($node);
		$node->belongsTo($page);

		$em->persist($page);
		$em->flush();

		$parent = $this->getPageByRequestKey('parent_id');
		$reference = $this->getPageByRequestKey('reference');

		try {
			if (!is_null($reference)) {
				$page->moveAsPrevSiblingOf($reference);
			} elseif (!is_null($parent)) {
				$parent->addChild($page);
			}
		} catch (DuplicatePagePathException $uniqueException) {

			$confirmation = $this->getConfirmation('{#sitemap.confirmation.duplicate_path#}');

			if ($confirmation instanceof Response) {
				return $confirmation;
			}

			$localizations = $page->getLocalizations();

			foreach ($localizations as $localization) {
				$pathPart = $localization->getPathPart();

				// some bad solution
				$localization->setPathPart($pathPart . '-' . time());
			}

			if (!is_null($reference)) {
				$page->moveAsPrevSiblingOf($reference);
			} elseif (!is_null($parent)) {
				$parent->addChild($page);
			}
		}

		return new SupraJsonResponse(true);

		// We need it so later we can mark it as restored
		$pageRevisionData = $draftEm->getRepository(PageRevisionData::CN())
			->findOneBy(array('type' => PageRevisionData::TYPE_TRASH, 'id' => $revisionId));

		if ( ! ($pageRevisionData instanceof PageRevisionData)) {
			throw new CmsException(null, 'Page revision data not found');
		}

		$masterId = $auditEm->createQuery("SELECT l.master FROM page:Abstraction\Localization l
				WHERE l.id = :id AND l.revision = :revision")
			->execute(
				array('id' => $localizationId, 'revision' => $revisionId), ColumnHydrator::HYDRATOR_ID);

		$page = null;

		try {
			$page = $auditEm->getRepository(AbstractPage::CN())
				->findOneBy(array('id' => $masterId, 'revision' => $revisionId));
		} catch (MissingResourceOnRestore $missingResource) {
			$missingResourceName = $missingResource->getMissingResourceName();
			throw new CmsException(null, "Wasn't able to load the page from the history because linked resource {$missingResourceName} is not available anymore.");
		}

		if (empty($page)) {
			throw new CmsException(null, "Cannot find the page");
		}

		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();

		$pageLocalization = $page->getLocalization($localeId);

		if (is_null($pageLocalization)) {
			throw new CmsException(null, 'This page has no localization for current locale');
		}

		$request = new HistoryPageRequestEdit($localeId, $media);
		$request->setDoctrineEntityManager($draftEm);
		$request->setPageLocalization($pageLocalization);

		$draftEventManager = $draftEm->getEventManager();
		$draftEventManager->dispatchEvent(AuditEvents::pagePreRestoreEvent);

		$parent = $this->getPageByRequestKey('parent_id');
		$reference = $this->getPageByRequestKey('reference');

		// Did not allowed to restore root page. Ask Aigars for details
//		if (is_null($parent) && $page instanceof Page) {
//			throw new CmsException('sitemap.error.parent_page_not_found');
//		}

		$draftEm->beginTransaction();
		try {
			$request->restorePage();

			// Read from the draft now
			$page = $draftEm->find(AbstractPage::CN(), $page->getId());

			/* @var $page AbstractPage */

			try {
				if ( ! is_null($reference)) {
					$page->moveAsPrevSiblingOf($reference);
				} elseif ( ! is_null($parent)) {
					$parent->addChild($page);
				}
			} catch (DuplicatePagePathException $uniqueException) {

				$this->getConfirmation('{#sitemap.confirmation.duplicate_path#}');

				$localizations = $page->getLocalizations();
				foreach ($localizations as $localization) {
					$pathPart = $localization->getPathPart();

					// some bad solution
					$localization->setPathPart($pathPart . '-' . time());
				}

				if ( ! is_null($reference)) {
					$page->moveAsPrevSiblingOf($reference);
				} elseif ( ! is_null($parent)) {
					$parent->addChild($page);
				}
			}

			$pageRevisionData->setType(PageRevisionData::TYPE_RESTORED);
			$draftEm->flush();

			$localization = $page->getLocalization($localeId);
			$this->pageData = $localization;
		} catch (\Exception $e) {
			$draftEm->rollback();
			throw $e;
		}
	}
}
