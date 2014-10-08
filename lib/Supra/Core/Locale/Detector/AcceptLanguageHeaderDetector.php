<?php

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;

class AcceptLanguageHeaderDetector implements DetectorInterface
{
	public function detect(Request $request)
	{
		return $request->getPreferredLanguage();
	}
}
