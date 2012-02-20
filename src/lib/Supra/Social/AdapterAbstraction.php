<?php

namespace Supra\Social;

use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;

/**
 * 
 */
abstract class AdapterAbstraction
{

	/**
	 * Returns adapter id
	 * @example facebook, twitter, googlePlus
	 * @return string 
	 */
	abstract public function getId();

	/**
	 * 
	 */
	abstract public function getLoginUrl();

	/**
	 * 
	 */
	abstract public function postMessage($params = null);

}