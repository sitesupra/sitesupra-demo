<?php

namespace Supra\Core\Locale\Storage;

use Supra\Core\Locale\LocaleInterface;

/**
 * Locale storage abstraction
 */
abstract class AbstractStorage implements StorageInterface
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