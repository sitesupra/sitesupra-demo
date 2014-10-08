<?php

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;

class PathDetector implements DetectorInterface
{
	public function detect(Request $request)
	{
		return $request->attributes->get('_locale');
	}
}