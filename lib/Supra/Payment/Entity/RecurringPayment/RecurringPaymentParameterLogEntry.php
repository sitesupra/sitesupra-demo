<?php

namespace Supra\Payment\Entity\RecurringPayment;

use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;
use DateTime;
use Supra\Payment\Entity\Abstraction\LogEntry;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="recurringPaymentParameterIdIdx", columns={"recurringPaymentParameterId"})
 * })
 */
class RecurringPaymentParameterLogEntry extends RecurringPaymentParameter implements LogEntry
{

	/**
	 * @Column(type="datetime", nullable=false)
	 * @var DateTime;
	 */
	protected $logTime;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $recurringPaymentParameterId;

	/**
	 * @param RecurringPaymentParameter $recurringPaymentParameter 
	 */
	function __construct(RecurringPaymentParameter $recurringPaymentParameter)
	{
		$this->copy($recurringPaymentParameter);
		parent::__construct();

		$this->logTime = new DateTime('now');
		$this->recurringPaymentParameterId = $recurringPaymentParameter->getId();
	}

	/**
	 * @return string
	 */
	public function getTransactionParameterId()
	{
		return $this->recurringPaymentParameterId;
	}

}
