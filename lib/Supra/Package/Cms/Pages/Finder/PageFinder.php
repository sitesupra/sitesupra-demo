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

use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Repository\PageAbstractRepository;

/**
 * PageFinder
 */
class PageFinder extends AbstractFinder
{
	/**
	 * @var \Supra\Core\NestedSet\DoctrineRepository
	 */
	private $nestedSetRepository;

	/**
	 * @var \Supra\Core\NestedSet\SearchCondition\DoctrineSearchCondition
	 */
	private $searchCondition;

	/**
	 * @return PageAbstractRepository
	 */
	protected function getRepository()
	{
		return $this->em->getRepository(Page::CN());
	}
	
	/**
	 * @return \Supra\Core\NestedSet\DoctrineRepository
	 */
	public function getNestedSetRepository()
	{
		if (is_null($this->nestedSetRepository)) {
			$repository = $this->getRepository();

			if ( ! $repository instanceof PageAbstractRepository) {
				throw new \UnexpectedValueException(sprintf(
						'Expecting PageAbstractRepository only, [%s] received.',
						get_class($repository)
				));
			}

			$this->nestedSetRepository = $repository->getNestedSetRepository();
		}

		return $this->nestedSetRepository;
	}

	/**
	 * @return \Supra\Core\NestedSet\SearchCondition\DoctrineSearchCondition
	 */
	public function getSearchCondition()
	{
		if (is_null($this->searchCondition)) {
			$nestedSetRepository = $this->getNestedSetRepository();
			$this->searchCondition = $nestedSetRepository->createSearchCondition();
		}

		return $this->searchCondition;
	}

	protected function doGetQueryBuilder()
	{
		$searchCondition = $this->getSearchCondition();
		$nestedSetRepository = $this->getNestedSetRepository();

		$queryBuilder = $nestedSetRepository->createSearchQueryBuilder($searchCondition);

		return $queryBuilder;
	}

	public function addLevelFilter($min = 0, $max = null)
	{
		$this->getSearchCondition()
				->levelGreaterThanOrEqualsTo($min);

		if ( ! is_null($max)) {
			$this->getSearchCondition()
					->levelLessThanOrEqualsTo($max);
		}
	}

	public function addFilterByParent(AbstractPage $page, $minDepth = 1, $maxDepth = null)
	{
		// Make sure it's positive
		$minDepth = max($minDepth, 0);

		$parentLevel = $page->getLevel();
		$this->addLevelFilter($parentLevel + $minDepth, is_null($maxDepth) ? null : $parentLevel + $maxDepth);

		// Can change the left/right by min depth without loosing any results
		$leftLimit = $page->getLeftValue() + $minDepth;
		$rightLimit = $page->getRightValue() - $minDepth;

		$this->getSearchCondition()
				->leftGreaterThanOrEqualsTo($leftLimit);

		// Left will be definitely strictly less than the right limit
		$this->getSearchCondition()
				->leftLessThan($rightLimit);
	}
	
	public function addFilterByChild(AbstractPage $page, $minDepth = 0, $maxDepth = null)
	{
		$level = $page->getLevel();
		
		if ($minDepth < 0) {
			$minDepth = $level + $minDepth;
		}
		
		if ($maxDepth < 0) {
			$maxDepth = $level + $maxDepth;
		}
		
		$this->addLevelFilter($minDepth, is_null($maxDepth) ? null : $maxDepth);
		
		$leftLimit = $page->getLeftValue();
		$rightLimit = $page->getRightValue();
		
		$this->getSearchCondition()
				->leftLessThanOrEqualsTo($leftLimit);

		$this->getSearchCondition()
				->rightGreaterThanOrEqualsTo($rightLimit);
	}

	public function getAncestors(Page $page)
	{
		$left = $page->getLeftValue();
		$right = $page->getRightValue();

		$searchCondition = $this->getSearchCondition();
		$repository = $this->getNestedSetRepository();

		// Will include the self node if required in the end
		$searchCondition->leftLessThan($left)
				->rightGreaterThan($right);

		//$searchCondition->levelGreaterThanOrEqualsTo($level);

		$orderRule = $repository->createSelectOrderRule()
				->byLevelDescending();

		$ancestors = $repository->search($searchCondition, $orderRule);

		return $ancestors;
	}

	/**
	 * @return LocalizationFinder
	 */
	public function createLocalizationFinder()
	{
		return new LocalizationFinder($this);
	}
}
