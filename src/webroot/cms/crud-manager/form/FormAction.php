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

		$em->persist($record);

		$output = $record->setEditValues($post);

		$em->flush();

		$this->move($record->getId(), $post->get('record-before'));

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
		$recordBefore = $post->get('record-before');
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

		$queryResult = array();
		if ( ! is_null($recordBefore)) {
			$query = $em->createQuery("SELECT e.position as position FROM {$configuration->entity} e WHERE e.id = :before");
			$query->setParameter('before', $recordBefore);
			$queryResult = $query->getResult();
			$beforePosition = $queryResult[0]['position'];
		}

		if (empty($queryResult)) {
			$query = $em->createQuery("SELECT MIN(e.position) as position FROM {$configuration->entity} e");
			$queryResult = $query->getResult();
			$beforePosition = $queryResult[0]['position'] - 1;
		}

		$query = $em->createQuery("UPDATE {$configuration->entity} e SET e.position = e.position + 1 WHERE e.position > :beforePosition");
		$query->setParameter('beforePosition', $beforePosition);
		$queryResult = $query->execute();

		$query = $em->createQuery("UPDATE {$configuration->entity} e SET e.position = :newPosition WHERE e.id = :currentRecord");
		$query->setParameter('newPosition', $beforePosition + 1);
		$query->setParameter('currentRecord', $recordId);
		$queryResult = $query->execute();
		
	}

}