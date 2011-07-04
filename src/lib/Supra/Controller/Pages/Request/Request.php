<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\Http,
		Supra\Controller\Pages\Entity;

/**
 * Page controller request
 */
abstract class Request extends Http
{
	/**
	 * Page data class to be used
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageData';
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $doctrineEntityManager;
	
	/**
	 * @var string
	 */
	private $locale;
	
	/**
	 * @var string
	 */
	private $media = Entity\Layout::MEDIA_SCREEN;
	
	/**
	 * @var Entity\Abstraction\Data
	 */
	private $requestPageData;
	
	/**
	 * @return Entity\Abstraction\Data
	 */
	public function getRequestPageData()
	{
		return $this->requestPageData;
	}
	
	/**
	 * @param Entity\Abstraction\Data $requestPageData
	 */
	public function setRequestPageData(Entity\Abstraction\Data $requestPageData)
	{
		$this->requestPageData = $requestPageData;
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
	
	/**
	 * @return string
	 */
	public function getMedia()
	{
		return $this->media;
	}

	/**
	 * @param string $media
	 */
	public function setMedia($media)
	{
		$this->media = $media;
	}

}
