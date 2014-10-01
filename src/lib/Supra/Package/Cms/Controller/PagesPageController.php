<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;

class PagesPageController extends AbstractPagesController
{
	public function layoutsListAction()
	{
		$themeProvider = $this->getThemeProvider();

		$theme = $themeProvider->getCurrentTheme();
		$themeConfig = $theme->getConfiguration();

//		$defaultIcon = '/cms/lib/supra/img/sitemap/preview/layout.png';

		$responseData = array();

		foreach ($theme->getLayouts() as $layout) {

			$layoutName = $layout->getName();

			$layoutIcon = $themeConfig->hasLayoutConfiguration($layoutName)
					? $themeConfig->getLayoutConfiguration($layoutName)->icon
					: null;

			$responseData[] = array(
				'id'	=> $layoutName,
				'title' => $layout->getTitle(),
				'icon'	=> $layoutIcon,
			);
		}

		return new SupraJsonResponse($responseData);
	}
}