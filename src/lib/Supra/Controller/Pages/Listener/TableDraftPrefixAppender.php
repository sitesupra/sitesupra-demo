<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Adds draft prefix for the tables from the schema for draft connection only
 */
class TableDraftPrefixAppender
{
	/**
	 * Draft table name prefix
	 */
	const TABLE_PREFIX = '_draft';
	
	/**
	 * Entities to be versioned
	 * @var array
	 */
	private static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\PageData',
		'Supra\Controller\Pages\Entity\TemplateData',
		'Supra\Controller\Pages\Entity\Abstraction\Data',
		'Supra\Controller\Pages\Entity\PagePlaceHolder',
		'Supra\Controller\Pages\Entity\TemplatePlaceHolder',
		'Supra\Controller\Pages\Entity\Abstraction\PlaceHolder',
		'Supra\Controller\Pages\Entity\Abstraction\Block',
		'Supra\Controller\Pages\Entity\PageBlock',
		'Supra\Controller\Pages\Entity\TemplateBlock',
		'Supra\Controller\Pages\Entity\BlockProperty',
	);
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		$name = &$classMetadata->table['name'];
		
		if (in_array($className, static::$versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}
}
