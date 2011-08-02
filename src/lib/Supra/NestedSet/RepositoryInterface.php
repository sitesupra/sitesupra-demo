<?php

namespace Supra\NestedSet;

use Doctrine\ORM\EntityRepository;
use Supra\NestedSet\Exception;
use Supra\NestedSet\DoctrineRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\EntityManager;

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