<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Entity;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

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
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em);
	
	/**
	 * @param Entity\ApplicationLocalization $applicationLocalization
	 */
	public function setApplicationLocalization(Entity\ApplicationLocalization $applicationLocalization);

	/**
	 * Application might provide virtual filter folders
	 * @param QueryBuilder $queryBuilder
	 * @param string $filter
	 * @return array
	 */
	public function getFilterFolders(QueryBuilder $queryBuilder, $filterName);
	
	/**
	 * Apply application filter
	 * @param QueryBuilder $queryBuilder
	 * @param string $filter
	 */
	public function applyFilters(QueryBuilder $queryBuilder, $filter);
}
