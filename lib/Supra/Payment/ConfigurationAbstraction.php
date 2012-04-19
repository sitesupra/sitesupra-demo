<?php

namespace Supra\Payment;

use Supra\Configuration\ConfigurationInterface;
use Supra\Controller\FrontController;
use Supra\Router\UriRouter;
use Supra\Authorization\Exception\ConfigurationException;
use Supra\Payment\Provider\PaymentProviderAbstraction;
use Supra\ObjectRepository\ObjectRepository;

abstract class ConfigurationAbstraction implements ConfigurationInterface
{

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $url;

	/**
	 * @var string
	 */
	public $collection;
	
	/**
	 * @var PaymentProviderAbstraction
	 */
	protected $paymentProvider;
	
	/**
	 * @var string
	 */
	protected $requestControllerClass;
		
	public function configure()
	{
		if (empty($this->id)) {
			throw new ConfigurationException('Payment provider configuration must have "id" key!');
		}

		if (empty($this->url)) {
			throw new ConfigurationException('Payment provider configuration must have "url" key!');
		}

		if (empty($this->collection)) {
			throw new ConfigurationException('Payment provider configuration must have "collection" key!');
		}

		$this->paymentProvider->setId($this->id);
		$this->paymentProvider->setBaseUrl($this->url);

		$paymentProviderCollection = ObjectRepository::getPaymentProviderCollection($this->collection);
		$paymentProviderCollection->add($this->paymentProvider);
		
		$router = new PaymentProviderUriRouter();
		$router->setPath($this->url);
		$router->setPaymentProvider($this->paymentProvider);
		
		FrontController::getInstance()->route($router, $this->requestControllerClass);		
	}

}

