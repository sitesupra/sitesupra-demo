<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\Entity;

/**
 * Page controller request object on view method
 */
class PageRequestView extends PageRequest
{
	/**
	 * @param HttpRequest $request
	 */
	public function __construct(HttpRequest $request)
	{
		// Not nice but functional method to downcast the request object
		foreach ($request as $field => $value) {
			$this->$field = $value;
		}
		
		$localeManager = ObjectRepository::getLocaleManager($this);
		$localeId = $localeManager->getCurrent()->getId();
		$this->setLocale($localeId);
	}
	
	/**
	 * Overriden with page detection from URL
	 * @return Entity\Abstraction\Data
	 */
	public function getRequestPageData()
	{
		$data = parent::getRequestPageData();
		
		if (empty($data)) {
			$data = $this->detectRequestPageData();
			
			$this->setRequestPageData($data);
		}

		return $data;
	}
	
	/**
	 * @return Entity\Abstraction\Data
	 * @throws ResourceNotFoundException if page not found or is inactive
	 */
	protected function detectRequestPageData()
	{
		$action = $this->getActionString();
		$action = trim($action, '/');

		$em = $this->getDoctrineEntityManager();
		$er = $em->getRepository(Entity\PageData::__CLASSNAME__());

		$searchCriteria = array(
			'locale' => $this->getLocale(),
			'path' => $action,
		);

		//TODO: think about "enable path params" feature

		/* @var $pageData Entity\PageData */
		$pageData = $er->findOneBy($searchCriteria);

		if (empty($pageData)) {
			//TODO: 404 page

			// for better exception message presentation
			if(empty($action)) {
				$action = '/';
			}

			throw new ResourceNotFoundException("No page found by path '$action' in pages controller");
		}
		
		if ( ! $pageData->isActive()) {
			throw new ResourceNotFoundException("Page found by path '$action' in pages controller but is inactive");
		}

		return $pageData;
	}
}
