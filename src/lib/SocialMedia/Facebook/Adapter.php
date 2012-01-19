<?php

namespace SocialMedia\Facebook;

use SocialMedia\AdapterAbstraction;
use SocialMedia\Exception\SocialMediaException;
use Supra\ObjectRepository\ObjectRepository;
use SocialMedia\Facebook\Exception\FacebookApiException;
use Supra\User\Entity\User;

class Adapter extends AdapterAbstraction
{

	/**
	 * Facebook API instance
	 * @var Facebook 
	 */
	public $instance;
	private $applicationToken;
	private $userToken;

	/**
	 * @var User 
	 */
	public $cmsUser;

	/**
	 * @var array 
	 */
	static public $requiredPermissions = array(
		'manage_pages',
		'offline_access',
		'publish_stream',
	);

	public function __construct(User $user)
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
		$this->cmsUser = $user;
		$this->userToken = $user->getFacebookAccessToken();
		$this->setAccessToken($user->getFacebookAccessToken());
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
		$userId = $this->cmsUser->getFacebookId();
		if ( ! empty($userId)) {
			return $userId;
		}
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

		$permissions = $this->instance->api('/' . $this->getUserId() . '/permissions', 'GET', array('access_token' => $this->instance->getAccessToken()));

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

	/**
	 * 
	 * @param boolean $cache if cache false, will ignore cache and make api request
	 * @return array 
	 */
	public function getUserPages($cache = true)
	{
		$cache = \Supra\ObjectRepository\ObjectRepository::getCacheAdapter($this);
		$cacheName = $this->getCacheName() . 'user-pages';

		if ($cache) {
			$result = $cache->fetch($cacheName);

			if ( ! empty($result)) {
				return $result;
			}
		}

		$queryResult = $this->instance->api($this->getUserId() . '/accounts', 'GET', array('access_token' => $this->instance->getAccessToken()));

		$pages = array();
		foreach ($queryResult['data'] as $page) {
			if ($page['category'] == 'Application') {
				continue;
			}

			$pages[$page['id']] = $page;
		}

		$cache->save($cacheName, $pages, 300);

		return $pages;
	}

	public function checkUserPage($pageId)
	{
		$pages = $this->getUserPages();
		if (array_key_exists($pageId, $pages)) {
			return true;
		}

		return false;
	}

	public function setAccessToken($token)
	{
		$this->instance->setAccessToken($token);
	}

	public function getApplicationAccessToken()
	{
		if ( ! empty($this->applicationToken)) {
			return $this->applicationToken;
		}
		
		$result = $this->instance->api('/'.$this->getUserId().'/accounts', 'GET', array('access_token' => $this->userToken));
		foreach ($result['data'] as $accountData) {
			if ($accountData['id'] != $this->getAppId()) {
				continue;
			}

			$this->applicationToken = $accountData['access_token'];
		}

	}

}