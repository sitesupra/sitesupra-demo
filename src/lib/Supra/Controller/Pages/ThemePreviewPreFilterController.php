<?php

namespace Supra\Controller\Pages;

use Supra\Controller;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;

class ThemePreviewPreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	const COOKIE_NAME_PREVIEW_THEME_NAME = 'previewThemeName';
	const COOKIE_NAME_DISABLE_THEME_PREVIEW = 'disableThemePreview';

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof HttpRequest) {

			$disablePreviewTheme = $request->getCookie(self::COOKIE_NAME_DISABLE_THEME_PREVIEW, false);

			if ($disablePreviewTheme) {

				$cookies = $request->getCookies();

				unset($cookies[self::COOKIE_NAME_DISABLE_THEME_PREVIEW], $cookies[self::COOKIE_NAME_PREVIEW_THEME_NAME]);

				$request->setCookies($cookies);
			}

			$previewThemeName = $request->getParameter(self::COOKIE_NAME_PREVIEW_THEME_NAME, FALSE);
			//$previewThemeName = $request->getCookie(self::COOKIE_NAME_PREVIEW_THEME_NAME, false);

			if ( ! empty($previewThemeName)) {

				$themeProvider = ObjectRepository::getThemeProvider($this);

				$previewTheme = $themeProvider->getTheme($previewThemeName);

				$previewTheme->setPreviewParametersAsCurrentParameters();
				$themeProvider->setCurrentTheme($previewTheme);
			}
		}
	}

}
