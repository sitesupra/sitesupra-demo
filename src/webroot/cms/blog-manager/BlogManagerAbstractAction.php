<?php

namespace Supra\Cms\BlogManager;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\User\Entity\User;
use Supra\User\UserProviderAbstract;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity\ApplicationLocalization;

class BlogManagerAbstractAction extends CmsAction
{
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;
	
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $application;
	
	/**
	 * @var Supra\Controller\Pages\Entity\ApplicationLocalization
	 */
	protected $applicationLocalization;
	
	
	/**
	 * @param \Supra\Request\RequestInterface $request
	 * @param \Supra\Response\ResponseInterface $response
	 */
	public function prepare(\Supra\Request\RequestInterface $request, \Supra\Response\ResponseInterface $response)
	{
		parent::prepare($request, $response);
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}
	
	/**
	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalization
	 */
	protected function getBlogApplicationLocalization()
	{
		if ($this->applicationLocalization === null) {

			$localizationId = $this->getRequestParameter('parent_id');
			
			if (empty($localizationId)) {
				throw new \RuntimeException('Blog manager requests must have blog application id specified');
			}
			
			$localization = $this->entityManager->find(ApplicationLocalization::CN(), $localizationId);
	
			if ( ! $localization instanceof ApplicationLocalization) {
				throw new \RuntimeException("ApplicationLocalization for id {$localizationId} not found");
			}
			
			$application = PageApplicationCollection::getInstance()
					->createApplication($localization, $this->entityManager);
				
			if ( ! $application instanceof BlogApplication) {
				throw new \RuntimeException('Specified localization does not belongs to BlogApplication');
			}
			
			$this->applicationLocalization = $localization;
		}
		
		return $this->applicationLocalization;
	}
	
	/**
	 * @return \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected function getBlogApplication()
	{
		if ($this->application === null) {
			$applicationLocalization = $this->getBlogApplicationLocalization();
			$this->application = PageApplicationCollection::getInstance()
					->createApplication($applicationLocalization, $this->entityManager);
		}
		
		return $this->application;
	}
	
}