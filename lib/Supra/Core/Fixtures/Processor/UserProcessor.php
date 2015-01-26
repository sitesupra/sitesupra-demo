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

namespace Supra\Core\Fixtures\Processor;

use Nelmio\Alice\ProcessorInterface;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\CmsAuthentication\Entity\User;

class UserProcessor implements ProcessorInterface, ContainerAware
{
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}


	public function preProcess($object)
	{
		if ($object instanceof User) {
			$encoder = $this->container['cms_authentication.encoder_factory']->getEncoder($object);

			$object->setPassword($encoder->encodePassword($object->getPassword(), $object->getSalt()));
		}
	}

	public function postProcess($object) {}

}