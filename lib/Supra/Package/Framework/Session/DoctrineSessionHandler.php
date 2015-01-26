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

namespace Supra\Package\Framework\Session;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Framework\Entity\SessionData;

class DoctrineSessionHandler implements \SessionHandlerInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function close()
	{
		return true;
	}

	public function destroy($session_id)
	{
		$entity = $this->container->getDoctrine()
			->getRepository('Framework:SessionData')
			->findOneBy(array('sessionId' => $session_id));

		if ($entity) {
			$this->container->getDoctrine()->getManager()->remove($entity);
			$this->container->getDoctrine()->getManager()->flush();
		}
	}

	public function gc($maxlifetime)
	{
		$this->container->getDoctrine()->getManager()
			->createQueryBuilder()
			->delete()
			->from('Framework:SessionData', 'd')
			->where('d.timestamp < :date')
			->setParameter('date', time() - $maxlifetime)
			->getQuery()
			->execute();
	}

	public function open($save_path, $session_id)
	{
		return true;
	}

	public function read($session_id)
	{
		$entity = $this->container->getDoctrine()
			->getRepository('Framework:SessionData')
			->findOneBy(array('sessionId' => $session_id));

		if ($entity) {
			return $entity->getData();
		} else {
			return '';
		}
	}

	public function write($session_id, $session_data)
	{
		$entity = $this->container->getDoctrine()
			->getRepository('Framework:SessionData')
			->findOneBy(array('sessionId' => $session_id));

		if ($entity) {
			$entity->setData($session_data);
			$entity->setTimestamp(time());
			$this->container->getDoctrine()->getManager()->flush();
		} else {
			$entity = new SessionData();
			$entity->setSessionId($session_id);
			$entity->setData($session_data);

			$this->container->getDoctrine()->getManager()->persist($entity);
			$this->container->getDoctrine()->getManager()->flush();
		}
	}

}
