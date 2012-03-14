<?php

namespace Supra\Authentication\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\Authentication\Event\EventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Request\HttpRequest;
use Supra\Authentication\Exception\AuthenticationBanException;

/**
 * Limits unsuccessful login count in time period
 */
class BruteForceAuthenticationAttackProtector implements EventSubscriber
{
	/**
	 * Default failure limit
	 * @var integer
	 */
	public static $failureLimit = 10;
	
	/**
	 * Default ban time in seconds
	 * @var integer
	 */
	public static $banTime = 10;
	
	/**
	 * Failure count local memory
	 * @var integer
	 */
	private $failureCount;
	
	/**
	 * Flag to skip failure increase
	 * @var boolean
	 */
	private $clientBanner = false;
	
	/**
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(EventArgs::preAuthenticate, EventArgs::onAuthenticationFailure);
	}
	
	/**
	 * Loads failure limit configuration
	 * @return integer
	 */
	protected function getFailureLimit()
	{
		return self::$failureLimit;
	}
	
	/**
	 * Get ban time configuration
	 * @return integer
	 */
	protected function getBanTime()
	{
		return self::$banTime;
	}
	
	/**
	 * Fetch IP address from global SERVER variable
	 * @param EventArgs $eventArgs
	 * @return string
	 */
	private function getClientIdentifier(EventArgs $eventArgs)
	{
		$clientId = '';
		
		if ($eventArgs->request instanceof HttpRequest) {
			$clientId = $eventArgs->request->getServerValue('REMOTE_ADDR');
		}
		
		return $clientId;
	}
	
	/**
	 * Generates cache key to store failure count
	 * @param EventArgs $eventArgs
	 * @return string
	 */
	private function getCacheKey(EventArgs $eventArgs)
	{
		return __CLASS__ . '$failures$' . $this->getClientIdentifier($eventArgs);
	}
	
	/**
	 * @param EventArgs $eventArgs
	 * @throws AuthenticationBanException if limit is reached
	 */
	public function preAuthenticate(EventArgs $eventArgs)
	{
		$cacheKey = $this->getCacheKey($eventArgs);
		$cache = ObjectRepository::getCacheAdapter($this);
		$this->failureCount = (int) $cache->fetch($cacheKey);
		$failureLimit = $this->getFailureLimit();
		
		if ($this->failureCount >= $failureLimit) {
			$this->clientBanner = true;
			throw new AuthenticationBanException("Too many login failures");
		}
	}
	
	/**
	 * Increases failure count by 1 and stores inside the cache adapter
	 * @param EventArgs $eventArgs
	 */
	public function onAuthenticationFailure(EventArgs $eventArgs)
	{
		if ($this->clientBanner) {
			return;
		}
		
		$this->failureCount++;
		$cache = ObjectRepository::getCacheAdapter($this);
		$cacheKey = $this->getCacheKey($eventArgs);
		$lifeTime = $this->getBanTime();
		$cache->save($cacheKey, $this->failureCount, $lifeTime);
	}
}
