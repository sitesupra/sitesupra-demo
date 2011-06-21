<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\Http,
		Supra\Locale\Data as LocaleData,
		Supra\Controller\Pages\Entity\Abstraction\Page;

/**
 * Page controller request object on view method
 */
class RequestView extends Request
{
	/**
	 * Page data class to be used
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageData';
	
	/**
	 * @param Http $request
	 */
	public function __construct(Http $request)
	{
		// Not nice but functional method to downcast the request object
		foreach ($request as $field => $value) {
			$this->$field = $value;
		}
		
		//TODO: real locale detection missing
		$locale = LocaleData::getInstance()->getCurrent();
		$this->setLocale($locale);
	}
	
	/**
	 * Overriden with page detection
	 * @return Page
	 */
	public function getRequestPage()
	{
		$page = parent::getRequestPage();
		
		if (empty($page)) {
			$page = $this->detectRequestPage();
			
			$this->setRequestPage($page);
		}

		return $page;
	}
	
	/**
	 * @return Page
	 */
	protected function detectRequestPage()
	{
		$action = $this->getActionString();
		$action = trim($action, '/');

		$em = $this->getDoctrineEntityManager();
		$er = $em->getRepository(static::PAGE_DATA_ENTITY);

		$searchCriteria = array(
			'locale' => $this->getLocale(),
			'path' => $action,
		);

		//TODO: think about "enable path params" feature

		/* @var $page Entity\PageData */
		$pageData = $er->findOneBy($searchCriteria);

		if (empty($pageData)) {
			//TODO: 404 page
			throw new NotFoundException("No page found by path '$action' in pages controller");
		}

		$page = $pageData->getPage();

		return $page;
	}
}
