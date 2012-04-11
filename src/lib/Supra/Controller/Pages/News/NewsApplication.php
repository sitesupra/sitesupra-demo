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
	/*
	 * Order direction constants
	 */
	const ORDER_DESC = 'DESC';
	const ORDER_ASC = 'ASC';
	
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
		$pageRep = $this->em->getRepository(Entity\Abstraction\AbstractPage::CN());
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
	 * @return QueryBuilder
	 */
	public function createChildrenQueryBuilder()
	{
		$page = $this->applicationLocalization->getMaster();
		$lft = $page->getLeftValue();
		$rgt = $page->getRightValue();
		$lvl = $page->getLevel();
		
		$nestedSet = $this->getNestedSetRepository();
		
		$filter = $nestedSet->createSearchCondition();
		/* @var $filter \Supra\NestedSet\SearchCondition\DoctrineSearchCondition */
		
		// Search for direct children
		$filter->add(SearchConditionInterface::LEFT_FIELD, SearchConditionInterface::RELATION_GREATER, $lft);
		$filter->add(SearchConditionInterface::RIGHT_FIELD, SearchConditionInterface::RELATION_LESS, $rgt);
		$filter->add(SearchConditionInterface::LEVEL_FIELD, SearchConditionInterface::RELATION_EQUALS, $lvl + 1);
		
		$qb = $nestedSet->createSearchQueryBuilder($filter);
		/* @var $qb \Doctrine\ORM\QueryBuilder */
		
		return $qb;
	}
	
	/**
	 * Creates generic query builder
	 * @return QueryBuilder
	 */
	public function createNewsQueryBuilder()
	{
		$locale = $this->applicationLocalization->getLocale();
		
		$qb = $this->createChildrenQueryBuilder();
		
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
	public function createHiddenQueryBuilder()
	{
		$qb = $this->createChildrenQueryBuilder();
		$groupEntity = Entity\GroupPage::CN();
		
		// Add localization inside FROM
		$qb->andWhere("e INSTANCE OF $groupEntity");
		
		$qb->select('e');
				
		return $qb;
	}
	
	/**
	 * @return QueryBuilder
	 */
	public function createCountQueryBuilder($groupBy = null, $orderDirection = self::ORDER_ASC)
	{
		$qb = $this->createNewsQueryBuilder();
		
		$qb->select('COUNT(e.id) AS total');
		
		if ( ! empty($groupBy)) {
			$qb->addSelect($groupBy)
					->groupBy($groupBy)
					->orderBy($groupBy, $orderDirection);
		}
		
		return $qb;
	}
	
	/**
	 * Loads news count by year
	 * @return array
	 */
	public function getCountByYear($orderDirection = self::ORDER_ASC)
	{
		$groupBy = 'l.creationYear';
		$qb = $this->createCountQueryBuilder($groupBy, $orderDirection);
		
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
		$qb = $this->createNewsQueryBuilder();
		
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
		
		$qb->orderBy("l.creationTime", "DESC");
		
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
	
	/**
	 * Get total news count (including global ones)
	 * @return int
	 */
	protected function getNewsCount()
	{
		//$count = $this->createNewsQueryBuilder()
		//		->select('COUNT(l.id)')
		//		->getQuery()
		//		->getSingleScalarResult();
		
		$query = $this->getNewsNativeQuery(true);
		$count = $query->getSingleScalarResult();
		
		return $count;
	}
	
	/**
	 * Whether should limit the news count inside the CMS sitemap initially
	 * @return boolean
	 */
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
	 * {@inheritdoc}
	 * @return array
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
	 * {@inheritdoc}
	 * @return array
	 */
	public function collapsedSitemapView()
	{
//		$qb = $this->createNewsQueryBuilder()
//			->orderBy('l.creationTime', 'DESC');
//		$query = $qb->getQuery();
//		$data = $query->getResult();
		
		$limit = null;
		if ($this->limitCollapsed()) {
			$limit = $this->collapsedLimit;
		}
		
		$query = $this->getNewsNativeQuery(false, $limit);
		$data = $query->getResult();
		
		return $data;
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function expandedSitemapView()
	{
		$query = $this->getNewsNativeQuery();
		$news = $query->getResult();
		
		$data = array();
		
		$currentYear = date('Y');
		
		/* @var $localization Entity\PageLocalization */
		foreach ($news as $localization)
		{
			$creationTime = $localization->getCreationTime();
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

	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function hasHiddenPages()
	{
		$hiddenPages = $this->getHiddenPages();
		$hasHiddenPages = ( ! empty($hiddenPages));
		
		return $hasHiddenPages;
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getHiddenPages()
	{
		$qb = $this->createHiddenQueryBuilder();
		$groups = $qb->getQuery()->getResult();
		
		return $groups;
	}
	
	/**
	 * @param boolean $countOnly
	 * @param int $limit
	 * @return NativeQuery
	 */
	protected function getNewsNativeQuery($countOnly = false, $limit = null)
	{
		$locale = $this->applicationLocalization->getLocale();
		
		$page = $this->applicationLocalization->getMaster();
		$lft = $page->getLeftValue();
		$rgt = $page->getRightValue();
		$lvl = $page->getLevel();
		
		$queryParameters = array(
			'lvl' => $lvl + 1,
			'lft' => $lft,
			'rgt' => $rgt,
			'locale' => $locale
		);
		
		$rsm = new \Doctrine\ORM\Query\ResultSetMapping;
		
		if ( ! $countOnly) {
			$rsm->addEntityResult(Entity\PageLocalization::CN(), 'l');

			$classMetadata = $this->em->getClassMetadata(Entity\PageLocalization::CN());
			foreach ($classMetadata->fieldMappings as $mapping) {
				$rsm->addFieldResult('l', $mapping['columnName'], $mapping['fieldName']);
			}
			
			// localization path
			$rsm->addJoinedEntityResult(Entity\PageLocalizationPath::CN(), 'pt', 'l', 'path');
			
			$rsm->addFieldResult('pt', 'pt_id', 'id');
			$rsm->addFieldResult('pt', 'pt_locale', 'locale');
			$rsm->addFieldResult('pt', 'path', 'path');
			$rsm->addMetaResult('pt', 'pt_id', 'id', true);

			$rsm->setDiscriminatorColumn('l', 'discr');
			$rsm->addMetaResult('l', 'discr', 'discr');

			$rsm->addMetaResult('l', 'master_id', 'master_id');
			$rsm->addMetaResult('l', 'template_id', 'template_id');
			$rsm->addMetaResult('l', 'redirect_id', 'redirect_id');
			$rsm->addMetaResult('l', 'path_id', 'path_id');
			$rsm->addMetaResult('l', 'id', 'id', true);
			//"select l.*" is not supported by SQL-92 standart
			$selectValues = 'l.id, l.locale, l.title, l.visibleInSitemap, l.visibleInMenu, l.includedInSearch,
						l.revision, l.path_part, l.meta_description, l.meta_keywords, l.active,	l.schedule_time,
						l.creationTime, l.creationYear, l.creationMonth, l.publishTimeSet, l.master_id, l.lock_id,
						l.template_id, l.path_id, l.redirect_id, l.discr, pt.id AS pt_id, pt.locale as pt_locale, pt.path, pt.id AS pt_id';
		} else {
			$selectValues = 'COUNT(l.id) as count';
			$rsm->addScalarResult('count', 'count');
		}
		
		$limitString = null;
		if ( ! is_null($limit)) {
			$limitString = 'LIMIT ' . (int)$limit;
		}

		$sql = "SELECT {$selectValues} FROM su_AbstractPage ap 
					INNER JOIN su_Localization_draft l ON ap.id = l.master_id 
					INNER JOIN su_PageLocalizationPath_draft pt ON pt.id = l.path_id 
					WHERE (ap.lvl = :lvl AND ap.lft > :lft AND ap.rgt < :rgt AND ap.discr = 'page') 
					AND l.id = (SELECT l2.id FROM su_Localization_draft l2 WHERE l2.master_id = l.master_id ORDER BY (l2.locale = :locale) DESC, l2.revision ASC LIMIT 1) 
					ORDER BY l.creationTime DESC {$limitString}";
		
		$query = $this->em->createNativeQuery($sql, $rsm);
		$query->setParameters($queryParameters);
		
		return $query;
	}
	
	/**
	 * @return \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	public function getPaginator()
	{
		$locale = $this->applicationLocalization->getLocale();
		$page = $this->applicationLocalization->getMaster();
		
		$pageFinder = new \Supra\Controller\Pages\Finder\PageFinder($this->em);
		$pageFinder->addFilterByParent($page, 1, 1);
		
		$queryBuilder = $pageFinder->getQueryBuilder();
		
		$queryBuilder->leftJoin('e.localizations', 'l_', 'WITH', 'l_.locale = :locale')
				->setParameter('locale', $locale)
				->join('e.localizations', 'l')
				->andWhere('l_.id IS NOT NULL OR e.global = true')
				->select('e, l, COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
				->orderBy('ct', 'DESC');
		
		$query = $queryBuilder->getQuery();
		
		$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query, true);
		
		return $paginator;
	}
	
	public function getSitemap($level = null)
	{
		$paginator = $this->getPaginator();
		$count = $paginator->count();
		
		$sitemap = array();
		
		$group = new Entity\TemporaryGroupPage();
		$group->setTitle('list');
		$group->setNumberChildren($count);
		
		// FIXME: bad workaround for children fetching
		$id = $this->applicationLocalization->getId()
				. '_' . 'list';
		$group->setId($id);
		
		if (is_null($level) || $level > 1) {
			$group->setChildren(iterator_to_array($paginator));
		}
		
		$sitemap[] = $group;
		$sitemap = array_merge($sitemap, $this->getHiddenPages());
		
		return $sitemap;
	}
	
	public function getList($limit = null, $offset = null)
	{
		$paginator = $this->getPaginator();
		$paginator->getQuery()->setMaxResults($limit)
				->setFirstResult($offset);
		
		return $paginator;
	}
}
