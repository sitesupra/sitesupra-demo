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
use Doctrine\ORM\QueryBuilder;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\ApplicationLocalization;
use Supra\Package\Cms\Uri\Path;

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
