<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\Payment\Entity\TransactionParameter;
use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionType;
use \DateTime;

/**
 * @Entity 
 * @HasLifecycleCallbacks
 */
class Transaction extends Entity
{

	/**
	 * @param string $paymentProviderId 
	 */
	public function setPaymentProviderId($paymentProviderId)
	{
		$this->paymentProviderId = $paymentProviderId;
	}

	/**
	 * @param AbstractUser $user 
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param integer $status 
	 */
	public function setStatus($status)
	{
		TransactionStatus::validate($status);

		$this->status = $status;
	}

	/**
	 * @param float $amount 
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
	}

	/**
	 * @param string $currencyId 
	 */
	public function setCurrencyId($currencyId)
	{
		$this->currencyId = $currencyId;
	}

	public function setType($type)
	{
		TransactionType::validate($type);

		$this->type = $type;
	}

	/**
	 * @param TransactionParameter $parameter 
	 */
	public function addParameter(TransactionParameter $parameter)
	{
		$this->parameters[] = $parameter;
	}

	public function makeAndAddPrameter($phaseName, $parameterName, $parameterValue)
	{
		$parameter = new TransactionParameter();
		
		$parameter->setPhaseName($phaseName);
		$parameter->setName($parameterName);
		$parameter->setValue($parameterValue);
		$parameter->setTransaction($this);

		$this->addParameter($parameter);
	}

	/**
	 * @prePersist
	 */
	public function autoCretionTime()
	{
		$this->creationTime = new DateTime('now');
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @preUpdate
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @return ArrayCollection
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

}
