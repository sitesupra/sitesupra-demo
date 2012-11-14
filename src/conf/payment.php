<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Payment\PaymentLogSubscriber;
use Supra\Payment\Provider\PaymentProviderCollection;

$entityManager = ObjectRepository::getEntityManager('');
$eventManager = $entityManager->getEventManager();
$subscriber = new PaymentLogSubscriber();
$eventManager->addEventSubscriber($subscriber);

$paymentProviderCollection = new PaymentProviderCollection();
ObjectRepository::setDefaultPaymentProviderCollection($paymentProviderCollection);

