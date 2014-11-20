<?php

namespace Supra\Package\Cms\Pages\Twig;

use Symfony\Component\HttpFoundation\Request;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
//use Supra\ObjectRepository\ObjectRepository;
//use Supra\Locale\LocaleInterface;
//use Supra\Request\RequestInterface;
//use Supra\Controller\Pages\Request\PageRequestEdit;
//use Supra\Uri\Path;
//use Supra\Controller\Pages\Request\PageRequest;
//use Supra\Controller\Pages\Entity\PageLocalization;
//use Supra\Controller\Pages\Entity\Abstraction\Localization;
//use Supra\Response\ResponseContext;
//use Supra\Html\HtmlTag;

/**
 * Helper object for twig processor
 */
class TwigSupraGlobal implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @return \Supra\Core\Locale\LocaleInterface
	 */
	public function getLocale()
	{
		return $this->container->getLocaleManager()->getCurrentLocale();
	}

	/**
	 * @param Request $request
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @throws \Exception 
	 */
	public function getInfo()
	{
		throw new \Exception('Not implemented.');
//		return ObjectRepository::getSystemInfo($this);
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

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

}
