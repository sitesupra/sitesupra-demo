<?php

namespace Supra\Package\Cms\Controller;

use SimpleThings\EntityAudit\AuditManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Entity\PageLocalization;

class RecycleController extends AbstractPagesController
{
	public function loadPagesAction()
	{
		$data = $this->loadSitemapTree(PageLocalization::CN());

		return new SupraJsonResponse($data);
	}

	protected function loadSitemapTree($entity)
	{
		$response = array();

		$locale = $this->container->getLocaleManager()->getCurrentLocale();

		$auditManager = $this->container['entity_audit.manager'];
		/* @var $auditManager AuditManager */

		$auditConfiguration = $auditManager->getConfiguration();

		$reader = $auditManager->createAuditReader($this->getEntityManager());

		$localizationMeta = $this->container->getDoctrine()->getManager()->getClassMetadata('Cms:PageLocalization');

		$query = 'SELECT l.id, l.title, l.'.$auditConfiguration->getRevisionFieldName().', l.template_id, l.path_part, '.
			'r.username, r.timestamp '.
			'FROM '.$auditConfiguration->getTablePrefix().$localizationMeta->table['name'].$auditConfiguration->getTableSuffix().' l '.
			'INNER JOIN '.$auditConfiguration->getRevisionTableName().' r '.
			'ON r.id = l.'.$auditConfiguration->getRevisionFieldName().' '.
			'WHERE l.'.$auditConfiguration->getRevisionTypeFieldName().' = ? '.
				'AND l.locale = ? '
		;

		$params = array(
			'DEL',
			$locale->getId()
		);

		$typeFilter = $this->getRequestParameter('filter');
		if ( ! empty($typeFilter)) {
			$query .= 'AND l.parentPageApplicationId = ? ';
			$params[] = $typeFilter;
		}

		$query .= 'ORDER BY l.'.$auditConfiguration->getRevisionTypeFieldName().' DESC';

		foreach ($reader->getConnection()->fetchAll($query, $params) as $row) {
			$response[] = array(
				'id' => $row['id'],
				'date' => \DateTime::createFromFormat('Y-m-d H:i:s', $row['timestamp'])->format(DATE_ATOM),
				'title' => $row['title'],
				'revision' => $row['rev'],
				'author' => $row['username'],
				'path' => $row['path_part'],
				'template' => $row['template_id'],
				// TODO: do we need this?
				'localized' => true,
				'published' => false,
				'scheduled' => false,
			);
		}

		return $response;
	}
}
