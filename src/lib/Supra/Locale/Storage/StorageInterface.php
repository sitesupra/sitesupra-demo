<?php

namespace Supra\Locale\Storage;

use Supra\Request\RequestInterface,
		Supra\Response\ResponseInterface,
		Supra\Locale\Data;

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
	 * @param Data $data
	 */
	public function setData(Data $data);
}