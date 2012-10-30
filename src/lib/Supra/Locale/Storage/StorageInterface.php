<?php

namespace Supra\Locale\Storage;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Locale\LocaleInterface;

/**
 * Interface for storages for current locale
 */
interface StorageInterface
{
	/**
	 * Store the detected locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @param string $localeIdentifier
	 */
	public function store(RequestInterface $request, ResponseInterface $response, $localeIdentifier);

	/**
	 * Sets locale data provider
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale);
}