<?php

namespace Supra\Payment\Entity\RecurringPayment;

use \DateTime;
use Supra\Payment\Entity\Abstraction\PaymentEntityLogEntry;
use Supra\Payment\Entity\Abstraction\LogEntry;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="recurringPaymentIdIdx", columns={"recurringPaymentId"})
 * })
 */
class RecurringPaymentLogEntry extends RecurringPayment implements LogEntry
{

	/**
	 * @Column(type="datetime", nullable=false)
	 * @var DateTime
	 */
	protected $logTime;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $recurringPaymentId;
	
	/**
	 * @param RecurringPayment $recurringPayment 
	 */
	function __construct(RecurringPayment $recurringPayment)
	{
		$this->copy($recurringPayment);
		parent::__construct();

		$this->logTime = new \DateTime('now');
		$this->recurringPaymentId = $recurringPayment->getId();
	}

	/**
	 * @return string
	 */
	public function getRecurringPaymentId()
	{
		return $this->recurringPaymentId;
	}

}
