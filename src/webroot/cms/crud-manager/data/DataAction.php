<?php

namespace Supra\Cms\CrudManager\Data;

use Supra\Cms\CrudManager\CrudManagerAbstractAction;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;

class DataAction extends CrudManagerAbstractAction
{

	public function datalistAction()
	{
		$resultsPerRequest = $this->getRequest()->getParameter('resultsPerRequest');
		if ( ! is_numeric($resultsPerRequest)) {
			$resultsPerRequest = 40;
		}

		$offset = $this->getRequest()->getParameter('offset');
		if ( ! is_numeric($offset)) {
			$offset = 0;
		}
		
		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);
		
		// selecting all data 
		$qb = $em->createQueryBuilder();
		$qb->select('e');
		$qb->from($configuration->entity, 'e');
		
		// set ordering and additional parameters
		$repo->setAdditionalQueryParams($qb);
		
		$qb->setFirstResult($offset);
		$qb->setMaxResults($resultsPerRequest);
		
		// if crud manager is sortable, then we overwrite orderings
		if ($repo->isSortable()) {
			$qb->orderBy('e.position', 'asc');
		}	
		$query = $qb->getQuery();
		
		$results = $query->getResult();
		
		$query = $em->createQuery("SELECT COUNT(e) as totalCount FROM {$configuration->entity} e");
		$totalCount = $query->getSingleScalarResult();

		$data = array();
		foreach ($results as $result) {
			$data[] = $result->getEditValues();
		}

		$output = array(
			'offset' => $offset,
			'total' => $totalCount,
			'results' => $data,
		);

		$response = $this->getResponse();
		$response->setResponseData($output);
	}

	public function configurationAction()
	{
		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);
		/* @var $repo Gjensidige\Branches\Repository\BranchesCrudRepository */

		$entityParts = explode('\\', $configuration->entity);
		$managerId = mb_strtolower(end($entityParts));

		$attributes = array(
			'id' => $managerId,
			'title' => $configuration->title,
			'delete' => $repo->isDeletable(),
			'create' => $repo->isCreatable(),
			'sortable' => $repo->isSortable(),
			'locale' => $repo->isLocalized(),
		);

		//TODO: something is wrong here
		$fieldObjects = array('id' => new Editable\Hidden('Id')) + $repo->getEditableFields()
				+ $repo->getListFields();
		$fields = array();
		
		foreach ($fieldObjects as $key => $fieldObject) {
			/* @var $fieldObject Editable\EditableInterface */
			
			$data = array(
				'label' => $fieldObject->getLabel(),
				'type' => $fieldObject->getEditorType(),
			);
			
			$data['defaultValue'] = $fieldObject->getDefaultValue();
			
			$data = array_merge($data, $fieldObject->getAdditionalParameters());
			$fields[$key] = $data;
		}

		$output = array(
			array(
				'attributes' => $attributes,
				'fields' => $fields,
				'ui_list' => array_keys($repo->getListFields()),
				'ui_edit' => array_keys($repo->getEditableFields()),
				'lists' => array(),
			)
		);

		$response = $this->getResponse();
		$response->setResponseData($output);
	}

}
