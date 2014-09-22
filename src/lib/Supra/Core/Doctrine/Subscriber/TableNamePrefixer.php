<?php

namespace Supra\Core\Doctrine\Subscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

/**
 * Adds prefix for the supra entity tables automatically
 */
class TableNamePrefixer implements EventSubscriber
{
	/**
	 * Suffix used for all tablenames
	 * @var string
	 */
	private $prefix;
	
	/**
	 * Entity namespace to prefix
	 * @var string
	 */
	private $entityNamespace;

	/**
	 * Add prefix for tablenames for entities from the namespace provided
	 * @param string $prefix
	 * @param string $prefixNamespace
	 */
	public function __construct($prefix, $entityNamespace = 'Supra')
	{
		$this->prefix = $prefix;
		$this->entityNamespace = trim($entityNamespace, '\\');
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::loadClassMetadata);
	}
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$name = &$classMetadata->table['name'];
		
		// Add supra prefix for entities matching the namespace
		if ($this->entityNamespace !== '') {
			if ($classMetadata->name != $this->entityNamespace 
					&& strpos($classMetadata->name, $this->entityNamespace . '\\') !== 0) {
				return;
			}
		}
		
		// Add supra prefix for entities if not added already
		if (strpos($name, $this->prefix) !== 0) {
			$name = $this->prefix . $name;
		}
	}
}
