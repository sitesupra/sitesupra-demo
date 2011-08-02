<?php

namespace Supra\FileStorage\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Supra\FileStorage\Exception;

/**
 * Will raise an exception if will be trying to use it
 */
class ForbiddenRepository extends EntityRepository
{
	/**
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct($em, Mapping\ClassMetadata $class)
	{
		throw new Exception\LogicException("Only repository of file abstraction should be requested");
	}
}
