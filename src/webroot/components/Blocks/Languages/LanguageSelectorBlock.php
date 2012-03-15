<?php

namespace Project\Blocks\Languages;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\BlockController;
use Supra\Locale\Locale;
use Supra\Controller\Pages\Entity;
use Supra\Uri\Path;
use Supra\Controller\Pages\Request\HistoryPageRequestEdit;

/**
 * Language Selector Block
 */
class LanguageSelectorBlock extends BlockController
{
	public function getPropertyDefinition()
	{
		
	}

	public function execute()
	{
		$request = $this->getRequest();
		$page = $request->getPage();
		$pageAncestors = $page->getAncestors(0, true);
		
		$localeManager = ObjectRepository::getLocaleManager($this);
		$currentLocale = $localeManager->getCurrent();
		$locales = $localeManager->getLocales();
		
		$url = array();
		
		foreach ($locales as $locale) {
			/* @var $locale Locale */
			$localeId = $locale->getId();
			
			$url[$localeId] = null;
			
			$pageLocalization = null;
			
			foreach ($pageAncestors as $_page) {
				/* @var $_page Entity\Abstraction\AbstractPage */
				if ($request instanceof HistoryPageRequestEdit) {
					// Fetch available draft localizations
					$pageLocalization = $request->getDraftLocalization($localeId);
				} else {
					$pageLocalization = $_page->getLocalization($localeId);
				}
				
				if ($pageLocalization instanceof Entity\PageLocalization) {
					$url[$localeId] = $pageLocalization->getPath()
							->getFullPath(Path::FORMAT_RIGHT_DELIMITER);
					
					break;
				}
			}
		}
		
		$response = $this->getResponse();
		$response->assign('locales', $locales);
		$response->assign('currentLocale', $currentLocale);
		$response->assign('localizedUrl', $url);
		
		$response->outputTemplate('main.html.twig');
	}
}
