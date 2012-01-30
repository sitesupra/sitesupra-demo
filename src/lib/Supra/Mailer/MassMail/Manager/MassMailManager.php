<?php

namespace Supra\Mailer\MassMail\Manager;

class MassMailManager
{
	/**
	 * @var Doctrine\ORM\EntityManager;
	 */
	protected $entityManager;

	function __construct($entityManager)
	{
		$this->setEntityManager($entityManager);
	}

	public function setEntityManager($entityManager)
	{
		$this->entityManager = $entityManager;
	}

}
