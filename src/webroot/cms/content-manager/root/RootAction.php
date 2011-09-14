<?php

namespace Supra\Cms\ContentManager\Root;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Request;
use Supra\Response;

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
		
		$localeManager = \Supra\ObjectRepository\ObjectRepository::getLocaleManager($this);
		$localesList = $localeManager->getLocalesCountryArray();
		
		$response = $this->getResponse();
		/* @var $response TwigResponse */
		
		$response->assign('localesList', $localesList);
		//$response->assign('currentLocale', $localeId);
		$response->assign('currentLocale', 'en_Latvia');
		
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
	
}
