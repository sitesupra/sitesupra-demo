<?php

namespace Supra\Payment\Entity\Abstraction;

use \DateTime;
use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *	"t" = "Supra\Payment\Entity\Transaction\Transaction", 
 *	"tl" = "Supra\Payment\Entity\Transaction\TransactionLogEntry", 
 *	"rp" = "Supra\Payment\Entity\RecurringPayment\RecurringPayment",
 * 	"rpl" = "Supra\Payment\Entity\RecurringPayment\RecurringPaymentLogEntry",
 * 	"rpt" = "Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransaction"
 * })
 */
abstract class PaymentEntity extends Database\Entity
{

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $status;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $paymentProviderId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $userId;

	/**
	 * @OneToMany(targetEntity="PaymentEntityParameter", mappedBy="paymentEntity")
	 * @var ArrayCollection
	 */
	protected $parameters;

	function __construct()
	{
		parent::__construct();

		$this->parameters = new ArrayCollection();
	}

	/**
	 * @param PaymentEntity $from 
	 */
	protected function copy(PaymentEntity $from)
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
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @return string
	 */
	public function getPaymentProviderId()
	{
		return $this->paymentProviderId;
	}

	/**
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * @prePersist
	 */
	public function autoCretionTime()
	{
		$this->creationTime = new DateTime('now');
	}

	/**
	 * @param integer type $status 
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * @param string $paymentProviderId 
	 */
	public function setPaymentProviderId($paymentProviderId)
	{
		$this->paymentProviderId = $paymentProviderId;
	}

	/**
	 * @param string $userId 
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param PaymentEntityParameter $parameter 
	 */
	public function addParameter(PaymentEntityParameter $parameter)
	{
		$this->parameters[] = $parameter;
	}

	/**
	 * @return PaymentEntityParameter
	 */
	abstract function createParameter();

	/**
	 * @param string $phaseName
	 * @param array $parameters 
	 */
	public function addToParameters($phaseName, $parameters)
	{
		if (is_array($parameters)) {
			
			foreach ($parameters as $key => $value) {

				$parameter = $this->createParameter();

				$parameter->setPhaseName($phaseName);
				$parameter->setName($key);
				$parameter->setValue($value);

				$this->addParameter($parameter);
			}
		}
	}

	/**
	 * @preUpdate
	 * @prePersist
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @prePersist
	 */
	public function autoCreationTime()
	{
		$this->creationTime = new DateTime('now');
	}

}
