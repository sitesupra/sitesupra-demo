<?php

namespace Supra\Locale\Detector;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;
use Supra\Request\HttpRequest;
use Supra\Log\Log;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Request parameter locale detector
 */
class ParameterLocaleDetector extends DetectorAbstraction
{
	/**
	 * Parameter name used to pass the locale ID
	 * @var string
	 */
	private $parameterName = 'locale';
	
	/**
	 * Detects the current locale
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return string
	 */
	public function detect(RequestInterface $request, ResponseInterface $response)
	{
		/* @var $request HttpRequest */
		if ( ! ($request instanceof HttpRequest)) {
			Log::warn('Request must be instance of Http request object to use path locale detection');
			return;
		}
		
		$localeManager = ObjectRepository::getLocaleManager($this);
	
		$post = $request->getPost();
		$query = $request->getQuery();
		$localeId = null;
		
		if ( ! $post->isEmpty($this->parameterName)) {
			$localeId = $post->get($this->parameterName);
		} else {
			$localeId = $query->get($this->parameterName, null);
		}
		
		if ( ! is_null($localeId)) {
			if ($localeManager->exists($localeId, false)) {
				return $localeId;
			}
		}
		
		return null;
	}
}