<?php

namespace Project\Payment\DummyShop;

use Supra\Configuration\ConfigurationInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Project\Payment\DummyShop\DummyShopEventListener;

class Configuration implements ConfigurationInterface
{

	public function configure()
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$listener = new DummyShopEventListener();

		$events = array(
				PaymentProviderAbstraction::EVENT_PROXY_ACTION,
				PaymentProviderAbstraction::EVENT_PROVIDER_NOTIFICATION_ACTION,
				PaymentProviderAbstraction::EVENT_CUSTOMER_RETURN_ACTION
		);

		$eventManager->listen($events, $listener);
	}

}
