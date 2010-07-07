<?php

namespace Supra\Locale\Storage;

use Supra\Controller\Request\RequestInterface,
		Supra\Controller\Response\ResponseInterface;

/**
 * Interface for storages for current locale
 */
interface StorageInterface
{
	/**
	 * Store the detected locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function store(RequestInterface $request, ResponseInterface $response);

	/**
	 * Sets locale data provider
	 * @param Data $data
	 */
	public function setData(Data $data);
}