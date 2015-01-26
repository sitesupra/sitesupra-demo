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

namespace Supra\Package\Cms\Pages\Application;

use Doctrine\ORM\EntityManager;
use Supra\Package\Cms\Entity\ApplicationPage;
use Supra\Package\Cms\Entity\ApplicationLocalization;

class PageApplicationManager
{
	/**
	 * @var PageApplicationInterface[]
	 */
	protected $applications = array();

	public function registerApplication(PageApplicationInterface $application)
	{
		if ($this->hasApplication($application->getId())) {
			throw new \LogicException(sprintf(
					'Application [%s] is already in collection.',
					$application->getId()
			));
		}

		$this->applications[$application->getId()] = $application;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasApplication($key)
	{
		return isset($this->applications[$key]);
	}

	/**
	 * @param string $key
	 * @return PageApplicationInterface
	 * @throws \InvalidArgumentException
	 */
	public function getApplication($key)
	{
		if (! $this->hasApplication($key)) {
			throw new \InvalidArgumentException(sprintf(
					'Application [%s] is missing.',
					$key
			));
		}

		return $this->applications[$key];
	}

	/**
	 * @return PageApplicationInterface[]
	 */
	public function getAllApplications()
	{
		return $this->applications;
	}

	/**
	 * @TODO: can we avoid use of entity manager here?
	 *
	 * @param ApplicationLocalization $page
	 * @param EntityManager $entityManager
	 * @return PageApplication
	 */
	public function createApplicationFor(
			ApplicationLocalization $localization,
			EntityManager $entityManager
	) {
		$applicationPage = $localization->getMaster();

		if (! $applicationPage instanceof ApplicationPage) {
			throw new \UnexpectedValueException(
					'Expecting ApplicationPage to be master of [%], [%s] received',
					$localization->getId(),
					get_class($applicationPage)
			);
		}

		$appId = $applicationPage->getApplicationId();

		// @TODO: this looks lame
		$application = clone $this->getApplication($appId);

		$application->setApplicationLocalization($localization);
		$application->setEntityManager($entityManager);

		return $application;
	}
}