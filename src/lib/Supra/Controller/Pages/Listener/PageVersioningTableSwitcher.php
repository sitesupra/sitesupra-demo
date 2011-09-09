<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Selects draft table schema for draft connection and published for publish
 */
class PageVersioningTableSwitcher
{
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$name = &$classMetadata->table['name'];
		
		// Add "su_" prefix for supra entities
		if (strpos($classMetadata->namespace, 'Supra\\') === 0 && strpos($name, 'su_') !== 0) {
			$name = 'su_' . $name;
		}
		
	}
}
