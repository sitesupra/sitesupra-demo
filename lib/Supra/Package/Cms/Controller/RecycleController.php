<?php

namespace Supra\Package\Cms\Controller;

use SimpleThings\EntityAudit\AuditManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;

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

		$query = 'SELECT l.id, l.title, l.'.$auditConfiguration->getRevisionFieldName().', l.template_id, l.path_part, '.
			'r.username, r.timestamp '.
			'FROM '.$auditConfiguration->getTablePrefix().$localizationMeta->table['name'].$auditConfiguration->getTableSuffix().' l '.
			'INNER JOIN '.$auditConfiguration->getRevisionTableName().' r '.
			'ON r.id = l.'.$auditConfiguration->getRevisionFieldName().' '.
			'WHERE l.'.$auditConfiguration->getRevisionTypeFieldName().' = ? '.
				'AND l.locale = ?'
				. 'AND l.discr = ?'
		;

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
}
