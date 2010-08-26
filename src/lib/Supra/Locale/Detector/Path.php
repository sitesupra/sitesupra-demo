<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface,
		Supra\Response\ResponseInterface;

/**
 * Path locale detector
 */
class Path extends DetectorAbstraction
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