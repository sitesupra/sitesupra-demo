<?php

namespace Supra\Package\Cms\FileStorage\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\NestedSet\Event\NestedSetEventArgs;
use Supra\Core\NestedSet\Event\NestedSetEvents;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Entity\Abstraction\File as FileAbstraction;

class FilePathChangeListener implements EventSubscriber, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
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
		$unitOfWork = $eventArgs->getEntityManager()->getUnitOfWork();

		$inserts = $unitOfWork->getScheduledEntityInsertions();
		$updates = $unitOfWork->getScheduledEntityUpdates();
		$entities = $inserts + $updates;
		
		foreach ($entities as $entity) {
			if ( ! $entity instanceof FileAbstraction) {
				continue;
			}

			$this->regeneratePathForEntity($entity, $eventArgs);
		}
	}

	/**
	 * This is called for public schema when structure is changed in draft schema
	 * @param NestedSetEventArgs $eventArgs
	 */
	public function nestedSetPostMove(NestedSetEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();

		if ($entity instanceof FileAbstraction) {
			$this->regeneratePathForEntity($entity, $eventArgs);
		}
	}

	protected function regeneratePathForEntity($entity, $eventArgs)
	{
		$descendants = $entity->getDescendants();

		if ( ! empty($descendants)) {
			foreach ($descendants as $descendant) {
				$this->generateEntityFilePath($descendant, $eventArgs);
			}
		}

		$this->generateEntityFilePath($entity, $eventArgs);
	}

	/**
	 * @return \Doctrine\Common\Persistence\ObjectManager
	 */
	protected function getEntityManager()
	{
		return $this->container->getDoctrine()->getManager();
	}

	/**
	 * @return UnitOfWork
	 */
	protected function getUnitOfWork()
	{
		return $this->getEntityManager()->getUnitOfWork();
	}

	private function generateEntityFilePath($fileEntity, $eventArgs)
	{
		$fileStorage = $this->container['cms.file_storage'];
		$em = $eventArgs->getEntityManager();
		$unitOfWork = $em->getUnitOfWork();
		
		$pathGenerator = $fileStorage->getFilePathGenerator();
		
		$pathGenerator->generateFilePath($fileEntity);
		
		$filePath = $fileEntity->getPathEntity();

		$fileMetadata = $em->getClassMetadata($fileEntity->CN());
		$filePathMetadata = $em->getClassMetadata($filePath->CN());

		if ($unitOfWork->getEntityState($fileEntity, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
			$em->persist($fileEntity);
		}

		if ($unitOfWork->getEntityState($filePath, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
			$em->persist($filePath);
		}

		if ($unitOfWork->getEntityChangeSet($filePath)) {
			$unitOfWork->recomputeSingleEntityChangeSet($filePathMetadata, $filePath);
		} else {
			$unitOfWork->computeChangeSet($filePathMetadata, $filePath);
		}

		if ($unitOfWork->getEntityChangeSet($fileEntity)) {
			$unitOfWork->recomputeSingleEntityChangeSet($fileMetadata, $fileEntity);
		} else {
			$unitOfWork->computeChangeSet($fileMetadata, $fileEntity);
		}
	}
}
