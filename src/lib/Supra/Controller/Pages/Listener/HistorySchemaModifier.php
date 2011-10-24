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

class HistorySchemaModifier extends VersionedTableMetadataListener
{
	const TABLE_PREFIX = '_history';
	const ANNOTATION_NS = 'Supra\Controller\Pages\Annotation\\';
	
	private $isOnCreateCall = false;
	
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
        $reader->setAnnotationNamespaceAlias(self::ANNOTATION_NS, 'History');
		
		AnnotationRegistry::registerFile(SUPRA_LIBRARY_PATH . '/Supra/Controller/Pages/Annotation/Annotation.php');
		
	    $class = $metadata->getReflectionClass();
		
		$properties = $class->getProperties();
		foreach($properties as $property) {
			
			$propertyName = $property->getName();
			$propertyAnnotations = $reader->getPropertyAnnotations($property);
			foreach($propertyAnnotations as $annotation) {
				
				if ($annotation instanceof Annotation\InheritOnCreate && $this->isOnCreateCall) {
					if (isset($metadata->associationMappings[$propertyName]['inherited'])) {
						unset($metadata->associationMappings[$propertyName]['inherited']);	
					}
					if (isset($metadata->fieldMappings[$propertyName]['inherited'])) {
						unset($metadata->fieldMappings[$propertyName]['inherited']);
					}
				}
				
				if ($annotation instanceof Annotation\Column) {
					
					if ( ! in_array($className, $versionedEntities)
							|| $metadata->isMappedSuperclass 
							&& ! $property->isPrivate() 
							|| $metadata->isInheritedField($property->name)
							|| $metadata->isInheritedAssociation($property->name)) {
						continue;
					}
					
					$mapping['fieldName'] = $propertyName;
					
					if ($annotation->type == null) {
						throw MappingException::propertyTypeIsRequired($className, $property->getName());
					}

					$mapping['type'] = $annotation->type;
					$mapping['length'] = $annotation->length;
					$mapping['precision'] = $annotation->precision;
					$mapping['scale'] = $annotation->scale;
					$mapping['nullable'] = $annotation->nullable;
					$mapping['unique'] = $annotation->unique;
					if ($annotation->options) {
						$mapping['options'] = $annotation->options;
					}

					if (isset($annotation->name)) {
						$mapping['columnName'] = $annotation->name;
					}

					if (isset($annotation->columnDefinition)) {
						$mapping['columnDefinition'] = $annotation->columnDefinition;
					}

					$idAnnotation = $reader->getPropertyAnnotation($property, 'Supra\Controller\Pages\Annotation\Id');
					if ($idAnnotation instanceof Annotation\Id) {
						$mapping['id'] = true;
					}
					$metadata->mapField($mapping);
				}
				
				
				if ($annotation instanceof Annotation\ManyToOne) {
					
					if ( in_array($className, $versionedEntities)
							&& ! $metadata->isMappedSuperclass) {
						
						if ( isset($metadata->fieldMappings[$propertyName]) 
								|| isset($metadata->associationMappings[$propertyName]) ) {
							
							continue;
						}
						
						$mapping = array();
						$joinColumns = array();

						$mapping['fieldName'] = $propertyName;

						$joinColumnAnnot = $reader->getPropertyAnnotation($property, 'Supra\Controller\Pages\Annotation\JoinColumn');
						if ($joinColumnAnnot instanceof Annotation\JoinColumn) {
							 $joinColumns[] = array(
								'name' => $joinColumnAnnot->name,
								'referencedColumnName' => $joinColumnAnnot->referencedColumnName,
								'unique' => $joinColumnAnnot->unique,
								'nullable' => $joinColumnAnnot->nullable,
								'onDelete' => $joinColumnAnnot->onDelete,
								'onUpdate' => $joinColumnAnnot->onUpdate,
								'columnDefinition' => $joinColumnAnnot->columnDefinition,
							);
						}

						$idAnnotation = $reader->getPropertyAnnotation($property, 'Supra\Controller\Pages\Annotation\Id');
						if ($idAnnotation instanceof Annotation\Id) {
							$mapping['id'] = true;
						}

						$mapping['joinColumns'] = $joinColumns;
						$mapping['cascade'] = $annotation->cascade;
						$mapping['inversedBy'] = $annotation->inversedBy;
						$mapping['targetEntity'] = $annotation->targetEntity;
						$mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $annotation->fetch);
						$metadata->mapManyToOne($mapping);
					}
				}
				
				if ($annotation instanceof Annotation\SkipForeignKey
						|| ($annotation instanceof Annotation\SkipForeignKeyOnCreate && ($this->isOnCreateCall))) {

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

		/**
		 * Removing unique constraint
		 * TODO: should process this from annotation, now simply hardcoded
		 */
		if ($className == Entity\Abstraction\Localization::CN()) {
			unset($metadata->table['uniqueConstraints']['locale_path_idx']);
		}
		
		// This hack will allow us to fetch block property metadata using id+revision column
		if ($className == Entity\BlockPropertyMetadata::CN()) {
			if (isset($metadata->associationMappings['blockProperty'])) {
				$metadata->associationMappings['blockProperty']['targetToSourceKeyColumns']['revision'] = 'revision';
			}
		}
		
		/**
		 * Add table prefixes
		 */
		$name = &$metadata->table['name'];
		if (in_array($className, $versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}
	
	public function setAsCreateCall()
	{
		$this->isOnCreateCall = true;
	}
	

}
