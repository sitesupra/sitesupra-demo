<?php

namespace Supra\Payment\Entity\Transaction;

use Supra\Database;
use Supra\User\Entity\AbstractUser;

/**
 * @MappedSuperclass
 * @Table(indexes={
 * 	@Index(name="statusIdx", columns="status"),
 * 	@Index(name="paymentProviderIdIdx", columns="paymentProviderId") 
 * })
 */
class Entity extends Database\Entity
{

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
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $paymentProviderAccount;

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
	 * @ManyToOne(targetEntity="Supra\User\Entity\AbstractUser")
	 * @JoinColumn(name="userId", referencedColumnName="id")
	 * @var AbstractUser
	 */
	protected $user;

	/**
	 * @return AbstractUser
	 */
	public function getUser()
	{
		return $this->user;
	}

}

