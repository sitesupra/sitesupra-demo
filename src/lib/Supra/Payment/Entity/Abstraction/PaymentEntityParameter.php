<?php

namespace Supra\Payment\Entity\Abstraction;

use Supra\Database;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 * 	"tp" = "Supra\Payment\Entity\Transaction\TransactionParameter", 
 * 	"tpl" = "Supra\Payment\Entity\Transaction\TransactionParameterLogEntry", 
 * 	"rpp" = "Supra\Payment\Entity\RecurringPayment\RecurringPaymentParameter",
 * 	"rppl" = "Supra\Payment\Entity\RecurringPayment\RecurringPaymentParameterLogEntry",
 * 	"rppt" = "Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransactionParameter"
 * })
 */
abstract class PaymentEntityParameter extends Database\Entity
{

	/**
	 * @ManyToOne(targetEntity="PaymentEntity", inversedBy="parameters")
	 * @JoinColumn(name="paymentEntityId", referencedColumnName="id")
	 */
	protected $paymentEntity;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $phaseName = null;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $parameterName = null;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $parameterValue = null;

	/**
	 * @param PaymentEntityParameter $from 
	 */
	protected function copy(PaymentEntityParameter $from)
	{
		$properties = $from->getInstancePropertiesAsArray();

		foreach ($properties as $name => $value) {
			$this->$name = $value;
		}
	}

	/**
	 * @return arary
	 */
	private function getInstancePropertiesAsArray()
	{
		$result = array();

		foreach ($this as $name => $value) {
			$result[$name] = $value;
		}

		return $result;
	}

	/**
	 * @return type 
	 */
	public function getPhaseName()
	{
		return $this->phaseName;
	}

	/**
	 * @return string
	 */
	public function getParameterName()
	{
		return $this->parameterName;
	}

	/**
	 * @return string
	 */
	public function getParameterValue()
	{
		return $this->parameterValue;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->parameterName = $name;
	}

	/**
	 * @param string $value 
	 */
	public function setValue($value)
	{
		$this->parameterValue = $value;
	}

	/**
	 * @param string $phaseName 
	 */
	public function setPhaseName($phaseName)
	{
		$this->phaseName = $phaseName;
	}

}
