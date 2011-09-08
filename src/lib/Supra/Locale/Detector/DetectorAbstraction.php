<?php

namespace Supra\Locale\Detector;

use Supra\Locale\Locale;

/**
 * Locale detector abstraction
 */
abstract class DetectorAbstraction implements DetectorInterface
{
	/**
	 * The locale data provider
	 * @var Locale
	 */
	protected $locale;

	/**
	 * Sets locale data provider
	 * @param Locale $locale
	 */
	public function setLocale(Locale $locale)
	{
		$this->locale = $locale;
	}
}