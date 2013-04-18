<?php

namespace Supra\Controller\Pages;

use Supra\Controller;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;

class ThemePreviewPreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	const KEY = '__previewKey';
	const THEME_NAME = '__previewThemeName';

	/**
	 * 
	 */
	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof HttpRequest) {
			
			$key = $request->getQueryValue(self::KEY, null);
			
			$info = ObjectRepository::getSystemInfo($this);
			if (method_exists($info, 'isDemoSite') && $info->isDemo()){
				$themeProvider = ObjectRepository::getThemeProvider($this);
				$themeProvider->setThemePreviewActive(true);
				return;
			}
			
			if ( ! empty($key)) {
				
				$userProvider = ObjectRepository::getUserProvider($this);
				$signedUser = $userProvider->getSignedInUser();
				
				if ($signedUser instanceof \Supra\User\Entity\User) {

					$sessionSpace = $userProvider->getSessionSpace();
					$sessionKey = $sessionSpace->__get(self::KEY);
					$previewThemeName = $sessionSpace->__get(self::THEME_NAME);
					
					if ($sessionKey === $key && ! empty($previewThemeName)) {
						
						$themeProvider = ObjectRepository::getThemeProvider($this);
						$previewTheme = $themeProvider->getThemeByName($previewThemeName);
				
						$themeProvider->useThemeAsPreviewTheme($previewTheme);
					}
				}
			}
		}
		
	}
}
