<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Supra\Database\Doctrine\Type\Sha1HashType;

class HistorySchemeModifier extends VersionedTableMetadataListener
{
	const TABLE_PREFIX = '_history';
	
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\AbstractPage',
		'Supra\Controller\Pages\Entity\ApplicationPage',
		'Supra\Controller\Pages\Entity\Page',
		'Supra\Controller\Pages\Entity\Template',
	);
		
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$versionedEntities = array_merge(self::$versionedEntities, parent::$versionedEntities);
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		$name = &$classMetadata->table['name'];
		
		/*
		 * Removing reference (foreign key) from PageData->Template
		 * by removing `template` from associationMappings and 
		 * adding it as a simple field
		 * TODO: field params are hardcoded, should we copy them somehow
		 * from associationsMappings?
		 * TODO: is there another way to make it?
		 */
		if ($className == 'Supra\Controller\Pages\Entity\PageLocalization') {
			unset($classMetadata->associationMappings['template']);
			$classMetadata->mapField(array(
				'fieldName' => 'template',
				'type' => Sha1HashType::NAME,
				'columnName' => 'template_id',
			));
		}	
		
		/*
		 * Removing reference between blocks and block properties
		 */
		if ($className == 'Supra\Controller\Pages\Entity\BlockProperty') {
			unset($classMetadata->associationMappings['block']);
			$classMetadata->mapField(array(
				'fieldName' => 'block',
				'type' => Sha1HashType::NAME,
				'columnName' => 'block_id',
			));
		}	
		
		/*
		 * Adding one more ID to store multiple records of similar page data,
		 * (dissalow merging them into single record);
		 */
		if (in_array($className, $versionedEntities)) {
			if ( ! isset($classMetadata->fieldMappings['revision'])) {
				$classMetadata->mapField(array(
					'id' => true,
					'fieldName' => 'revision',
					'type' => Sha1HashType::NAME,
					'columnName' => 'revision_id',
				));
			} else {
				/* 
				 * We must remove inheritance "mark" or `revision_id` field
				 * will be added only to AbstractPage table
				 */
				unset($classMetadata->fieldMappings['revision']['inherited']);
			}
		}	
		
		if ($className == 'Supra\Controller\Pages\Entity\Abstraction\Localization') {
			unset($classMetadata->table['uniqueConstraints']['locale_path_idx']);
		}
		
		if (in_array($className, $versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}

}
