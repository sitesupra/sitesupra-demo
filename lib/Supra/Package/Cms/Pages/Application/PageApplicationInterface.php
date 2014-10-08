<?php

namespace Supra\Package\Cms\Pages\Application;

use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\ApplicationLocalization;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Supra\Uri\Path;

/**
 * Interface for page applications
 */
interface PageApplicationInterface
{
	const INSERT_POLICY_PREPEND = 'prepend';
	const INSERT_POLICY_APPEND = 'append';

	public function getId();

	public function getTitle();

	public function getIcon();
	
	public function getAllowChildren();

	public function getNewChildInsertPolicy();

	/**
	 * Renerates the base path for page localization.
	 * Must NOT start and end with "/"
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(PageLocalization $pageLocalization);

	/**
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em);

	/**
	 * @param Entity\ApplicationLocalization $applicationLocalization
	 */
	public function setApplicationLocalization(ApplicationLocalization $applicationLocalization);

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
