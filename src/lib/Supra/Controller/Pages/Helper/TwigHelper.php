<?php

namespace Supra\Controller\Pages\Helper;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Request\RequestInterface;

/**
 * Helper object for twig processor
 */
class TwigHelper
{
	/**
	 * @var RequestInterface
	 */
	protected $request;

	/**
	 * @return RequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param RequestInterface $request
	 */
	public function setRequest(RequestInterface $request = null)
	{
		$this->request = $request;
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
}
