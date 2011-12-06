<?php

namespace Project\Payment\DummyShop;

use Supra\Payment\Provider\Event\ProxyEventArgs;
use Supra\Payment\Provider\Event\CustomerReturnEventArgs;
use Supra\Payment\Provider\Event\ProviderNotificationEventArgs;
use Supra\Response\HttpResponse;

class DummyShopEventListener
{

	public function proxy(ProxyEventArgs $args)
	{
		
	}

	public function customerReturn(CustomerReturnEventArgs $args)
	{

	}

	public function providerNotification(ProviderNotificationEventArgs $args)
	{
		
	}

}
