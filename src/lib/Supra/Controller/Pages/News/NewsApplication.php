<?php

namespace Supra\Controller\Pages\News;

use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Entity;
use DateTime;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\NestedSet\SearchCondition\SearchConditionInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Supra\NestedSet\DoctrineRepository;

/**
 * News page application
 */
class NewsApplication implements PageApplicationInterface
{
	/**
	 * @var EntityManager
	 */
	protected $em;
	
	/**
	 * @var Entity\ApplicationLocalization
	 */
	protected $applicationLocalization;
	
	/**
	 * @var boolean
	 */
	protected $showInactivePages;
	
	/**
	 * How many items to show in collapse view
	 * @var int
	 */
	protected $collapsedLimit = 5;
	
	/**
	 * Show all news in collapsed mode if total count less then $hideExpandIfLessThen
	 * @var int
	 */
	protected $hideExpandIfLessThen = 7;
	
	/**
	 * {@inheritdoc}
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}
	
	/**
	 * {@inheritdoc}
	 * @param Entity\ApplicationLocalization $localization
	 */
	public function setApplicationLocalization(Entity\ApplicationLocalization $applicationLocalization)
	{
		$this->applicationLocalization = $applicationLocalization;
	}

	/**
	 * {@inheritdoc}
	 * @param boolean $show
	 */
	public function showInactivePages($show)
	{
		$this->showInactivePages = $show;
	}
	
	/**
	 * {@inheritdoc}
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization)
	{
		$creationTime = $pageLocalization->getCreationTime();

		// Shouldn't we set some other path for not published publications?
		if ( ! $creationTime instanceof DateTime) {
			$creationTime = new DateTime();
		}
		
		$pathString = $creationTime->format('Y/m/d');
		$path = new Path($pathString);
		
		return $path;
	}

	/**
	 * News application hasn't path
	 * @return boolean
	 */
	public function hasPath()
	{
		return false;
	}
	
	/**
	 * @return DoctrineRepository 
	 */
	public function getNestedSetRepository()
	{
		$pageRep = $this->em->getRepository(Entity\Page::CN());
		/* @var $pageRep PageRepository */
		
		$nestedSet = $pageRep->getNestedSetRepository();
		/* @var $nestedSet DoctrineRepository */
		
		return $nestedSet;
	}
	
	/**
	 * Reserves and returns DQL query parameter key
	 * @return int
	 */
	public function getNextParameterKey()
	{
		$nextKey = $this->getNestedSetRepository()
				->increaseParameterOffset();
		
		return $nextKey;
	}
	
	/**
	 * Creates generic query builder
	 * @return QueryBuilder
	 */
	public function createQueryBuilder()
	{
		$page = $this->applicationLocalization->getMaster();
		$lft = $page->getLeftValue();
		$rgt = $page->getRightValue();
		$lvl = $page->getLevel();
		$locale = $this->applicationLocalization->getLocale();
		
		$nestedSet = $this->getNestedSetRepository();
		
		$filter = $nestedSet->createSearchCondition();
		/* @var $filter \Supra\NestedSet\SearchCondition\DoctrineSearchCondition */
		
		// Search for direct children
		$filter->add(SearchConditionInterface::LEFT_FIELD, SearchConditionInterface::RELATION_MORE, $lft);
		$filter->add(SearchConditionInterface::RIGHT_FIELD, SearchConditionInterface::RELATION_LESS, $rgt);
		$filter->add(SearchConditionInterface::LEVEL_FIELD, SearchConditionInterface::RELATION_EQUALS, $lvl + 1);
		
		$qb = $nestedSet->createSearchQueryBuilder($filter);
		/* @var $qb \Doctrine\ORM\QueryBuilder */
		
		$parameterOffset = $this->getNextParameterKey();
		
		// Add localization inside FROM
		$qb->from(Entity\PageLocalization::CN(), 'l')
				->andWhere('l.master = e')
				->andWhere("l.locale = ?{$parameterOffset}")
				->setParameter($parameterOffset, $locale);
		
		// Show inactive pages only if parameter is set
		if ( ! $this->showInactivePages) {
			$qb->andWhere('l.active = true');
		}
				
		// Will select localization by default
		$qb->select('l');
				
		return $qb;
	}
	
	/**
	 * @return QueryBuilder
	 */
	public function createCountQueryBuilder($groupBy = null)
	{
		$qb = $this->createQueryBuilder();
		
		// Ignore not published items without creation time
		$qb->andWhere('l.creationTime IS NOT NULL');
		$qb->select('COUNT(e.id) AS total');
		
		if ( ! empty($groupBy)) {
			$qb->addSelect($groupBy)
					->groupBy($groupBy)
					->orderBy($groupBy);
		}
		
		return $qb;
	}
	
	/**
	 * Loads news count by year
	 * @return array
	 */
	public function getCountByYear()
	{
		$groupBy = 'l.creationYear';
		$qb = $this->createCountQueryBuilder($groupBy);
		
		$data = $qb->getQuery()->getResult();
		
		return $data;
	}
	
	/**
	 * Load news count by month
	 * @param int $year
	 * @return array
	 */
	public function getCountByMonth($year = null)
	{
		$groupBy = 'l.creationYear, l.creationMonth';
		$qb = $this->createCountQueryBuilder($groupBy);
		
		if ( ! empty($year)) {
			$nextKey = $this->getNextParameterKey();
			$qb->andWhere('l.creationYear = ?' . $nextKey)
					->setParameter($nextKey, $year, Type::SMALLINT);
		}
		
		$data = $qb->getQuery()->getResult();
		
		return $data;
	}
	
	/**
	 * Load publication page localization array by interval
	 * @param DateTime $startTime
	 * @param DateTime $endTime
	 * @return array
	 */
	public function findByTime(DateTime $startTime = null, DateTime $endTime = null)
	{
		$qb = $this->createQueryBuilder();
		
		if ( ! is_null($startTime)) {
			$nextKey = $this->getNextParameterKey();
			$qb->andWhere("l.creationTime >= ?{$nextKey}")
					->setParameter($nextKey, $startTime, Type::DATETIME);
		}
		
		if ( ! is_null($endTime)) {
			$nextKey = $this->getNextParameterKey();
			$qb->andWhere("l.creationTime < ?{$nextKey}")
					->setParameter($nextKey, $endTime, Type::DATETIME);
		}
		
		$data = $qb->getQuery()->getResult();
		
		return $data;
	}
	
	/**
	 * @param int $year
	 * @param int $month
	 * @return array
	 */
	public function findByMonth($year, $month)
	{
		$startTime = new DateTime();
		$startTime->setDate($year, $month, 1);
		
		/* @var $endTime DateTime */
		$endTime = clone($startTime);
		$endTime->modify('+1 month');
		
		$data = $this->findByTime($startTime, $endTime);
		
		return $data;
	}
	
	/**
	 * @param int $year
	 * @return array
	 */
	public function findByYear($year)
	{
		$startTime = new DateTime();
		$startTime->setDate($year, 1, 1);
		
		/* @var $endTime DateTime */
		$endTime = clone($startTime);
		$endTime->modify('+1 year');
		
		$data = $this->findByTime($startTime, $endTime);
		
		return $data;
	}
	
	protected function getNewsCount()
	{
		$count = $this->createQueryBuilder()
				->select('COUNT(l.id)')
				->getQuery()
				->getSingleScalarResult();
		
		return $count;
	}
	
	protected function limitCollapsed()
	{
		$limit = $this->collapsedLimit;
		$count = $this->getNewsCount();
		
		if ($count < $this->hideExpandIfLessThen) {
			return false;
		}
		
		return true;
	}

	/**
	 * 
	 */
	public function getAvailableSitemapViewModes()
	{
		$modes = array(self::SITEMAP_VIEW_COLLAPSED);
		
		if ($this->limitCollapsed()) {
			$modes[] = self::SITEMAP_VIEW_EXPANDED;
		}
		
		return $modes;
	}
	
	/**
	 * @return array
	 */
	public function collapsedSitemapView()
	{
		$qb = $this->createQueryBuilder()
				->orderBy('l.creationTime DESC');
		$query = $qb->getQuery();
		
		if ($this->limitCollapsed()) {
			$query->setMaxResults($this->collapsedLimit);
		}
		
		$data = $query->getResult();
		
		return $data;
	}
	
	/**
	 * @return array
	 */
	public function expandedSitemapView()
	{
		$groupedData = array();
		
		$qb = $this->createQueryBuilder();
		$qb->orderBy('l.creationTime DESC');
		
		$data = $qb->getQuery()->getResult();
		
		return $data;
		
		// TODO: activate next block when sitemap will work as expected
		$currentYear = date('Y');
		
		/* @var $localization Entity\PageLocalization */
		foreach ($data as $localization)
		{
			$creationTime = $localization->getCreationTime();
			
			if (is_null($creationTime)) {
				$creationTime = new DateTime();
			}
			
			$year = $creationTime->format('Y');
			
			$groupName = null;
			
			if ($currentYear != $year) {
				$groupName = $creationTime->format('Y F');
			} else {
				$groupName = $creationTime->format('F');
			}
			
			$data[$groupName][] = $localization;
		}
		
		return $data;
	}
	
}
