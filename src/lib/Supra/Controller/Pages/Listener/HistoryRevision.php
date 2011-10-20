<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

class HistoryRevision
{
	protected $_revisionData;
	
	public function __construct ($revisionData)
	{
		$this->_revisionData = $revisionData;
	}
	
	public function prePersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$entity->setRevisionData($this->_revisionData);
	}

	public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            if ($entity instanceof Entity\BlockPropertyMetadata) {
				// Reverting back modifications between blockProperty + blockPropertyMetadata
				// or DB insert query will contain properties in incorrect orded
				$metadata = $em->getClassMetadata($entity::CN());
				unset($metadata->associationMappings['blockProperty']['targetToSourceKeyColumns']['revision']);
            }
        }
    }
}
