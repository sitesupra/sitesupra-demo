<?php

namespace Supra\Payment;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\Transaction\TransactionLogEntry;
use Supra\Payment\Entity\Transaction\TransactionParameter;
use Supra\Payment\Entity\Transaction\TransactionParameterLogEntry;
use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentParameter;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentLogEntry;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentParameterLogEntry;
use Supra\Payment\Entity\Abstraction\LogEntry;

class PaymentLogSubscriber implements EventSubscriber
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
//		\Log::debug('### postPersist ###', get_class($eventArgs->getEntity()));

		$entity = $eventArgs->getEntity();

		if ($entity instanceof LogEntry) {
			return;
		}

		if ($entity instanceof Transaction) {
			$this->addTransactionLogEntry($eventArgs);
		} else if ($entity instanceof TransactionParameter) {
			$this->addTransactionParameterLogEntry($eventArgs);
		} else if ($entity instanceof RecurringPayment) {
			$this->addRecurringPaymentLogEntry($eventArgs);
		} else if ($entity instanceof RecurringPaymentPrameter) {
			$this->addRecurringPaymentParameterLogEntry($eventArgs);
		}
	}

	/**
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
//		\Log::debug('### postUpdate ###', get_class($eventArgs->getEntity()));

		$entity = $eventArgs->getEntity();

		if ($entity instanceof LogEntry) {
			return;
		}

		if ($entity instanceof Transaction) {
			$this->addTransactionLogEntry($eventArgs);
		} else if ($entity instanceof TransactionParameter) {
			$this->addTransactionParameterLogEntry($eventArgs);
		} else if ($entity instanceof RecurringPayment) {
			$this->addRecurringPaymentLogEntry($eventArgs);
		} else if ($entity instanceof RecurringPaymentPrameter) {
			$this->addRecurringPaymentParameterLogEntry($eventArgs);
		}
	}

	/**
	 * @param LifecycleEventArgs $eventArgs 
	 */
	private function addTransactionLogEntry(LifecycleEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$transaction = $eventArgs->getEntity();

		$logEntry = new TransactionLogEntry($transaction);
		
        $em->persist($logEntry);
        
		$em->flush();
	}

	private function addTransactionParameterLogEntry(LifecycleEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$transactionParameter = $eventArgs->getEntity();

		$logEntry = new TransactionParameterLogEntry($transactionParameter);
		$em->persist($logEntry);
		$em->flush();
	}

	private function addRecurringPaymentLogEntry(LifecycleEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$recurringPayment = $eventArgs->getEntity();

		$logEntry = new RecurringPaymentLogEntry($recurringPayment);
		$em->persist($logEntry);
		$em->flush();
	}

	private function addRecurringPaymentParameterLogEntry(LifecycleEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$recurringPaymentParameter = $eventArgs->getEntity();

		$logEntry = new RecurringPaymentParameterLogEntry($recurringPaymentParameter);
		$em->persist($logEntry);
		$em->flush();
	}

}
