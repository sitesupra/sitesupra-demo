<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

class HistoryRevisionListener
{
	/**
	 * @var string
	 */
	private $_revisionId;
	
	/**
	 * @param LifecycleEventArgs $eventArgs 
	 */
	public function prePersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$revisionId = $entity->getRevisionId();
		
		if ( ! empty($revisionId)) {
			if ($revisionId != $this->_revisionId) {
				$this->_revisionId = $revisionId;
			}
		} else if ( ! empty($this->_revisionId)) {
			$entity->setRevisionId($this->_revisionId);
		}
	}

}
