<?php

namespace Supra\FileStorage\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\NestedSet\Event\NestedSetEventArgs;
use Supra\NestedSet\Event\NestedSetEvents;
use Supra\FileStorage\Entity;

class FilePathGenerator implements EventSubscriber
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
				$this->regeneratePath($descendant);
			}
		}

		$this->regeneratePath($entity);
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

	public function regeneratePath(Entity\Abstraction\File $entity)
	{
		$filePath = $entity->getPathEntity();

		$filePath->setSystemPath(self::getSystemPath($entity));
		$filePath->setWebPath($this->getWebPath($entity));

		$entity->setPathEntity($filePath);
		$filePath->setId($entity->getId());

		$entityMetadata = $this->em->getClassMetadata($entity->CN());
		$filePathMetadata = $this->em->getClassMetadata($filePath->CN());

		if ($this->unitOfWork->getEntityState($entity, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
			$this->em->persist($entity);
		}

		if ($this->unitOfWork->getEntityState($filePath, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
			$this->em->persist($filePath);
		}

		if ($this->unitOfWork->getEntityChangeSet($filePath)) {
			$this->unitOfWork->recomputeSingleEntityChangeSet($filePathMetadata, $filePath);
		} else {
			$this->unitOfWork->computeChangeSet($filePathMetadata, $filePath);
		}

		if ($this->unitOfWork->getEntityChangeSet($entity)) {
			$this->unitOfWork->recomputeSingleEntityChangeSet($entityMetadata, $entity);
		} else {
			$this->unitOfWork->computeChangeSet($entityMetadata, $entity);
		}
	}

	protected function getWebPath(Entity\Abstraction\File $file)
	{
		$fileStorage = \Supra\ObjectRepository\ObjectRepository::getFileStorage($this);

		if ($file->isPublic()) {

			$externalUrlBase = $fileStorage->getExternalUrlBase();

			if ( ! empty($externalUrlBase)) {
				$path = $externalUrlBase . DIRECTORY_SEPARATOR;
			} else {
				$path = '/' . str_replace(SUPRA_WEBROOT_PATH, '', $fileStorage->getExternalPath());
			}

			// Fix backslash on Windows
			$path = str_replace(array('//', "\\"), '/', $path);

			// get file dir
			$pathNodes = $file->getAncestors(0, false);
			$pathNodes = array_reverse($pathNodes);

			foreach ($pathNodes as $pathNode) {
				/* @var $pathNode Entity\Folder */
				$path .= rawurlencode($pathNode->getFileName()) . '/';
			}

			// Encode the filename URL part
			$path .= rawurlencode($file->getFileName());

			return $path;
		}
	}

	public static function getSystemPath(Entity\Abstraction\File $file)
	{
		$pathNodes = $file->getAncestors(0, true);
		$items = array();
		foreach ($pathNodes as $node) {
			array_unshift($items, $node->__toString());
		}
		$path = implode(DIRECTORY_SEPARATOR, $items);

		return $path;
	}

}
