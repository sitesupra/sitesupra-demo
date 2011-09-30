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
	 * @return Entity\Abstraction\Localization
	 */
	public function getPageLocalization()
	{
		$data = parent::getPageLocalization();
		
		if (empty($data)) {
			$data = $this->detectRequestPageLocalization();
			
			$this->setPageLocalization($data);
		}

		return $data;
	}
	
	/**
	 * @return Entity\Abstraction\Localization
	 * @throws ResourceNotFoundException if page not found or is inactive
	 */
	protected function detectRequestPageLocalization()
	{
		$path = $this->getPath();

		$em = $this->getDoctrineEntityManager();
		$er = $em->getRepository(Entity\PageLocalization::CN());

		$searchCriteria = array(
			'locale' => $this->getLocale(),
			'path' => $path->getPath(),
		);

		//TODO: think about "enable path params" feature

		/* @var $pageData Entity\PageLocalization */
		$pageData = $er->findOneBy($searchCriteria);

		if (empty($pageData)) {
			//TODO: 404 page

			throw new ResourceNotFoundException("No page found by path '$path' in pages controller");
		}
		
		if ( ! $pageData->isActive()) {
			throw new ResourceNotFoundException("Page found by path '$path' in pages controller but is inactive");
		}
		
		return $pageData;
	}
}
