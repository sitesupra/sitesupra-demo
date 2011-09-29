<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Request\PageRequest;

class HistoryRevision extends VersionedTableMetadataListener
{
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\AbstractPage',
		'Supra\Controller\Pages\Entity\Page',
		'Supra\Controller\Pages\Entity\Template',
	);
	
	protected $_revisionId;
	
	public function __construct ($revisionId) {
		$this->_revisionId = $revisionId;
	}
	
	public function prePersist(LifecycleEventArgs $eventArgs) {
		$entity = $eventArgs->getEntity();
		$metadata = $eventArgs->getEntityManager()->getClassMetadata($entity::CN());
		
		$metadata->fieldMappings['revision']['inherited'] = $metadata->rootEntityName;
		$entity->setRevisionId($this->_revisionId);
		
		/*
		 * Return associations back or `template_id` and `block_id` fields will
		 * contain __toString representation of joined class, not ID
		 */
		if (isset($metadata->fieldMappings['template'])) {
			unset($metadata->fieldMappings['template']);
			$metadata->mapManyToOne(array(
				'targetEntity' => PageRequest::TEMPLATE_ENTITY, 
				'fieldName' => 'template',
				'joinColumns' => array(array(
					'name' => 'template_id',
					'referencedColumnName' => 'id',
				)),
			));
		}
		
		if (isset($metadata->fieldMappings['block'])) {
			unset($metadata->fieldMappings['block']);
			$metadata->mapManyToOne(array(
				'targetEntity' => PageRequest::TEMPLATE_BLOCK_ENTITY,
				'fieldName' => 'block',
				'joinColumns' => array(array(
					'name' => 'block_id',
					'referencedColumnName' => 'id',
				)),
			));
		}
	}
	
}
