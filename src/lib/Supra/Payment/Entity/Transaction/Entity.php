<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\Database;
use Supra\User\Entity\AbstractUser;
use Supra\Payment\Entity\TransactionParameter;

/**
 * @MappedSuperclass
 * @Table(indexes={
 * 	@Index(name="statusIdx", columns="status"),
 * 	@Index(name="paymentProviderIdIdx", columns="paymentProviderId") 
 * })
 */
class Entity extends Database\Entity
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
	 * @var DateTime
	 */
	protected $paymentProviderId;

	/**
	 * @Column(type="decimal", precision="10", scale="2", nullable=false)
	 * @var float
	 */
	protected $amount;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $currencyId;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $type;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $userId;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $description;

	/**
	 * @OneToMany(targetEntity="Supra\Payment\Entity\TransactionParameter", mappedBy="transaction")
	 * @var ArrayCollection
	 */
	protected $parameters;

	function __construct()
	{
		parent::__construct();

		$this->parameters = new \Doctrine\Common\Collections\ArrayCollection();
	}

	protected function copy(Entity $from)
	{
		$properties = $from->getPropertiesAsArray();

		foreach ($properties as $name => $value) {
			$this->$name = $value;
		}
	}

	protected function getPropertiesAsArray()
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
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
	}

}

