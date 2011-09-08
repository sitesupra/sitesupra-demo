<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;

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
		//TODO: do functionality
	}
}