<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Database\Doctrine\Type\Sha1HashType;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Supra\Controller\Pages\Annotation;
use Supra\Controller\Pages\Entity;

class HistorySchemaModifier extends VersionedTableMetadataListener
{
	const TABLE_PREFIX = '_history';
	const ANNOTATION_NS = 'Supra\Controller\Pages\Annotation\\';
	
	private $mappingStorage = array();
	private $isOnCreateCall = false;
	
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\AbstractPage',
		'Supra\Controller\Pages\Entity\Page',
		'Supra\Controller\Pages\Entity\Template',
		'Supra\Controller\Pages\Entity\ApplicationPage',
		'Supra\Controller\Pages\Entity\GroupPage',
	);
			
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$versionedEntities = array_merge(self::$versionedEntities, parent::$versionedEntities);
		$metadata = $eventArgs->getClassMetadata();
		$em = $eventArgs->getEntityManager();
		$className = $metadata->name;
			
        $reader = new AnnotationReader;
        $reader->setIgnoreNotImportedAnnotations(true);
        $reader->setAnnotationNamespaceAlias(self::ANNOTATION_NS, 'History');
		
		AnnotationRegistry::registerFile(SUPRA_LIBRARY_PATH . '/Supra/Controller/Pages/Annotation/HistoryAnnotation.php');
		
	    $class = $metadata->getReflectionClass();
        //$annotations = $reader->getClassAnnotations($class);
		
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
				
				// $propertyName == 'revision' is workaround for revision field
				// as it is impossible to properly add mapped as OneToOne/ManyToMany & etc. field inside tables as primary key
				// TODO: this "hack" should be fixed, or defined as another annotation 
				if ($annotation instanceof Annotation\SkipForeignKey && ($this->isOnCreateCall) || $propertyName == 'revision') {

					if (isset($metadata->associationMappings[$propertyName])) {
						
						$joinColumn = array_shift($metadata->associationMappings[$propertyName]['joinColumns']);
						
						$this->mappingStorage[$propertyName] = $metadata->associationMappings[$propertyName];
						unset($metadata->associationMappings[$propertyName]);
						
						$metadata->mapField(array(
							'fieldName' => $propertyName,
							'type' => Sha1HashType::NAME,
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
		
		/**
		 * Add table prefixes
		 */
		$name = &$metadata->table['name'];
		if (in_array($className, $versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}
	
	/**
	 * @param LifecycleEventArgs $eventArgs 
	 */
	public function prePersist(LifecycleEventArgs $eventArgs) 
	{
		return;
		$entity = $eventArgs->getEntity();
		$metadata = $eventArgs->getEntityManager()->getClassMetadata($entity::CN());
		
		$reader = new AnnotationReader;
        $reader->setIgnoreNotImportedAnnotations(true);
        $reader->setAnnotationNamespaceAlias(self::ANNOTATION_NS, 'History');
		
	    $class = $metadata->getReflectionClass();
		
		$properties = $class->getProperties();
		foreach($properties as $property) {
			
			$propertyName = $property->getName();
			$propertyAnnotations = $reader->getPropertyAnnotations($property);
			foreach($propertyAnnotations as $annotation) {
				/**
				 * Return back 
				 */
				if ($annotation instanceof Annotation\InheritOnCreate) {
					if (isset ($metadata->associationMappings[$propertyName])) {
						$metadata->associationMappings[$propertyName]['inherited'] = $metadata->rootEntityName;
					}
					else if (isset ($metadata->fieldMappings[$propertyName])) {
						$metadata->fieldMappings[$propertyName]['inherited'] = $metadata->rootEntityName;
					}
				}
				
			}
		}
	}
	
	public function postLoad(LifecycleEventArgs $eventArgs)
	{
		return;
		$entity = $eventArgs->getEntity();
		$metadata = $eventArgs->getEntityManager()->getClassMetadata($entity::CN());
		
		
		if ($entity instanceof \Supra\Controller\Pages\Entity\PageLocalization) {
			1+1;
		}
		
		$reader = new AnnotationReader;
        $reader->setIgnoreNotImportedAnnotations(true);
        $reader->setAnnotationNamespaceAlias(self::ANNOTATION_NS, 'History');
		
	    $class = $metadata->getReflectionClass();
		
		$properties = $class->getProperties();
		foreach($properties as $property) {
			
			$propertyName = $property->getName();
			$propertyAnnotations = $reader->getPropertyAnnotations($property);
			foreach($propertyAnnotations as $annotation) {
				if ($annotation instanceof Annotation\SkipForeignKey) {
					
					if ($propertyName == 'template') {
						//$tpl1 = $property->getValue();
						$tpl2 = $entity->getTemplate();
					}
					
				}
			}
		}
	}
	
	public function setAsCreateCall()
	{
		$this->isOnCreateCall = true;
	}
	

}
