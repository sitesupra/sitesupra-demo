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

class VersionedAnnotationListener extends VersionedTableMetadataListener
{
	const ANNOTATION_NS = 'Supra\Controller\Pages\Annotation\\';
	
	/**
	 * @var boolean
	 */
	private $isOnCreateCall = false;
	
	/**
	 * @var array
	 */
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

		// Namespace is defined by entity manager name
		$namespace = $eventArgs->getEntityManager()->_mode;
		$reader->setAnnotationNamespaceAlias(self::ANNOTATION_NS, $namespace);
		
		AnnotationRegistry::registerFile(SUPRA_LIBRARY_PATH . '/Supra/Controller/Pages/Annotation/Annotation.php');
		
	    $class = $metadata->getReflectionClass();
		
		
		// TODO: unset constraints by annotations
		if ($className == Entity\Abstraction\Localization::CN() && ($namespace == 'Trash' || $namespace == 'History')) {
			unset($metadata->table['uniqueConstraints']['locale_path_idx']);
		}
		/*
		$skipUniqueConstraintsAnnotation = $reader->getClassAnnotation($class, 'Supra\Controller\Pages\Annotation\SkipUniqueConstraints');
		if ($skipUniqueConstraintsAnnotation instanceof Annotation\SkipUniqueConstraints
				&& $this->isOnCreateCall) {
			
			$this->skipUniqueConstraints[] = $className;
		}
		
		if (in_array($className, $this->skipUniqueConstraints) && $this->isOnCreateCall) {
			unset($metadata->table['uniqueConstraints']);
		}
		 */
		
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
				
				} else if ($annotation instanceof Annotation\Column) {
					
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
				
					
				} else if ($annotation instanceof Annotation\OneToOne) {
					
					if ( $metadata->isMappedSuperclass 
							&& ! $property->isPrivate() 
							|| $metadata->isInheritedField($property->name)
							|| $metadata->isInheritedAssociation($property->name)) {
						continue;
					}

					$mapping = array();
					$mapping['fieldName'] = $propertyName;
	                $mapping['targetEntity'] = $annotation->targetEntity;
					$mapping['joinColumns'] = array();
					$mapping['mappedBy'] = $annotation->mappedBy;
					$mapping['inversedBy'] = $annotation->inversedBy;
					$mapping['cascade'] = $annotation->cascade;
					$mapping['orphanRemoval'] = $annotation->orphanRemoval;
					$mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $annotation->fetch);
					$metadata->mapOneToOne($mapping);
					
				} else if ($annotation instanceof Annotation\SkipForeignKey
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
	}
	
	public function setAsCreateCall($createCall = true)
	{
		$this->isOnCreateCall = $createCall;
	}
	
	public function isOnCreateMode() 
	{
		return $this->isOnCreateCall;
	}

}
