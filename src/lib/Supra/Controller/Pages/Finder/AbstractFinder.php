<?php

namespace Supra\Controller\Pages\Finder;

use Doctrine\ORM\EntityManager;

/**
 * AbstractFinder
 */
abstract class AbstractFinder
{
	/**
	 * @var EntityManager
	 */
	protected $em;
	
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}
	
	public function getEntityManager()
	{
		return $this->em;
	}
	
	abstract public function getQueryBuilder();
	
	public function getResult()
	{
		return $this->getQueryBuilder()
				->getQuery()
				->getResult();
	}
}
