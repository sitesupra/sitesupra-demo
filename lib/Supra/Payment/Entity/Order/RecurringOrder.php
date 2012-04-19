<?php

namespace Supra\Payment\Entity\Order;

use Supra\Payment\Entity\RecurringPayment\RecurringPayment;
use Supra\Payment\Order\OrderStatus;
use Supra\Payment\Order\RecurringOrderPeriodDimension;

/**
 * @Entity
 */
class RecurringOrder extends Order
{

	/**
	 * @OneToOne(targetEntity="Supra\Payment\Entity\RecurringPayment\RecurringPayment")
	 * @JoinColumn(name="recurringPaymentId", referencedColumnName="id")
	 * @var RecurringPayment
	 */
	protected $recurringPayment;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $billingDescription;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $periodLength;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $periodDimension;

	/**
	 * @return integer
	 */
	public function getPeriodDimension()
	{
		return $this->periodDimension;
	}

	/**
	 * @param integer $periodDimension 
	 */
	public function setPeriodDimension($periodDimension)
	{
		RecurringOrderPeriodDimension::validate($periodDimension);

		$this->periodDimension = $periodDimension;
	}

	/**
	 * @return string
	 */
	public function getPeriodLength()
	{
		return $this->periodLength;
	}

	/**
	 * @param string $periodLength 
	 */
	public function setPeriodLength($periodLength)
	{
		$this->periodLength = $periodLength;
	}

	/**
	 * @return RecurringPayment
	 */
	public function getRecurringPayment()
	{
		return $this->recurringPayment;
	}

	/**
	 * @param RecurringPayment $recurringPayment 
	 */
	public function setRecurringPayment($recurringPayment)
	{
		$this->recurringPayment = $recurringPayment;
	}

	/**
	 * @return string
	 */
	public function getPaymentProviderId()
	{
		$paymentProviderId = null;

		if ( ! empty($this->recurringPayment)) {
			$paymentProviderId = $this->recurringPayment->getPaymentProviderId();
		}
		return $paymentProviderId;
	}

	public function addToPaymentEntityParameters($phaseName, $data)
	{
		$recurringPayment = $this->getRecurringPayment();

		$recurringPayment->addToParameters($phaseName, $data);
	}

	/**
	 * @param integer $status 
	 */
	public function setStatus($status)
	{
		OrderStatus::validate($status);
		$this->status = $status;
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
	 * @param string $phaseName
	 * @param string $name
	 * @return mixed
	 */
	public function getPaymentEntityParameterValue($phaseName, $name)
	{
		$recurringPayment = $this->getRecurringPayment();

		if (empty($recurringPayment)) {
			throw new Exception\RuntimeException('Ŗecurring payment entity not set.');
		}

		$value = $recurringPayment->getParameterValue($phaseName, $name);

		return $value;
	}

	/**
	 * @return string
	 */
	public function getPaymentEntityId()
	{
		$paymentEntityId = $this->getRecurringPayment()
				->getId();

		return $paymentEntityId;
	}

}
