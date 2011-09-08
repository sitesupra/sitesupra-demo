<?php

namespace Supra\Locale\Storage;

use Supra\Locale\Locale;

/**
 * Locale storage abstraction
 */
abstract class StorageAbstraction implements StorageInterface
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