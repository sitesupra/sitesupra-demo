<?php

namespace Supra\Controller\Pages\Finder;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Repository;

/**
 * PageFinder
 */
class PageFinder extends AbstractFinder
{
	/**
	 * @var \Supra\NestedSet\DoctrineRepository
	 */
	private $nestedSetRepository;
	
	/**
	 * @var \Supra\NestedSet\SearchCondition\DoctrineSearchCondition
	 */
	private $searchCondition;
	
	/**
	 * @return \Supra\NestedSet\DoctrineRepository
	 */
	public function getNestedSetRepository()
	{
		if (is_null($this->nestedSetRepository)) {
			$repository = $this->em->getRepository(Entity\Page::CN());

			if ( ! $repository instanceof Repository\PageAbstractRepository) {
				throw new \Supra\Controller\Pages\Exception\ConfigurationException("Wrong repository received");
			}

			$this->nestedSetRepository = $repository->getNestedSetRepository();
		}
		
		return $this->nestedSetRepository;
	}
	
	/**
	 * @return \Supra\NestedSet\SearchCondition\DoctrineSearchCondition
	 */
	public function getSearchCondition()
	{
		if (is_null($this->searchCondition)) {
			$nestedSetRepository = $this->getNestedSetRepository();
			$this->searchCondition = $nestedSetRepository->createSearchCondition();
		}
		
		return $this->searchCondition;
	}
	
	public function getQueryBuilder()
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
	
	public function addFilterByParent(Entity\Abstraction\AbstractPage $page, $minDepth = 1, $maxDepth = null)
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
}
