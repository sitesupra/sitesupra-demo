<?php

namespace Supra\Cms\CrudManager\Form;

use Supra\Cms\CrudManager\CrudManagerAbstractAction;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Validator\Exception\RuntimeException;
use Supra\Cms\Exception\CmsException;

class FormAction extends CrudManagerAbstractAction
{

	public function saveAction()
	{
		$request = $this->getRequest();
		if ( ! $request->isPost()) {
			throw new CmsException(null, 'Only post request allowed');
		}

		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);

		$post = $request->getPost();
		
		$record = null;
		$recordId = $post->get('id');

		if ( ! empty($recordId)) {
			$record = $repo->findOneById($recordId);
		} else {
			if ($repo->isCreatable()) {
				$record = new $configuration->entity;
			} else {
				return null;
			}
		}

		if ( ! $record instanceof $configuration->entity) {
			throw new CmsException(null, 'Could not find any record with id #' . $recordId);
		}

		ObjectRepository::setCallerParent($record, $this);
		
		$em->persist($record);
		
		//setting new values
		$output = $record->setEditValues($post);
		
		$em->flush();
		
		$recordId = $record->getId();
		$recordBefore = $post->get('record-before', null);
		
		if($repo->isSortable()) {
			$this->move($recordId, $recordBefore);
		}

		$response = $this->getResponse();
		$response->setResponseData($output);
	}

	public function deleteAction()
	{
		$request = $this->getRequest();
		if ( ! $request->isPost()) {
			throw new CmsException(null, 'Only post request allowed');
		}

		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);

		if ($repo->isDeletable()) {
			throw new CmsException(null, 'It\'s not allowed to remove records. Change configuration');
		}

		$post = $request->getPost();
		$recordId = $post->get('id');
		if (empty($recordId)) {
			throw new CmsException(null, 'Record id is empty');
		}

		$record = $repo->findOneById($recordId);
		if ( ! $record instanceof $configuration->entity) {
			throw new CmsException(null, 'Can\'t find record to delete');
		}

		$em->remove($record);
		$em->flush();
	}

	/**
	 * Performs sorting action
	 */
	public function sortAction()
	{
		$request = $this->getRequest();
		if ( ! $request->isPost()) {
			throw new CmsException(null, 'Only post request allowed');
		}

		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);

		if ( ! $repo->isSortable()) {
			throw new CmsException(null, 'Manager is not sortable. Change configuration');
		}

		if ( ! property_exists($configuration->entity, 'position')) {
			throw new \Exception('Looks like there is no $position property in ' . $configuration->entity . ' entity.');
		}

		$post = $request->getPost();

		$recordId = $post->get('id');
		$recordBefore = $post->get('record-before', null);
		if (empty($recordId)) {
			throw new CmsException(null, 'Empty record id');
		}
		
		$this->move($recordId, $recordBefore);
	}

	/**
	 * Moving current record
	 * @param string $recordId
	 * @param string $recordBefore
	 */
	protected function move($recordId, $recordBefore)
	{
		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$beforePosition = 0;
		
		$em->beginTransaction();

		try {
			$queryResult = array();
			if ( ! empty($recordBefore)) {
				
				// check if nothing has been changed
				$query = $em->createQuery("SELECT e.id as position FROM {$configuration->entity} e WHERE e.id = :recordId");
				$query->setParameter('recordId', $recordId);
				$beforePosition = $query->getSingleScalarResult();
				
				if($beforePosition == $recordBefore) {
					return;
				}
				
				$query = $em->createQuery("SELECT e.position as position FROM {$configuration->entity} e WHERE e.id = :before");
				$query->setParameter('before', $recordBefore);
				$beforePosition = $query->getSingleScalarResult();
			}

			// This is because of MySQL unique contraint failure if position is unique
			$query = $em->createQuery("UPDATE {$configuration->entity} e SET e.position = - e.position WHERE e.position > :beforePosition");
			$query->setParameter('beforePosition', $beforePosition);
			$queryResult = $query->execute();

			$query = $em->createQuery("UPDATE {$configuration->entity} e SET e.position = - e.position + 1 WHERE e.position < - :beforePosition");
			$query->setParameter('beforePosition', $beforePosition);
			$queryResult = $query->execute();

			$query = $em->createQuery("UPDATE {$configuration->entity} e SET e.position = :newPosition WHERE e.id = :currentRecord");
			$query->setParameter('newPosition', $beforePosition + 1);
			$query->setParameter('currentRecord', $recordId);
			$queryResult = $query->execute();
			
			$em->flush();
		} catch (\Exception $e) {
			$em->rollback();
			
			throw $e;
		}
		
		$em->commit();
	}

}
