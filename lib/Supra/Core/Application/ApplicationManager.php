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

namespace Supra\Core\Application;

class ApplicationManager
{
	/**
	 * @var ApplicationInterface[]
	 */
	protected $applications = array();

	/**
	 * @var ApplicationInterface
	 */
	protected $currentApplication;

	/**
	 * @return ApplicationInterface
	 * @throws \LogicException
	 */
	public function getCurrentApplication()
	{
		if (!$this->currentApplication) {
			throw new \LogicException('No current application selected. You need to call "ApplicationManager::selectApplication" somewhere in our code!');
		}

		return $this->currentApplication;
	}

	public function registerApplication(ApplicationInterface $application)
	{
		$this->applications[$application->getId()] = $application;
	}

	public function selectApplication($name)
	{
		foreach ($this->applications as $id => $application) {
			if ($id == $name) {
				$this->currentApplication = $application;
				return;
			}
		}

		throw new \RuntimeException(sprintf('No reference for application "%s"', $name));
	}

	public function getApplications()
	{
		return $this->applications;
	}
}