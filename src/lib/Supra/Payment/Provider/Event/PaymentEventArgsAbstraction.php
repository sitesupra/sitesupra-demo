<?php

namespace Supra\Payment\Provider\Event;

use Supra\Payment\Entity\Order\Order;
use Supra\Event\EventArgs;

abstract class PaymentEventArgsAbstraction extends EventArgs
{

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @param Order $order 
	 */
	public function setOrder(Order $order)
	{
		$this->order = $order;
	}

	/**
	 * @return Order
	 */
	public function getOrder()
	{
		return $this->order;
	}

}
