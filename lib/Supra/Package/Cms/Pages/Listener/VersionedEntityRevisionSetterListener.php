<?php

namespace Supra\Package\Cms\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntityInterface;

//use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
//use Supra\Controller\Pages\Event\AuditEvents;
//use Supra\Controller\Pages\Entity\PageRevisionData;
//use Supra\ObjectRepository\ObjectRepository;
//use Supra\Controller\Pages\Event\PageEventArgs;
//use Supra\Controller\Pages\Entity\BlockProperty;
//use Supra\Controller\Pages\Entity\Abstraction\Block;
//use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
//use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

class VersionedEntityRevisionSetterListener implements EventSubscriber
{
//	const pagePreDeleteEvent = 'preDeleteEvent';
//	const pagePostDeleteEvent = 'postDeleteEvent';
//
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $entityManager;

	/**
	 * @var \Doctrine\ORM\UnitOfWork
	 */
	private $uow;
	
	/**
	 * @var array
	 */
	private $visitedEntities = array();
//
//	/**
//	 * @var boolean
//	 */
//	private $_pageRestoreState = false;
//
//	/**
//	 *
//	 * @var boolean
//	 */
//	private $_pageDeleteState = false;
//
//	/**
//	 * @var Supra\User\Entity\User
//	 */
//	private $user;
//
//	/**
//	 * @var string
//	 */
//	private $referenceId;
//
//	/**
//	 * @var string
//	 */
//	private $globalElementReferenceId;
//
//	/**
//	 * @var string
//	 */
//	private $revision;
//
//	/**
//	 * @var string
//	 */
//	private $revisionInfo;
	
	/**
	 * @inheritDoc
	 */
	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
//			AuditEvents::pagePreRestoreEvent,
//			AuditEvents::pagePostRestoreEvent,
//			AuditEvents::pagePreEditEvent,
//			AuditEvents::pageContentEditEvent,
//			AuditEvents::localizationPreRestoreEvent,
//			AuditEvents::localizationPostRestoreEvent,
//			self::pagePreDeleteEvent,
//			self::pagePostDeleteEvent,
		);
	}
	
	/**
	 * Listen all entity insertions and updates performed by Draft entity manager,
	 * update their revision Id
	 * 
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->setup($eventArgs);
		
		foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
			
			if (! $entity instanceof VersionedEntityInterface) {
				continue;
			}

			$changeSet = $this->uow->getEntityChangeSet($entity);
			
			foreach($changeSet as $fieldName => $fieldValue) {

				// skip some cases:
				//   - when changeset contains only associated collection
				//   - when Localization lock/unlock action is performed (lock column value update)
				if ($fieldValue instanceof PersistentCollection
						|| ($entity instanceof Localization && $fieldName == 'lock')
						|| $fieldName == 'revision'
						|| ($fieldValue[0] instanceof \DateTime && $fieldValue[0] == $fieldValue[1])
//						|| ($fieldValue[0] instanceof \Supra\Editable\EditableAbstraction && $fieldValue[0] == $fieldValue[1])
						// FIXME: workaround for PageLocalization::resetPath() called on page delete action
//						|| $this->_pageDeleteState && $fieldName == 'path'
						) {
					unset($changeSet[$fieldName]);
				}
			}

			if (! empty($changeSet)) {				
				$this->setRevisionRecursive($entity, $this->generateRevision());
			}
		}
		
		foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
			if ($entity instanceof VersionedEntityInterface) {
				$this->setRevisionRecursive($entity, $this->generateRevision());
			}
		}
	}
	
	/** 
	 * @param VersionedEntityInterface $entity
	 * @param string $newRevisionId
	 * @param bool $nestedCall
	 */
	private function setRevisionRecursive(VersionedEntityInterface $entity, $revision)
	{
		$splHash = spl_object_hash($entity);

		// ignore already visited
		if (isset($this->visitedEntities[$splHash])) {
			return;
		}

		$this->visitedEntities[$splHash] = true;

		$currentRevision = $entity->getRevision();

		// ignore if revision has not changed
		if ($revision === $currentRevision) {
			return;
		}

		// ignore not managed
		if ($this->uow->getEntityState($entity) !== UnitOfWork::STATE_MANAGED) {
			return;
		}
		
		$entity->setRevision($revision);
		
		$this->uow->propertyChanged($entity, 'revision', $currentRevision, $revision);
		
		$this->uow->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata($entity::CN()), $entity);

		if (! $this->uow->isScheduledForUpdate($entity)) {
			$this->uow->scheduleForUpdate($entity);
		}
				
		if (($parent = $entity->getVersionedParent()) !== null) {
			$this->setRevisionRecursive($parent, $revision);
		}
	}

	/**
	 * Setups the internal state.
	 *
	 * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
	 */
	private function setup(OnFlushEventArgs $eventArgs)
	{
		$this->visitedEntities = array();

		$this->entityManager = $eventArgs->getEntityManager();
		$this->uow = $this->entityManager->getUnitOfWork();
	}

	/**
	 * @return string
	 */
	private function generateRevision()
	{
		return Entity::generateId();
	}

//	public function pagePreRestoreEvent()
//	{
//		$this->_pageRestoreState = true;
//	}
//
//	public function pagePostRestoreEvent()
//	{
//		 $this->_pageRestoreState = false;
//	}
//
//	public function preDeleteEvent()
//	{
//		$this->_pageDeleteState = true;
//	}
//
//	public function postDeleteEvent()
//	{
//		$this->_pageDeleteState = false;
//	}
//
//	public function localizationPreRestoreEvent()
//	{
//		$this->_pageRestoreState = true;
//	}
//
//	public function localizationPostRestoreEvent()
//	{
//		$this->_pageRestoreState = false;
//	}
//
//	public function pagePreEditEvent(PageEventArgs $eventArgs)
//	{
//		$this->referenceId = $eventArgs->getProperty('referenceId');
//		$this->globalElementReferenceId = $eventArgs->getProperty('globalElementReferenceId');
//	}
//	
//	private function createRevisionData($entity, $type = PageRevisionData::TYPE_ELEMENT_EDIT)
//	{
//		$blockName = null;
//
//		// Need to search block title even if revision is created because
//		// referenced element might be in the changeset before metadata.
//		if (is_null($this->revision) || $this->revision->getElementTitle() === null) {
//			$blockName = $this->findBlockName($entity);
//		}
//
//		if (is_null($this->revision)) {
//
//			$em = ObjectRepository::getEntityManager('#public');
//
//			$revision = new PageRevisionData();
//
//			$className = $entity::CN();
//			if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
//				$className = \Doctrine\Common\Util\ClassUtils::getRealClass($entity::CN());
//			}
//
//			$revision->setElementName($className);
//			$revision->setElementId($entity->getId());
//
//			$revision->setType($type);
//			$revision->setReferenceId($this->referenceId);
//			$revision->setAdditionalInfo($this->revisionInfo);
//
//			if ( ! empty($this->globalElementReferenceId)) {
//				$revision->setGlobalElementReferenceId($this->globalElementReferenceId);
//			}
//
//			$userId = null;
//			if ($this->user instanceof \Supra\User\Entity\User) {
//				$userId = $this->user->getId();
//			}
//			$revision->setUser($userId);
//
//			$em->persist($revision);
//			$em->flush();
//
//			$this->revision = $revision;
//		}
//
//		if ( ! is_null($blockName)) {
//			$this->revision->setElementTitle($blockName);
//
//			$em = ObjectRepository::getEntityManager('#public');
//			$em->flush();
//		}
//
//		return $this->revision;
//	}
//	
//	/**
//	 * Finds block title by element changed.
//	 * Doesn't work for metadata referenced elements, but currently they are
//	 * saved togather with metadata elements.
//	 * @param mixed $entity
//	 * @return string
//	 */
//	private function findBlockName($entity)
//	{
//		$block = null;
//
//		switch (true) {
//			case ($entity instanceof BlockPropertyMetadata):
//				$block = $entity->getBlockProperty()
//						->getBlock();
//				break;
//
//			case ($entity instanceof BlockProperty):
//				$block = $entity->getBlock();
//				break;
//
//			case ($entity instanceof Block):
//				$block = $entity;
//				break;
//		}
//
//		if (is_null($block)) {
//			return;
//		}
//
//		$blockName = null;
//		$entity = null;
//
//		if ( ! is_null($block)) {
//			$componentClass = $block->getComponentClass();
//			$componentConfiguration = ObjectRepository::getComponentConfiguration($componentClass);
//
//			if ($componentConfiguration instanceof BlockControllerConfiguration) {
//				$blockName = $componentConfiguration->title;
//			}
//		}
//
//		return $blockName;
//	}
//	
//	/**
//	 * @param PageEventArgs $eventArgs
//	 */
//	public function pageContentEditEvent(PageEventArgs $eventArgs)
//	{
//		$this->revisionInfo = $eventArgs->getRevisionInfo();
//	}
}
