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
}
