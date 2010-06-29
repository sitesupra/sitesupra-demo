<?php

namespace Supra\Controller\Pages;

use Doctrine\ORM\EntityManager;
use Supra\Database\Doctrine;

/**
 * Base entity class for Pages controller
 */
class EntityAbstraction
{
	/**
	 * Connection name
	 * @var string
	 */
	static private $connnection;

	/**
	 * Set connection name used by Pages controller
	 * @param string $connectionName
	 */
	public static function setConnectionName($connectionName = null)
	{
		self::$connnection = $connectionName;
	}

	/**
	 * Get configured doctrine entity manager
	 * @return EntityManager
	 */
	public static function getConnection()
	{
		return Doctrine::getInstance()->getEntityManager(self::$connnection);
	}
}