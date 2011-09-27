<?php

namespace Supra\Database\Doctrine\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Adds supra suffix for the supra entity tables automatically
 */
class TableNameGenerator
{
	const SUPRA_SUFFIX = 'su_';
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$name = &$classMetadata->table['name'];
		
		// Add supra suffix for supra entities if not added already
		if (strpos($classMetadata->namespace, 'Supra\\') === 0 && strpos($name, static::SUPRA_SUFFIX) !== 0) {
			$name = static::SUPRA_SUFFIX . $name;
		}
	}
}
