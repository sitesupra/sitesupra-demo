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

namespace Supra\Package\Cms\Pages\Finder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Supra\Core\Doctrine\Hydrator\ColumnHydrator;

/**
 * AbstractFinder
 */
abstract class AbstractFinder
{
	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var boolean
	 */
	private $cache = true;

	/**
	 * @var array
	 */
	private $customConditions = array();

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function getEntityManager()
	{
		return $this->em;
	}

	public function addCustomCondition($customCondition)
	{
		$this->customConditions[] = $customCondition;
	}

	/**
	 * @return QueryBuilder
	 * @throws \LogicException
	 */
	final public function getQueryBuilder()
	{
		$qb = $this->doGetQueryBuilder();

		if ( ! $qb instanceof QueryBuilder) {
			throw new \LogicException("Inner method doGetQueryBuilder didn't return QueryBuilder");
		}

		// Custom conditions
		foreach ($this->customConditions as $customCondition) {
			$qb->andWhere($customCondition);
		}


		// @FIXME: caching is disabled

//		// Wrap only if not wrapped already
//		if ($this->cache && ! $qb instanceof CachedQueryBuilderWrapper) {
//			$qb = new CachedQueryBuilderWrapper($qb, PageController::CACHE_GROUP_NAME);
//		}
//
//		if ( ! $this->cache && $qb instanceof CachedQueryBuilderWrapper) {
//			$qb = $qb->getWrappedQueryBuilder();
//		}

		return $qb;
	}

	public function disableCache()
	{
		$this->cache = false;
	}

	abstract protected function doGetQueryBuilder();

	public final function getResult()
	{
		$query = $this->getQueryBuilder()
				->getQuery();

		$result = $query->getResult();
		$filteredResult = $this->filterResult($result);

		return $filteredResult;
	}

	/**
	 * @param array $result
	 * @return array
	 */
	protected function filterResult($result)
	{
		return $result;
	}

	public function getTotalCount($qb, $groupBy)
	{
		$qbTotal = clone($qb);
		/* @var $qbTotal QueryBuilder */

		$totalCount = $qbTotal->select('COUNT(DISTINCT ' . $groupBy . ') as cnt')
				->getQuery()
				->getSingleScalarResult();

		return $totalCount;
	}

	public function getPaginatorResult($qb, $groupBy, $limit, $offset = 0)
	{
		$qb = clone($qb);
		/* @var $qb QueryBuilder */
		$qbDistinct = clone($qb);
		/* @var $qbDistinct QueryBuilder */

		$ids = $qbDistinct->select('DISTINCT ' . $groupBy)
				->setMaxResults($limit)
				->setFirstResult($offset)
				->getQuery()
				->getResult(ColumnHydrator::HYDRATOR_ID);

		if ( ! empty($ids)) {
			$result = $qb->andWhere($qb->expr()->in($groupBy, ':ids'))
					->setParameter('ids', $ids, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
					->getQuery()
					->getResult();
		} else {
			$result = array();
		}

		$filteredResult = $this->filterResult($result);

		return $filteredResult;
	}
}
