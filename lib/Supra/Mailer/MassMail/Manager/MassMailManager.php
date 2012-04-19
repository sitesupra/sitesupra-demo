<?php
namespace Supra\Mailer\MassMail\Manager;

use Supra\ObjectRepository\ObjectRepository;

class MassMailManager
{
	/**
	 * @var Doctrine\ORM\EntityManager;
	 */
	protected $entityManager;

	protected $log;
	
	function __construct($entityManager)
	{
		$this->setEntityManager($entityManager);
		$this->log = ObjectRepository::getLogger($this);
	}

	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}

}
