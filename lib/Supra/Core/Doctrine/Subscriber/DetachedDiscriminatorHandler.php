<?php

namespace Supra\Core\Doctrine\Subscriber;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Supra\Database\Annotation\DetachedDiscriminators;
use Supra\Database\Annotation\DetachedDiscriminatorValue;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DetachedDiscriminatorHandler implements EventSubscriber
{
	/**
	 * @var SimpleAnnotationReader
	 */
	protected $annotationReader;

	/**
	 * @var bool
	 */
	protected $ignoreDetachedDiscriminators = false;
	
	/**
	 * @var array
	 */
	protected $discriminatorMaps = array();

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents()
	{
		return array(Events::loadClassMetadata);
	}

	/**
	 * @return SimpleAnnotationReader
	 */
	public function getAnnotationReader()
	{
		if (empty($this->annotationReader)) {
			$this->annotationReader = new SimpleAnnotationReader();
			$this->annotationReader->addNamespace('Supra\Database\Annotation');
		}

		return $this->annotationReader;
	}

	/**
	 * * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$reflection = $eventArgs->getClassMetadata()
				->getReflectionClass();
		
		if (empty($reflection)) {
			return;
		}

		$reader = $this->getAnnotationReader();
		
		$annotation = $reader->getClassAnnotation($reflection, DetachedDiscriminatorValue::CN());
		if ( ! empty($annotation)) {
			$this->handleDetachedDisciminatorValue($eventArgs, $annotation);
		} 

		$annotation = $reader->getClassAnnotation($reflection, DetachedDiscriminators::CN());
		if ( ! empty($annotation)) {
			$this->handleDetachedDiscriminators($eventArgs);
		} 
	}

	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	protected function handleDetachedDisciminatorValue(LoadClassMetadataEventArgs $eventArgs, $annotation)
	{
		//\Log::debug('QQQQ: ', $eventArgs->getClassMetadata()->name);

		$classMetadata = $eventArgs->getClassMetadata();
		/* @var $classMetadata ClassMetadataInfo */

		$discriminatorValue = $annotation->value;

		$classMetadata->addDiscriminatorMapClass($discriminatorValue, $classMetadata->name);

		$em = $eventArgs->getEntityManager();

		$f = $em->getMetadataFactory();

		foreach ($classMetadata->parentClasses as $parentClass) {

			$parentMetadata = $em->getClassMetadata($parentClass);
			/* @var $parentMetadata ClassMetadataInfo */

			$parentMetadata->addDiscriminatorMapClass($discriminatorValue, $classMetadata->name);
			
			$this->discriminatorMaps[$parentMetadata->name] = $parentMetadata->discriminatorMap;

			$id = $parentClass . '$CLASSMETADATA';
			$f->getCacheDriver()->save($id, $parentMetadata);
		}

		
	}

	/**
	 * @param LoadClassMetadataEventArgs $eventArgs 
	 */
	protected function handleDetachedDiscriminators(LoadClassMetadataEventArgs $eventArgs)
	{
		//\Log::debug('WWWW: ', $eventArgs->getClassMetadata()->name);

		if ($this->ignoreDetachedDiscriminators) {
			return;
		}

		$this->ignoreDetachedDiscriminators = true;

		$em = $eventArgs->getEntityManager();

		$metadataFactory = $em->getMetadataFactory();

		$metadataFactory->getAllMetadata();

		$this->ignoreDetachedDiscriminators = false;
		
		$classMetadata = $eventArgs->getClassMetadata();
		
		if (isset($this->discriminatorMaps[$classMetadata->name])) {	
			$localDiscriminatorMap = $this->discriminatorMaps[$classMetadata->name];
			$classMetadata->setDiscriminatorMap($localDiscriminatorMap);
		}
	}
	
}