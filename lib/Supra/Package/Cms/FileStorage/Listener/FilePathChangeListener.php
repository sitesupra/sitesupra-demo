<?php

namespace Supra\Package\Cms\FileStorage\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Supra\NestedSet\Event\NestedSetEventArgs;
use Supra\NestedSet\Event\NestedSetEvents;
use Supra\FileStorage\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\FileStorage;

class FilePathChangeListener implements EventSubscriber
{
	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var UnitOfWork
	 */
	private $unitOfWork;
	
	/**
	 * @var FileStorage
	 */
	private $fileStorage;

	/**
	 * This class is used by path regeneration command as well
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em = null)
	{
		if ( ! is_null($em)) {
			$this->em = $em;
			$this->unitOfWork = $em->getUnitOfWork();
		}
	}

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			NestedSetEvents::nestedSetPostMove,
		);
	}

	/**
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();

		$inserts = $this->unitOfWork->getScheduledEntityInsertions();
		$updates = $this->unitOfWork->getScheduledEntityUpdates();
		$entities = $inserts + $updates;
		
		foreach ($entities as $entity) {
			if ( ! $entity instanceof Entity\Abstraction\File) {
				continue;
			}

			$this->regeneratePathForEntity($entity);
		}
	}

	protected function regeneratePathForEntity($entity)
	{
		$descendants = $entity->getDescendants();

		if ( ! empty($descendants)) {
			foreach ($descendants as $descendant) {
				$this->generateEntityFilePath($descendant);
			}
		}

		$this->generateEntityFilePath($entity);
	}
	
	private function generateEntityFilePath($fileEntity)
	{
		if ($this->fileStorage === null) {
			$this->fileStorage = ObjectRepository::getFileStorage($this);
		}
		
		$pathGenerator = $this->fileStorage->getFilePathGenerator();
		
		$pathGenerator->generateFilePath($fileEntity);
		
		$filePath = $fileEntity->getPathEntity();
		
		$fileMetadata = $this->em->getClassMetadata($fileEntity->CN());
		$filePathMetadata = $this->em->getClassMetadata($filePath->CN());

		if ($this->unitOfWork->getEntityState($fileEntity, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
			$this->em->persist($fileEntity);
		}

		if ($this->unitOfWork->getEntityState($filePath, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
			$this->em->persist($filePath);
		}

		if ($this->unitOfWork->getEntityChangeSet($filePath)) {
			$this->unitOfWork->recomputeSingleEntityChangeSet($filePathMetadata, $filePath);
		} else {
			$this->unitOfWork->computeChangeSet($filePathMetadata, $filePath);
		}

		if ($this->unitOfWork->getEntityChangeSet($fileEntity)) {
			$this->unitOfWork->recomputeSingleEntityChangeSet($fileMetadata, $fileEntity);
		} else {
			$this->unitOfWork->computeChangeSet($fileMetadata, $fileEntity);
		}
	}

	/**
	 * This is called for public schema when structure is changed in draft schema
	 * @param NestedSetEventArgs $eventArgs
	 */
	public function nestedSetPostMove(NestedSetEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();
		$entity = $eventArgs->getEntity();

		if ($entity instanceof Entity\Abstraction\File) {
			$this->regeneratePathForEntity($entity);
		}
	}
}
