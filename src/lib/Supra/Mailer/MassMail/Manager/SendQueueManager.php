<?php
namespace Supra\Mailer\MassMail\Manager;

use Supra\Mailer\MassMail\Entity;

class SendQueueManager extends MassMailManager
{
	public function __construct($entityManager)
	{
		parent::__construct($entityManager);
	}
	
	
	public function createQueueItem()
	{
		$sendQueueItem = new Entity\SendQueueItem();
		$this->entityManager->persist($sendQueueItem);
		
		return $sendQueueItem;
	}

}

