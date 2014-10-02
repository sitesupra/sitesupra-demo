<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;

class PagesPageController extends AbstractPagesController
{
	public function layoutsListAction()
	{
		$themeProvider = $this->getThemeProvider();

		$theme = $themeProvider->getActiveTheme();

		$responseData = array();

		foreach ($theme->getLayouts() as $layout) {

			$layoutName = $layout->getName();

			$responseData[] = array(
				'id'	=> $layoutName,
				'title' => $layout->getTitle(),
				'icon'	=> $layout->getIcon(),
			);
		}

		return new SupraJsonResponse($responseData);
	}
}