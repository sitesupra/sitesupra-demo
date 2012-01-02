<?php

namespace Supra\Controller\Pages\Helper;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Uri\Path;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Response\ResponseContext;

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
	public function setResponseContext(ResponseContext $responseContext)
	{
		$this->responseContext = $responseContext;
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
		// Check if path is relative
		$pathData = parse_url($path);
		if ( ! empty($pathData['scheme'])
				|| ! empty($pathData['host'])
				|| ! empty($pathData['port'])
				|| ! empty($pathData['user'])
				|| ! empty($pathData['pass'])
		) {
			return false;
		}

		$path = $pathData['path'];

		$request = $this->request;

		if ( ! $request instanceof PageRequest) {
			return false;
		}

		$localization = $request->getPageLocalization();

		if ( ! $localization instanceof PageLocalization) {
			return false;
		}

		// Remove locale prefix
		$localeId = $localization->getLocale();
		$localeIdQuoted = preg_quote($localeId);
		$path = preg_replace('#^(/?)' . $localeIdQuoted . '(/|$)#', '$1', $path);

		$checkPath = new Path($path);
		$currentPath = $localization->getPath();

		if (is_null($currentPath)) {
			return false;
		}

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
