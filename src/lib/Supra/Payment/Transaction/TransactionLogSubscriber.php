<?php

namespace Supra\Payment\Transaction;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Supra\Payment\Entity\Transaction\TransactionLogEntry;
use Supra\Payment\Entity\Transaction\Transaction;

// Supra\Payment\Entity\Transaction\Transaction
class TransactionLogSubscriber implements EventSubscriber
{

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::postPersist, Events::postUpdate);
	}

	/**
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		//\Log::debug('### postPersist ###', get_class($eventArgs->getEntity()));
		
		if($eventArgs->getEntity() instanceof \Supra\Payment\Entity\Transaction\Transaction) {
			$this->addLogEntry($eventArgs);
		}
	}

	/**
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
		//\Log::debug('### postUpdate ###', get_class($eventArgs->getEntity()));
		
		if($eventArgs->getEntity() instanceof \Supra\Payment\Entity\Transaction\Transaction) {		
			$this->addLogEntry($eventArgs);
		}
	}

	/**
	 * @param LifecycleEventArgs $eventArgs 
	 */
	private function addLogEntry(LifecycleEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$transaction = $eventArgs->getEntity();

		$logEntry = new TransactionLogEntry($transaction);
		$em->persist($logEntry);
		$em->flush();
	}

}
