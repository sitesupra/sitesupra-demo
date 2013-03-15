<?php

namespace Supra\Cms\ContentManager\Root;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Root action, returns initial HTML
 * @method TwigResponse getResponse()
 */
class RootAction extends PageManagerAction
{

	/**
	 * Method returning manager initial HTML
	 */
	public function indexAction()
	{
		$response = $this->getResponse();

		// Last opened page, overrides current detected locale if found
		$pageLocalization = $this->getInitialPageLocalization();

		if ( ! is_null($pageLocalization)) {
			$pageLocalizationId = $pageLocalization->getId();
			$locale = $pageLocalization->getLocale();
			$response->assign('pageLocalizationId', $pageLocalizationId);
			$response->assign('currentLocale', $locale);
		} else {
			$response->assign('pageLocalizationId', null);
		}

		$appConfig = ObjectRepository::getApplicationConfiguration($this);

		$response->assign('config', $appConfig);

		$fontList = ObjectRepository::getThemeProvider($this)
				->getActiveTheme()
				->getConfiguration()
				->getFontList();
		
		$response->assign('fonts', array_values($fontList));
		
		if ( ! empty($appConfig->galleryBlockId)) {
			$blockId = str_replace('\\', '_', $appConfig->galleryBlockId);
			$response->assign('galleryBlockId', $blockId);
		}
		
		if ($appConfig->allowLimitedPageOption == false) {
			$allowLimitedOption = 'false';
		} else {
			$allowLimitedOption = 'true';
		}
		$response->assign('allowLimitedAccessPages', $allowLimitedOption);
		

		$response->outputTemplate('content-manager/root/index.html.twig');
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return $this->createTwigResponse();
	}

}
