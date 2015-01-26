<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
