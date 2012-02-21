<?php

namespace Supra\Payment\Entity\RecurringPayment;

use \DateTime;
use Supra\Payment\Entity\Abstraction\PaymentEntity;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class RecurringPayment extends PaymentEntity
{

	/**
	 * @OneToMany(targetEntity="RecurringPaymentParameter", mappedBy="recurringPayment")
	 * @var ArrayCollection
	 */
	protected $parameters;

	/**
	 * @Column(type="decimal", precision=10, scale=2, nullable=false)
	 * @var float
	 */
	protected $amount;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $currencyId;

	/**
	 * @OneToMany(targetEntity="RecurringPaymentTransaction", mappedBy="recurringPayment")
	 * @var ArrayCollection
	 */
	protected $transactions;

	/**
	 * @OneToOne(targetEntity="RecurringPaymentTransaction")
	 * @JoinColumn(name="lastRecurringPaymentTransactionId", referencedColumnName="id")
	 * @var Transaction
	 */
	protected $lastTransaction;
	
	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $gracePeriodLength;
	
	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $paymentReminderOffset;
	
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
	 * @return string
	 */
	public function getBillingDescription()
	{
		return $this->billingDescription;
	}

	/**
	 * @param string $billingDescription 
	 */
	public function setBillingDescription($billingDescription)
	{
		$this->billingDescription = $billingDescription;
	}

	/**
	 * @return RecurringPaymentParameter 
	 */
	public function createParameter()
	{
		$parameter = new RecurringPaymentParameter();

		$parameter->setRecurringPayment($this);

		return $parameter;
	}

	public function getTransactions()
	{
		return $this->transactions;
	}

	/**
	 * @return RecurringPaymentTransaction
	 */
	public function getLastTransaction()
	{
		return $this->lastTransaction;
	}

	/**
	 * @param RecurringPaymentTransaction $lastTransaction 
	 */
	public function setLastTransaction(RecurringPaymentTransaction $lastTransaction)
	{
		$this->lastTransaction = $lastTransaction;
	}

	public function addTransaction(RecurringPaymentTransaction $transaction)
	{
		$transaction->setRecurringPayment($this);
		$transaction->setCurrencyId($this->getCurrencyId());
		$transaction->setUserId($this->getUserId());
		
		$this->transactions[] = $transaction;
		$this->setLastTransaction($transaction);
	}

	/**
	 * @return integer
	 */
	public function getGracePeriodLength()
	{
		return $this->gracePeriodLength;
	}

	/**
	 * @param integer $gracePeriodLength 
	 */
	public function setGracePeriodLength($gracePeriodLength)
	{
		$this->gracePeriodLength = $gracePeriodLength;
	}

	/**
	 * @return integer
	 */
	public function getPaymentReminderOffset()
	{
		return $this->paymentReminderOffset;
	}

	/**
	 * @param integer $paymentReminderOffset 
	 */
	public function setPaymentReminderOffset($paymentReminderOffset)
	{
		$this->paymentReminderOffset = $paymentReminderOffset;
	}

}
