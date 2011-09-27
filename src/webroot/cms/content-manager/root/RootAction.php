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
		$response = $this->getResponse();
		/* @var $response TwigResponse */
		
		// Last opened page
		$pageLocalizationId = $this->getInitialPageLocalizationId();
		$response->assign('pageLocalizationId', $pageLocalizationId);
		
		// Current locale ID
		$localeId = $this->getLocale()->getId();
		$response->assign('currentLocale', $localeId);

		// Locale array
		$localeList = $this->createLocaleArray();
		$response->assign('localesList', $localeList);
		
		// Currently signed in user
		$user = $this->getUser();
		$response->assign('user', $user);
		
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
