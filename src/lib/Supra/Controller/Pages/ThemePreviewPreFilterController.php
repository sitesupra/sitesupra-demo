<?php

namespace Supra\Controller\Pages;

use Supra\Controller;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;

class ThemePreviewPreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	const COOKIE_THEME_NAME = '__themeName';
	const COOKIE_DISABLE_PREVIEW = '__themeDisablePreview';

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof HttpRequest) {

			$disablePreviewTheme = $request->getCookie(self::COOKIE_DISABLE_PREVIEW, false);

			if ($disablePreviewTheme) {

				$cookies = $request->getCookies();
				unset($cookies[self::COOKIE_DISABLE_PREVIEW], $cookies[self::COOKIE_THEME_NAME]);
				$request->setCookies($cookies);
				
				$response = $this->getResponse();
				
				$cookie = new \Supra\Http\Cookie();
				$cookie->setName(self::COOKIE_THEME_NAME);
				
				$response->removeCookie($cookie);
				
				$cookie = new \Supra\Http\Cookie();
				$cookie->setName(self::COOKIE_DISABLE_PREVIEW);
				
				$response->removeCookie($cookie);
			}

			$previewThemeName = $request->getCookie(self::COOKIE_THEME_NAME, null);
			
			if ( ! empty($previewThemeName)) {
				
				$userProvider = ObjectRepository::getUserProvider($this);
				$signedUser = $userProvider->getSignedInUser();
				
				if ($signedUser instanceof \Supra\User\Entity\User) {

					$themeProvider = ObjectRepository::getThemeProvider($this);
					$previewTheme = $themeProvider->getThemeByName($previewThemeName);
				
					$themeProvider->useThemeAsPreviewTheme($previewTheme);
				}
			}
		}
	}
}
