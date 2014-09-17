<?php

namespace Supra\Core\Locale\Detector;

use Supra\Log\Log;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Path locale detector
 */
class PathLocaleDetector extends AbstractDetector
{
	/**
	 * Detects the current locale
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 * @return string
	 */
	public function detect(Request $request, Response $response)
	{
		/* @var $request HttpRequest */
		if ( ! ($request instanceof HttpRequest)) {
			Log::warn('Request must be instance of Http request object to use path locale detection');
			return;
		}
		
		$localeManager = ObjectRepository::getLocaleManager($this);
	
		$path = $request->getPath();
		$list = $path->getPathList();
		
		if (!empty($list) && !empty($list[0])) {
			$localeId = $list[0];
			
			if ($localeManager->exists($localeId, false) && $localeManager->isActive($localeId)) {
				$path->setBasePath(new \Supra\Uri\Path($localeId));
				return $localeId;
			}
		}
		
		return null;
	}
}