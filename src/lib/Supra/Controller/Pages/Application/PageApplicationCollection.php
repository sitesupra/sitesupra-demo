<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Configuration\PageApplicationConfiguration;
use Supra\Loader\Loader;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;

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
	 * @param string $id
	 * @return PageApplicationConfiguration 
	 */
	public function getConfiguration($id)
	{
		if (isset($this->applicationConfigurationList[$id])) {
			return $this->applicationConfigurationList[$id];
		}
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
			throw new Exception\RuntimeException("Method createApplication accepts only application page localization objects");
		}
		
		$applicationId = $applicationPage->getApplicationId();
		$cacheId = ObjectRepository::getObjectHash($pageLocalization) 
				. spl_object_hash($em);
		
		if ( ! isset($this->loadedApplications[$cacheId])) {
			$configuration = $this->getConfiguration($applicationId);
			
			if ($configuration instanceof PageApplicationConfiguration) {
				
				$application = Loader::getClassInstance(
						$configuration->className, 
						'Supra\Controller\Pages\Application\PageApplicationInterface');
				
				/* @var $application PageApplicationInterface */
				
				$application->setEntityManager($em);
				$application->setApplicationLocalization($pageLocalization);
				
				$this->loadedApplications[$cacheId] = $application;
			}
		}
		
		return $this->loadedApplications[$cacheId];
	}
}
