<?php

namespace SocialMedia\Facebook;

use SocialMedia\AdapterAbstraction;
use SocialMedia\Exception\SocialMediaException;
use Supra\ObjectRepository\ObjectRepository;
use SocialMedia\Facebook\Exception\FacebookApiException;

class Adapter extends AdapterAbstraction
{

	/**
	 * Facebook API instance
	 * @var Facebook 
	 */
	public $instance;
	static public $requiredPermissions = array(
		'manage_pages',
		'offline_access',
		'publish_stream',
	);

	public function __construct()
	{
		// currently all configuration in supra.ini
		$appId = null;
		$appSecret = null;

		try {
			$appId = ObjectRepository::getIniConfigurationLoader($this)->getValue('social', 'facebook.app.id');
			$appSecret = ObjectRepository::getIniConfigurationLoader($this)->getValue('social', 'facebook.app.secret');
		} catch (ConfigurationMissing $exc) {
			// currently nothing here to not annoy with warn messages
		}

		$config = array(
			'appId' => $appId,
			'secret' => $appSecret,
			'cookie' => true,
		);

		$this->instance = new Facebook($config);
	}

	public function getId()
	{
		return 'facebook';
	}

	public function getLoginUrl()
	{
		return $this->instance->getLoginUrl();
	}

	public function getAppId()
	{
		return $this->instance->getAppId();
	}

	public function getUserId()
	{
		return $this->instance->getUser();
	}

	public function postMessage($params = null)
	{
		try {
			$this->instance->api('/me/feed', 'POST', array(
				'link' => 'http://google.lv/',
				'name' => 'Hellow World',
				'description' => 'description',
				'picture' => 'http://a4.mzstatic.com/us/r1000/080/Purple/52/29/76/mzl.hzhjgono.png',
			));
		} catch (FacebookApiException $exc) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 * @throws FacebookApiException
	 */
	public function getUserData()
	{
		return $this->instance->api('/me');
	}

	/**
	 * @return Facebook 
	 */
	public function getInstance()
	{
		return $this->instance;
	}

	public function getPermissonList()
	{
		$cache = \Supra\ObjectRepository\ObjectRepository::getCacheAdapter($this);
		$cacheName = $this->getCacheName() . 'permissions';
		$result = $cache->fetch($cacheName);

		if ( ! empty($result)) {
			return $result;
		}

		$permissions = $this->instance->api('/me/permissions', 'GET', array('access_token' => $this->instance->getAccessToken()));

		$cache->save($cacheName, $permissions, 60);

		return $permissions;
	}

	/**
	 * @throws FacebookApiException
	 * @return boolean
	 */
	public function checkAppPermissions()
	{
		$permissionsList = $this->getPermissonList();

		if (empty($permissionsList['data'][0])) {
			throw new FacebookApiException('Empty permission list');
		}

		$requiredPermissions = self::$requiredPermissions;
		$permissionsList = array_keys($permissionsList['data'][0]);

		foreach ($requiredPermissions as $permission) {
			if ( ! in_array($permission, $permissionsList)) {

				$message = "Permission: {$permission} was not found in permissons which user allowed";

				throw new FacebookApiException($message);
			}
		}
	}

	private function getCacheName()
	{
		return $this->getId() . '-' . $this->instance->getAppId();
	}
	
	public function getUserPages() {
		$cache = \Supra\ObjectRepository\ObjectRepository::getCacheAdapter($this);
		$cacheName = $this->getCacheName() . 'user-pages';
		$result = $cache->fetch($cacheName);

		if ( ! empty($result)) {
			return $result;
		}

		$queryResult = $this->instance->api($this->getUserId() . '/accounts', 'GET', array('access_token' => $this->instance->getAccessToken()));
		
		$pages = array();
		foreach ($queryResult['data'] as $page) {
			if ($page['category'] == 'Application') {
				continue;
			}
			
			$pages[] = $page;
			
		}
		
		$cache->save($cacheName, $pages, 3600);

		return $pages;
	}
	

}