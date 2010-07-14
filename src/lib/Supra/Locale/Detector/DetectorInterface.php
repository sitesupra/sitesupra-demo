<?php

namespace Supra\Locale\Detector;

use Supra\Controller\Request\RequestInterface,
		Supra\Controller\Response\ResponseInterface,
		Supra\Locale\Data;

/**
 * Locale detector interface
 */
interface DetectorInterface
{
	/**
	 * Sets locale data provider
	 * @param Data $data
	 */
	public function setData(Data $data);

	/**
	 * Detects the current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function detect(RequestInterface $request, ResponseInterface $response);
}