<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\Payment\Transaction\TransactionStatus;
use Supra\Payment\Transaction\TransactionType;
use Supra\Payment\Entity\Abstraction\PaymentEntity;
use \DateTime;

/**
 * @Entity 
 */
class Transaction extends PaymentEntity
{

	/**
	 * @Column(type="string", nullable=false)
	 * @var float
	 */
	protected $amount;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $currencyId;
	
	/**
	 * @OneToMany(targetEntity="TransactionParameter", mappedBy="transaction")
	 * @var ArrayCollection
	 */
	protected $parameters;
	
	/**
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @param float $amount 
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
	}

	/**
	 * @return string
	 */
	public function getCurrencyId()
	{
		return $this->currencyId;
	}

	/**
	 * @param string $currencyId 
	 */
	public function setCurrencyId($currencyId)
	{
		$this->currencyId = $currencyId;
	}

	/**
	 * @param integer $type 
	 */
	public function setType($type)
	{
		TransactionType::validate($type);

		$this->type = $type;
	}

	/**
	 * @return TransactionParameter 
	 */
	public function createParameter()
	{
		$parameter = new TransactionParameter();

		$parameter->setTransaction($this);

		return $parameter;
	}

}
