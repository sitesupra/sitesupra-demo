<?php

namespace Supra\Controller\Pages\News;

use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Entity;
use DateTime;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\NestedSet\SearchCondition\SearchConditionInterface;

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
	 * Should load news count by month
	 * @return array
	 */
	public function getCountByMonth()
	{
		$page = $this->applicationLocalization->getMaster();
		$lft = $page->getLeftValue();
		$rgt = $page->getRightValue();
		$lvl = $page->getLevel();
		$locale = $this->applicationLocalization->getLocale();
		
		$pageRep = $this->em->getRepository(Entity\Page::CN());
		/* @var $pageRep PageRepository */
		
		$nestedSet = $pageRep->getNestedSetRepository();
		/* @var $nestedSet \Supra\NestedSet\DoctrineRepository */
		
		$filter = $nestedSet->createSearchCondition();
		/* @var $filter \Supra\NestedSet\SearchCondition\DoctrineSearchCondition */
		
		// Search for direct children
		$filter->add(SearchConditionInterface::LEFT_FIELD, SearchConditionInterface::RELATION_MORE, $lft);
		$filter->add(SearchConditionInterface::RIGHT_FIELD, SearchConditionInterface::RELATION_LESS, $rgt);
		$filter->add(SearchConditionInterface::LEVEL_FIELD, SearchConditionInterface::RELATION_EQUALS, $lvl + 1);
		
		$qb = $nestedSet->createSearchQueryBuilder($filter);
		/* @var $qb \Doctrine\ORM\QueryBuilder */
		
		$parameterOffset = $nestedSet->increaseParameterOffset();
		
		// Add localization inside FROM
		$qb->from(Entity\PageLocalization::CN(), 'l')
				->andWhere('l.master = e')
				->andWhere("l.locale = ?{$parameterOffset}")
				->setParameter($parameterOffset, $locale);
		
		//TODO: group by month not time, will need to create new column I suppose...
		$qb->select('l.creationTime, COUNT(e.id)');
		$qb->groupBy("l.creationTime");
		
		// For query testing
//		$dql = $qb->getDQL();
		
		$data = $qb->getQuery()->getResult();
		
		return $data;
	}
	
	public function findByTime(DateTime $startTime, DateTime $endTime)
	{
		
	}
	
	public function findByMonth($year, $month)
	{
		
	}
	
	public function findByYear($year)
	{
		
	}
	
	
}
