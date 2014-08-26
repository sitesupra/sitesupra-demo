<?php

namespace Project\Payment\Dengi\Configuration;

use Project\Payment\Dengi;
use Supra\Configuration\ConfigurationInterface;

class BackendConfiguration implements ConfigurationInterface
{

	/**
	 * @var string
	 */
	public $modeType;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $currencyCode;

	/**
	 * @var string
	 */
	public $backendClass;

	/**
	 * @var Dengi\Backend\BackendAbstraction
	 */
	protected $backendInstance;

	/**
	 * @return Dengi\Backend\BackendAbstraction;
	 */
	public function getBackendInstance()
	{
		return $this->backendInstance;
	}

	/**
	 * 
	 */
	public function configure()
	{
		/* @var $backendInstance Dengi\Backend\BackendAbstraction */
		$backendInstance = new $this->backendClass;
		$backendInstance->setName($this->name);
		$backendInstance->setCurrencyCode($this->currencyCode);
		$backendInstance->setModeType($this->modeType);

		$this->backendInstance = $backendInstance;
	}

}

