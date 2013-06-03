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
		$ini = ObjectRepository::getIniConfigurationLoader($this);
		
		$activeTheme = ObjectRepository::getThemeProvider($this)
				->getActiveTheme();
		
		$response->assign('config', $appConfig);

//		$fontList = $activeTheme->getConfiguration()
//				->getFontList();
//		
//		$response->assign('fonts', array_values($fontList));
		
		if ( ! empty($appConfig->galleryBlockId)) {
			$blockId = str_replace('\\', '_', $appConfig->galleryBlockId);
			$response->assign('galleryBlockId', $blockId);
		}
		
		$response->assign('allowLimitedAccessPages', $appConfig->allowLimitedPageOption);
		
		if ($ini->getValue('system', 'supraportal_site', false)) {
			$response->assign('themeName', $activeTheme->getName());
		}

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
