<?php

namespace Supra\Locale\Storage;

use Supra\Locale\LocaleInterface;

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
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale)
	{
		$this->locale = $locale;
	}
}