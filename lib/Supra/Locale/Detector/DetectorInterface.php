<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Locale\Locale;

/**
 * Locale detector interface
 */
interface DetectorInterface
{
	/**
	 * Sets locale data provider
	 * @param Locale $locale
	 */
	public function setLocale(Locale $locale);

	/**
	 * Detects the current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function detect(RequestInterface $request, ResponseInterface $response);
}