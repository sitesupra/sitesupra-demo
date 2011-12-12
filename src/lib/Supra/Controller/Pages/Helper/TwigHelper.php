<?php

namespace Supra\Controller\Pages\Helper;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Uri\Path;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageLocalization;

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
	 * Whether the passed link is actual - is some descendant opened currently
	 * @param string $path
	 * @param boolean $strict
	 * @return boolean
	 */
	public function isActive($path, $strict = false)
	{
		$request = $this->request;
		
		if ( ! $request instanceof PageRequest) {
			return false;
		}
		
		$localization = $request->getPageLocalization();
		
		if ( ! $localization instanceof PageLocalization) {
			return false;
		}
		
		$checkPath = new Path($path);
		$currentPath = $localization->getPath();
		
		if ($strict) {
			if ($checkPath->equals($currentPath)) {
				return true;
			}
		} elseif ($currentPath->startsWith($checkPath)) {
			return true;
		}
		
		return false;
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
