<?php

namespace Supra\Controller\Pages\Twig;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Uri\Path;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Response\ResponseContext;
use Supra\Html\HtmlTag;

/**
 * Helper object for twig processor
 */
class TwigSupraGlobal
{

	/**
	 * @var RequestInterface
	 */
	protected $request;

	/**
	 * @var ResponseContext
	 */
	protected $responseContext;

	/**
	 * @return RequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return ResponseContext
	 */
	public function getResponseContext()
	{
		return $this->responseContext;
	}

	/**
	 * @param RequestInterface $request
	 */
	public function setRequest(RequestInterface $request = null)
	{
		$this->request = $request;
	}

	/**
	 * @param ResponseContext $responseContext 
	 */
	public function setResponseContext(ResponseContext $responseContext = null)
	{
		$this->responseContext = $responseContext;
	}

	/**
	 * @return Locale
	 */
	public function getLocale()
	{
		$locale = ObjectRepository::getLocaleManager($this)
				->getCurrent();

		return $locale;
	}

	/**
	 * @return Info
	 */
	public function getInfo()
	{
		$info = ObjectRepository::getSystemInfo($this);

		return $info;
	}

	/**
	 * Try getting 1) from request 2) from system settings
	 * @return string
	 */
	public function getHost()
	{
		// From request
		if ($this->request instanceof \Supra\Request\HttpRequest) {
			$fromRequest = $this->request->getBaseUrl();

			if ( ! empty($fromRequest)) {
				return $fromRequest;
			}
		}

		// From info package
		return $this->getInfo()
						->getHostName(\Supra\Info::WITH_SCHEME);
	}

}
