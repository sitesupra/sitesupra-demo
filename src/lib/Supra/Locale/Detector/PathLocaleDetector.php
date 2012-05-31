<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Request\HttpRequest;
use Supra\Log\Log;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Path locale detector
 */
class PathLocaleDetector extends DetectorAbstraction
{
	/**
	 * Detects the current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function detect(RequestInterface $request, ResponseInterface $response)
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