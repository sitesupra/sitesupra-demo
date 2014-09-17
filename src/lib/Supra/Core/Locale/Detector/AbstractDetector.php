<?php

namespace Supra\Core\Locale\Detector;

use Supra\Core\Locale\LocaleInterface;

/**
 * Locale detector abstraction
 */
abstract class AbstractDetector implements DetectorInterface
{
	/**
	 * The locale data provider
	 * @var LocaleInterface
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