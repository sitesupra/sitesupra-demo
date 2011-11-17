<?php

namespace Project\Payment\DummyPay\Action;

use Supra\Payment\Action\ProxyActionAbstraction;
use Supra\Payment\Order\OrderProvider;
use Supra\Payment\Entity\Order\Order;
use Supra\Payment\Entity\Transaction\Transaction;

class ProxyAction extends ProxyActionAbstraction
{

	public function execute()
	{
		$orderTransaction = $this->order->getTransaction();

		if ( ! empty($orderTransaction)) { // if order already started transaction 
			$this->handleResubmit();
		}
		else {
			$this->startNewOrder();
		}
	}

	private function getFormData()
	{
		
	}

	public function startNewOrder()
	{
		$postUrl = $this->getPaymentProvider()->getProxyActionUrl();
		
		$formData = $this->getFormData();
		
	}

	public function handleResubmit()
	{
		
	}

}
