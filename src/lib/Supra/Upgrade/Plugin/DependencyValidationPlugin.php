<?php

namespace Supra\Upgrade\Plugin;

use Doctrine\ORM\EntityManager;
use Supra\Upgrade\Exception\RuntimeException;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Checks entity dependencies. 
 * E.g. MySuperUpgradeScript need to update User entity,
 * 
 * @example DependencyValidationPlugin($em, array('My\Super\Entity\User'));
 */
class DependencyValidationPlugin implements UpgradePluginInterface
{

	/**
	 * @var EntityManager 
	 */
	protected $em;

	/**
	 * Dependency entities
	 * @var array
	 */
	protected $dependencies;

	/**
	 * @param EntityManager $enitityManager
	 * @param array $dependencies 
	 */
	public function __construct(EntityManager $enitityManager, array $dependencies = array())
	{
		$this->em = $enitityManager;
		$this->dependencies = $dependencies;
	}

	public function execute()
	{
		if ( ! is_array($this->dependencies)) {
			\Log::warn('Entity dependencies should be an array');
			return false;
		}

		// gathering all entities 
		$metadataDriver = $this->em->getConfiguration()->getMetadataDriverImpl();
		$entities = $metadataDriver->getAllClassNames();

		// checking dependencies
		foreach ($this->dependencies as $dependency) {
			if ( ! in_array($dependency, $entities)) {
				\Log::warn("Failed to find dependent entity \"$dependency\" entity manager");
				return false;
			}
		}

		return true;
	}

}