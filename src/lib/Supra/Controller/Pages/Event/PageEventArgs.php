<?php

namespace Supra\Controller\Pages\Event;

use Doctrine\Common\EventArgs;

class PageEventArgs extends EventArgs
{
	/**
	 * @var array
	 */
	protected $properties = array();
	
	/**
	 * @var string
	 */
	protected $revisionInfo; 
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;
	
	
	/**
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 */
	public function __construct($entityManager = null)
	{
		$this->entityManager = $entityManager;
	}
	
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name) 
	{
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}
		
		return null;
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value)
	{
		$this->properties[$name] = $value;
	}
	
	/**
	 * @param string $info
	 */
	public function setRevisionInfo($info)
	{
		$this->revisionInfo = $info;
	}
	
	/**
	 * @return string
	 */
	public function getRevisionInfo()
	{
		return $this->revisionInfo;
	}
	
	public function setEntityManager(\Doctrine\ORM\EntityManager $em)
	{
		$this->entityManager = $em;
	}
	
	public function getEntityManager()
	{
		return $this->entityManager;
	}
}
