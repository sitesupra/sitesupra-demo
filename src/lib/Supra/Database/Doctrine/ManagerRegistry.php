<?php 

namespace Supra\Database\Doctrine;

use \Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;

class ManagerRegistry extends \Doctrine\Common\Persistence\AbstractManagerRegistry
{
	protected function getService($name)
	{
		switch ($name) {
			case PageController::SCHEMA_PUBLIC:
				return ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
				
			case PageController::SCHEMA_DRAFT:
				return ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
				
			case PageController::SCHEMA_AUDIT:
				return ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
				
			default:
				throw new \RuntimeException("Unknown manager {$name}");
		}
	}

	protected function resetService($name)
	{
		
	}

	public function getAliasNamespace($alias)
	{
		
	}	
}