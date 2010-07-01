<?php

namespace Supra\Locale;

/**
 * Localization
 */
class Locale
{

	protected $locales = array();

	protected $detectors = array();

	function add($locale)
	{
		$this->locales[] = $locale;
	}

	function addDetector($detector)
	{
		$this->detectors[] = $detector;
	}

	function setCurrent($locale)
	{
		$this->current = $locale;
	}

	function getCurrent()
	{
		return $this->current;
	}
}