<?php

namespace Supra\Core\Locale\Detector;

use Supra\Core\Locale\LocaleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale detector interface
 */
interface DetectorInterface
{
	/**
	 * Sets locale data provider
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale);

	/**
	 * Detects the current locale
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 * @return string
	 */
	public function detect(Request $request, Response $response);
}