<?php

namespace Supra\Cms\CrudManager\Data;

use Supra\Cms\CrudManager\CrudManagerAbstractAction;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\CrudManager\CrudRepositoryInterface;
use Supra\Cms\CrudManager\CrudRepositoryWithFilterInterface;

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
		
		$configuration = $this->getConfiguration();
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);

		if ( ! $repo instanceof CrudRepositoryInterface) {
			throw new \LogicException("Crud entity's repository must implement CrudRepositoryInterface");
		}
		
		// selecting all data 
		$qb = $em->createQueryBuilder();
		$qb->select('e');
		$qb->from($configuration->entity, 'e');

		// set ordering and additional parameters
		$repo->setAdditionalQueryParams($qb);

		if ($repo instanceof CrudRepositoryWithFilterInterface) {
			$filter = $this->getRequestInput();
			$repo->applyFilters($qb, $filter);
		}
		
		// if crud manager is sortable, then we overwrite orderings
		if ($repo->isSortable()) {
			$qb->orderBy('e.position', 'asc');
		}

		$countQueryBuilder = clone($qb);
		/* @var $countQueryBuilder \Doctrine\ORM\QueryBuilder */

		$qb->setFirstResult($offset);
		$qb->setMaxResults($resultsPerRequest);

		$results = $qb->getQuery()
				->getResult();
		
		$countQueryBuilder->select('COUNT(e)');
		$totalCount = $countQueryBuilder->getQuery()->getSingleScalarResult();

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

	public function sourcedataAction()
	{
		$requestQuery = $this->getRequest()
				->getQuery();
		
		$sourceId = $requestQuery->get('sourceId');
		
		// @TODO: validate $sourceId value

		$repository = $this->getRepository();

		$methodName = sprintf('load%sSourceData', ucfirst(mb_strtolower($sourceId)));

		// @TODO: it shouldn't be called on Repository
		if ( ! method_exists($repository, $methodName)) {
			throw new \InvalidArgumentException("There is no corresponding method to data for [{$sourceId}].");
		}

		$responseData = $repository->$methodName($requestQuery);

		$this->getResponse()
				->setResponseData($responseData);
	}

	public function configurationAction()
	{
		$localeId = $this->getLocale()->getId();
		$configuration = $this->getConfiguration();
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);

		if ( ! $repo instanceof CrudRepositoryInterface) {
			throw new \LogicException("Crud entity's repository must implement CrudRepositoryInterface");
		}

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
				'description' => $fieldObject->getDescription(),
				'defaultValue' => $fieldObject->getDefaultValue($localeId),
			) + (array) $fieldObject->getAdditionalParameters();
			
			$fields[$key] = $data;
		}

		$filters = null;

		if ($repo instanceof CrudRepositoryWithFilterInterface) {
			$filters = array();
			$filtersObjects = $repo->getFilters();

			foreach ($filtersObjects as $filterId => $filterObject) {
				/* @var $filterObject Editable\EditableInterface */

				$data = array(
					'label' => $filterObject->getLabel(),
					'type' => $filterObject->getEditorType(),
					'description' => $filterObject->getDescription(),
					'defaultValue' => $filterObject->getDefaultValue($localeId),
				) + (array) $filterObject->getAdditionalParameters();

				$filters[$filterId] = $data;
			}
		}

		$output = array(
			array(
				'attributes' => $attributes,
				'fields' => $fields,
				'ui_list' => array_keys($repo->getListFields()),
				'ui_edit' => array_keys($repo->getEditableFields()),
				'lists' => array(),
				'filters' => $filters ?: null,
			)
		);

		$response = $this->getResponse();
		$response->setResponseData($output);
	}

}
