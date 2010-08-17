<?php

namespace Supra\NestedSet;

use Doctrine\ORM\EntityRepository,
		Supra\NestedSet\Exception,
		Supra\NestedSet\DoctrineRepository,
		Doctrine\ORM\Mapping,
		Doctrine\ORM\EntityManager;

/**
 * The nested set and Doctrine entity repositories must implement this interface
 */
interface RepositoryInterface
{
	/**
	 * @return RepositoryAbstraction
	 */
	public function getNestedSetRepository();
}