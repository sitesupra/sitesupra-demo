<?php

namespace Supra\Cms\CrudManager;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;

abstract class CrudManagerAbstractAction extends CmsAction
{
	/**
	 * @return ApplicationConfiguration
	 */
	protected function getConfiguration()
	{
		$configuration = ObjectRepository::getApplicationConfiguration($this);
		
		if ( ! $configuration instanceof ApplicationConfiguration) {
			throw new \RuntimeException("CRUD manager must have Supra\Cms\CrudManager\ApplicationConfiguration as configuration object, " . get_class($configuration) . ' received.');
		}
		
		return $configuration;
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	protected function getRepository()
	{
		$config = $this->getConfiguration();
		$entityManager = ObjectRepository::getEntityManager($this);

		return $entityManager->getRepository($config->entity);
	}
}
