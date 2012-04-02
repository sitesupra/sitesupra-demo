<?php

namespace Supra\Controller\Pages;

use Supra\Controller;
use Supra\Request\HttpRequest;

class ThemeSwitcherPreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof HttpRequest) {

			$previewThemeName = $request->getCookie('previewThemeName', FALSE);

			if ( ! empty($previewThemeName)) {
				
			}
		}
	}

}
