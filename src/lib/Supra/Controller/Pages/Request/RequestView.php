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
	 * @return Page
	 */
	protected function detectRequestPageData()
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

		return $pageData;
	}
}
