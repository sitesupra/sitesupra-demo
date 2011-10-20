<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

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
            if ($entity instanceof \Supra\Controller\Pages\Entity\BlockPropertyMetadata) {
				// Reverting back modifications between blockProperty + blockPropertyMetadata
				// or DB insert query will contain properties in incorrect orded
				$metadata = $em->getClassMetadata($entity::CN());
				unset($metadata->associationMappings['blockProperty']['targetToSourceKeyColumns']['revision']);
            }
        }
    }
}
