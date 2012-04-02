<?php

namespace Supra\Controller\Pages\Finder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Supra\Controller\Pages\PageController;
use Supra\Cache\CachedQueryBuilderWrapper;

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
		
		$cachedQb = new CachedQueryBuilderWrapper($qb, PageController::CACHE_GROUP_NAME);
		
		return $cachedQb;
	}
	
	abstract protected function doGetQueryBuilder();
	
	public function getResult()
	{
		$query = $this->getQueryBuilder()
				->getQuery();
		
		return $query->getResult();
	}
}
