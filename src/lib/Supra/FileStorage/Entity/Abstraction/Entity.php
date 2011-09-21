<?php

namespace Supra\FileStorage\Entity\Abstraction;

use Doctrine\ORM\EntityManager;
use Supra\Database\Doctrine;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Supra\Database\Entity as DatabaseEntity;

/**
 * Base entity class for file storage
 */
abstract class Entity extends DatabaseEntity
{
	
}