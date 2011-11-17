<?php

namespace Supra\Payment\Entity\Order;

use Supra\Database;
use Supra\Payment\Entity\Transaction\Transaction;
use Supra\Payment\Entity\Currency\Currency;
use Supra\User\Entity\AbstractUser;

/**
 * @Entity
 */
class Order extends Database\Entity
{

	/**
	 * @OneToMany(targetEntity="OrderItem", mappedBy="orderId")
	 */
	protected $items;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $userId;

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
	 * @ManyToOne(targetEntity="Supra\Payment\Entity\Transaction\Transaction")
	 * @JoinColumn(name="transactionId", referencedColumnName="id")
	 * @var Transaction
	 */
	protected $transaction;

	/**
	 * @ManyToOne(targetEntity="Supra\Payment\Entity\Currency\Currency")
	 * @JoinColumn(name="currencyId", referencedColumnName="id")
	 * @var Currency
	 */
	protected $currency;

	/**
	 * @ManyToOne(targetEntity="Supra\User\Entity\AbstractUser")
	 * @JoinColumn(name="userId", referencedColumnName="id")
	 * @var AbstractUser
	 */
	protected $user;

	/**
	 * Returns order items.
	 * @return array
	 */
	function getItems()
	{
		return $this->items;
	}

	/**
	 * @return Transaction
	 */
	function getTransaction()
	{
		return $this->transaction;
	}

	/**
	 * @return Currency
	 */
	function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @param Transaction $transaction 
	 */
	function setTransaction(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	/**
	 * @param Currency $currency
	 */
	function setCurrency(Currency $currency)
	{
		$this->currency = $currency;
	}

	/**
	 * @param AbstractUser $user 
	 */
	public function setUser(AbstractUser $user)
	{
		$this->user = $user;
	}

	/**
	 * @return AbstractUser
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Returns sum of all prices of items in order.
	 * @return float
	 */
	public function getTotal()
	{
		$total = 0;

		foreach ($this->items as $item) {
			/* @var $item OrderItem */

			$total = $total + $item->getPrice();
		}

		return $total;
	}

}

