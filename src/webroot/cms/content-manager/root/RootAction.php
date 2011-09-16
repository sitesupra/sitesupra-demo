<?php

namespace Supra\Cms\ContentManager\Root;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;

/**
 * Root action, returns initial HTML
 */
class RootAction extends PageManagerAction
{
	/**
	 * Method returning manager initial HTML
	 */
	public function indexAction()
	{
		$pageId = $this->getInitialPageId();
		$localeId = $this->getLocale()->getId();
		
		$response = $this->getResponse();
		/* @var $response TwigResponse */
		
		$response->assign('localesList', $this->createLocaleArray());
		$response->assign('currentLocale', $localeId);
		
		$response->assign('pageId', $pageId);
		
		$this->getResponse()->outputTemplate('webroot/cms/content-manager/root/index.html.twig');
	}
	
	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return new Response\TwigResponse();
	}
	
	/**
	 * Creates locale array for JS
	 * @return array
	 */
	private function createLocaleArray()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);
		$locales = $localeManager->getLocales();
		
		$jsLocales = array();
		
		/* @var $locale Locale */
		foreach ($locales as $locale) {
			
			$country = $locale->getCountry();
			
			if ( ! isset($jsLocales[$country])) {
				$jsLocales[$country] = array(
					'title' => $country,
					'languages' => array()
				);
			}
			
			$jsLocales[$country]['languages'][] = array(
				'id' => $locale->getId(),
				'title' => $locale->getTitle(),
				'flag' => $locale->getProperty('flag')
			);
		}
		
		$jsLocales = array_values($jsLocales);
		
		return $jsLocales;
	}
	
}
