<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Configuration\PageApplicationConfiguration;
use Supra\Loader\Loader;
use Doctrine\ORM\EntityManager;
//use Supra\Controller\Pages\Entity\ApplicationPage;
//use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;

use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\ApplicationPage;

/**
 * Collection of page applications
 */
class PageApplicationCollection
{
	/**
	 * @var PageApplicationCollection
	 */
	private static $instance;
	
	/**
	 * @var array
	 */
	protected $applicationConfigurationList = array();
	
	/**
	 * @var array
	 */
	protected $loadedApplications = array();
	
	/**
	 * @return PageApplicationCollection 
	 */
	public static function getInstance()
	{
		throw new \RuntimeException('Dont use me bro.');

		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * @param PageApplicationConfiguration $configuration
	 */
	public function addConfiguration(PageApplicationConfiguration $configuration)
	{
		$id = $configuration->id;
		$this->applicationConfigurationList[$id] = $configuration;
	}
	
	/**
	 * @retunr array
	 */
	public function getApplicationConfigurationList()
	{
		return $this->applicationConfigurationList;
	}
	
	/**
	 * @param string $id
	 * @return PageApplicationConfiguration 
	 */
	public function getConfiguration($id)
	{
		if (isset($this->applicationConfigurationList[$id])) {
			return $this->applicationConfigurationList[$id];
		}

		throw new \InvalidArgumentException(sprintf(
				'Missing configuration for application [%s]',
				$id
		));
	}
	
	/**
	 * @param PageLocalization $pageLocalization
	 * @param EntityManager $em
	 * @return PageApplicationInterface
	 */
	public function createApplication(PageLocalization $pageLocalization, EntityManager $em)
	{
		$applicationPage = $pageLocalization->getMaster();
		
		if ( ! $applicationPage instanceof ApplicationPage) {
			throw new \UnexpectedValueException(
					'Expecting ApplicationPage object, [%s] received',
					get_class($applicationPage)
			);
		}
		
		$applicationId = $applicationPage->getApplicationId();
		
		$cacheId = ObjectRepository::getObjectHash($pageLocalization) 
				. spl_object_hash($em);
		
		if ( ! isset($this->loadedApplications[$cacheId])) {

			$configuration = $this->getConfiguration($applicationId);

			$application = Loader::getClassInstance(
					$configuration->className,
					'Supra\Controller\Pages\Application\PageApplicationInterface'
			);

			/* @var $application PageApplicationInterface */

			$application->setEntityManager($em);
			$application->setApplicationLocalization($pageLocalization);

			$this->loadedApplications[$cacheId] = $application;
		}


//		// TODO: this might cause some problems because this instance could be shared
//		// Still resetting this value is better than not doing this.
//		$this->loadedApplications[$cacheId]->showInactivePages(false);
		
		return $this->loadedApplications[$cacheId];
	}
}
