<?php

namespace Supra\Controller\Pages\Helper;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Request\PageRequestEdit;

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
	 * Returns if in CMS mode
	 * @return boolean
	 */
	public function isCmsRequest()
	{
		return ($this->request instanceof PageRequestEdit);
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
