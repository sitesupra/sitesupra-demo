<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Entity;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;

/**
 * Interface for page applications
 */
interface PageApplicationInterface
{
	/**
	 * Renerates the base path for page localization.
	 * Must NOT start and end with "/"
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization);
	
	/**
	 * Whether the application page has it's own path
	 * @return boolean
	 */
	public function hasPath();
	
	/**
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em);
	
	/**
	 * @param Entity\ApplicationLocalization $applicationLocalization
	 */
	public function setApplicationLocalization(Entity\ApplicationLocalization $applicationLocalization);

}
