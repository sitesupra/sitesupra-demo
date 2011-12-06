<?php

namespace Supra\Controller\Pages\Event;

use Doctrine\Common\EventArgs;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

class PagePublishEventArgs extends EventArgs
{
	/**
	 * @var string
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $localization;
	
	/**
	 * @var array
	 */
	protected $blockIdCollection = array();
	
	/**
	 * @var array
	 */
	protected $blockPropertyIdCollection = array();
	
	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $entityManager;
	
	
	public function setUserId($userId)
	{
		$this->user = $userId;
	}
	public function setLocalizationId($localizationId)
	{
		$this->localization = $localizationId;
	}
	
	public function setBlockIdCollection($blockIdCollection)
	{
		$this->blockIdCollection = $blockIdCollection;
	}
	
	public function setBlockPropertyIdCollection($blockPropertyIdCollection)
	{
		$this->blockPropertyIdCollection = $blockPropertyIdCollection;
	}
	
	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	public function getBlockIdCollection()
	{
		return $this->blockIdCollection;
	}
	
	public function getBlockPropertyIdCollection()
	{
		return $this->blockPropertyIdCollection;
	}
	
	public function getUserId()
	{
		return $this->user;
	}
	
	public function getLocalizationId()
	{
		return $this->localization;
	}
	
	public function getEntityManager()
	{
		return $this->entityManager;
	}
}
