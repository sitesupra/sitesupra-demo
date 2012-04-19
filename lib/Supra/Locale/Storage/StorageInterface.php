<?php

namespace Supra\Locale\Storage;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Locale\Locale;

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
	 * @param Locale $locale
	 */
	public function setLocale(Locale $locale);
}