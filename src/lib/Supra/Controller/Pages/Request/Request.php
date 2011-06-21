<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\Http,
		Supra\Controller\Pages\Entity\Abstraction\Page;

/**
 * Page controller request
 */
abstract class Request extends Http
{
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $doctrineEntityManager;
	
	/**
	 * @var string
	 */
	private $locale;
	
	/**
	 * @var Page
	 */
	private $requestPage;
	
	/**
	 * @return Page
	 */
	public function getRequestPage()
	{
		return $this->requestPage;
	}
	
	/**
	 * @param Page $requestPage
	 */
	public function setRequestPage(Page $requestPage)
	{
		$this->requestPage = $requestPage;
	}

	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	public function setDoctrineEntityManager(\Doctrine\ORM\EntityManager $em)
	{
		$this->doctrineEntityManager = $em;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getDoctrineEntityManager()
	{
		return $this->doctrineEntityManager;
	}
	
	/**
	 * @param string $locale
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;
	}
	
	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}
}
