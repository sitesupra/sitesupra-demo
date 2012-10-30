<?php

namespace Supra\Locale\Detector;

use Supra\Locale\LocaleInterface;

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
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale)
	{
		$this->locale = $locale;
	}
}