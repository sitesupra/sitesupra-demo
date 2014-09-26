<?php

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