<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Database\Doctrine\Type;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Supra\Controller\Pages\Annotation;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Mapping\MappingException;

class TrashSchemaModifier extends VersionedTableMetadataListener
{
	const TABLE_PREFIX = '_trash';
	const ANNOTATION_NS = 'Supra\Controller\Pages\Annotation\\';
	
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\AbstractPage',
		'Supra\Controller\Pages\Entity\Page',
		'Supra\Controller\Pages\Entity\Template',
		'Supra\Controller\Pages\Entity\ApplicationPage',
		'Supra\Controller\Pages\Entity\GroupPage',
		'Supra\Controller\Pages\Entity\TemplateLayout',
	);
			
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$versionedEntities = array_merge(self::$versionedEntities, parent::$versionedEntities);
		$metadata = $eventArgs->getClassMetadata();
		$className = $metadata->name;
			
        $reader = new AnnotationReader;
        $reader->setIgnoreNotImportedAnnotations(true);
        $reader->setAnnotationNamespaceAlias(self::ANNOTATION_NS, 'Trash');
		
		AnnotationRegistry::registerFile(SUPRA_LIBRARY_PATH . '/Supra/Controller/Pages/Annotation/Annotation.php');
		
	    $class = $metadata->getReflectionClass();
		
		$properties = $class->getProperties();
		foreach($properties as $property) {
			
			$propertyName = $property->getName();
			$propertyAnnotations = $reader->getPropertyAnnotations($property);
			foreach($propertyAnnotations as $annotation) {
				
				if ($annotation instanceof Annotation\SkipForeignKey) {

					if (isset($metadata->associationMappings[$propertyName])) {
						$joinColumn = array_shift($metadata->associationMappings[$propertyName]['joinColumns']);
						unset($metadata->associationMappings[$propertyName]);

						$metadata->mapField(array(
							'fieldName' => $propertyName,
							'type' => !is_null($annotation->type) ? $annotation->type : $joinColumn['type'],
							'columnName' => $joinColumn['name'],
						));
					}
				}
			}
		}

		if ($className == Entity\Abstraction\Localization::CN()) {
			unset($metadata->table['uniqueConstraints']['locale_path_idx']);
		}
		
		$name = &$metadata->table['name'];
		if (in_array($className, $versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}

}
