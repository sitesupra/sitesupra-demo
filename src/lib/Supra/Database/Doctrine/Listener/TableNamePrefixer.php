<?php

namespace Supra\Database\Doctrine\Listener;

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
	 * @param string $prefix
	 */
	public function __construct($prefix)
	{
		$this->prefix = $prefix;
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
		
		// Add supra prefix for supra entities if not added already
		if (strpos($classMetadata->namespace, 'Supra\\') === 0 && strpos($name, $this->prefix) !== 0) {
			$name = $this->prefix . $name;
		}
	}
}
