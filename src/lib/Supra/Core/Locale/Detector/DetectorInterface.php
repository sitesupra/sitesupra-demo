<?php

namespace Supra\Core\Locale\Detector;

use Symfony\Component\HttpFoundation\Request;

/**
 * Locale detector interface
 */
interface DetectorInterface
{
	/**
	 * Detects the current locale
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return string
	 */
	public function detect(Request $request);
}