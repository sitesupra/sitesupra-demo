<?php

namespace SocialMedia\Facebook;

use SocialMedia\AdapterAbstraction;
use SocialMedia\Exception\SocialMediaException;
use Supra\ObjectRepository\ObjectRepository;
use SocialMedia\Facebook\Exception\FacebookApiException;
use Supra\User\Entity\User;
use Supra\User\Entity\UserFacebookData;
use Supra\User\Entity\UserFacebookPage;
use Supra\User\Entity\UserFacebookPageTab;

class Adapter extends AdapterAbstraction
{

	/**
	 * Facebook API instance
	 * @var Facebook 
	 */
	public $instance;

	/**
	 *
	 * @var string 
	 */
	private $applicationToken;

	/**
	 *
	 * @var string 
	 */
	private $userToken;

	/**
	 *
	 * @var string 
	 */
	private $cmsUser;

	/**
	 * @var UserFacebookData 
	 */
	public $facebookData;

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
		$this->instance->clearAllData();
		$this->cmsUser = $user;

		$repo = ObjectRepository::getEntityManager($this)->getRepository('\Supra\User\Entity\UserFacebookData');
		$facebookData = $repo->findOneByUser($user->getId());

		if ($facebookData instanceof UserFacebookData) {
			$this->facebookData = $facebookData;

			$userToken = $facebookData->getFacebookAccessToken();
			if ( ! empty($userToken) && $facebookData->isActive()) {
				$this->userToken = $userToken;
				$this->setAccessToken($userToken);
			}
		}
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
		if ($this->facebookData instanceof UserFacebookData) {
			$userId = $this->facebookData->getFacebookUserId();
			if ( ! empty($userId)) {
				return $userId;
			}
		}

		return $this->instance->getUser();
	}

	/**
	 * Post message to wall
	 * @param array $params
	 * @see http://d2o0t5hpnwv4c1.cloudfront.net/1097_fbapi/post_breakdown.png
	 * @see https://developers.facebook.com/docs/reference/api/page/#feed
	 * $defaultParams = array(
	 *     // Post message. Required
	 *     'message' => 'Message',
	 *     //Post URL. Required
	 *     'link' => 'http://sitesupra.com/',
	 *     // Post thumbnail image
	 *     'picture' => 'http://sitesupra.com/images/maintanance_logo.png',
	 *     // Post name
	 *     'name' => '',
	 *     // Post caption
	 *     'caption' => '',
	 *     // Post description
	 *     'description' => '',
	 *     // Post actions
	 *     'actions' => array(),
	 *     // Post privacy settings
	 *    'privacy' => '',
	 * );
	 * @return type 
	 */
	public function postMessage($params = array())
	{
		if ( ! isset($params['message']))
			return false;

		try {
			$newPostId = $this->instance->api('/me/feed', 'POST', $params);
		} catch (FacebookApiException $exc) {
			// TODO: if SSL Connection timeout, add message to queue.
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

	/**
	 *
	 * @param boolean $caching
	 * @return array 
	 */
	public function getPermissonList($caching = true)
	{
		$cache = \Supra\ObjectRepository\ObjectRepository::getCacheAdapter($this);
		$cacheName = $this->getCacheName() . 'permissions';
		
		if($caching) {
			$result = $cache->fetch($cacheName);

			if ( ! empty($result)) {
				return $result;
			}
		}

		$permissions = $this->instance->api('/' . $this->getUserId() . '/permissions', 'GET', array('access_token' => $this->instance->getAccessToken()));

		$cache->save($cacheName, $permissions, 60);

		return $permissions;
	}

	/**
	 * @throws FacebookApiException
	 * @return boolean
	 */
	public function checkAppPermissions($cache = true)
	{
		$permissionsList = $this->getPermissonList($cache);

		if (empty($permissionsList['data'][0])) {
			throw new FacebookApiException(array(
				'error_msg' => 'Empty permission list'
			));
		}

		$permissionsList = array_keys($permissionsList['data'][0]);

		foreach (self::$requiredPermissions as $permission) {
			if ( ! in_array($permission, $permissionsList)) {

				$message = "Permission: {$permission} was not found in permissons which user allowed";

				throw new FacebookApiException(array(
					'error_msg' => $message
				));
			}
		}
	}

	/**
	 * Generates cache name from "CMS" user id and 
	 * @return string 
	 */
	private function getCacheName()
	{
		return $this->cmsUser->getId() . '-' . $this->getId() . '-' . $this->instance->getAppId();
	}

	/**
	 * Returns all user pages
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

	/**
	 * Check if current user has page with provided id
	 * @param string $pageId
	 * @return boolean 
	 */
	public function checkUserPage($pageId)
	{
		$pages = $this->getUserPages();
		if (array_key_exists($pageId, $pages)) {
			return true;
		}

		return false;
	}

	/**
	 * Sets access token
	 * @param string $token 
	 */
	public function setAccessToken($token)
	{
		$this->instance->setAccessToken($token);
	}

	/**
	 * Returns application access token
	 * @return string 
	 */
	public function getApplicationAccessToken()
	{
		if ( ! empty($this->applicationToken)) {
			return $this->applicationToken;
		}

		$result = $this->instance->api('/' . $this->getUserId() . '/accounts', 'GET', array('access_token' => $this->userToken));
		foreach ($result['data'] as $accountData) {
			if ($accountData['id'] != $this->getAppId()) {
				continue;
			}

			$this->applicationToken = $accountData['access_token'];
		}
	}

	/**
	 * Returns facebook user page 
	 * @param string $pageId
	 * @param boolean $cache
	 * @return array 
	 */
	public function getUserPage($pageId, $cache = true)
	{

		$cache = \Supra\ObjectRepository\ObjectRepository::getCacheAdapter($this);
		$cacheName = $this->getCacheName() . 'page-' . $pageId;

		if ($cache) {
			$result = $cache->fetch($cacheName);

			if ( ! empty($result)) {
				return $result;
			}
		}

		$queryResult = $this->instance->api($pageId, 'GET', array('access_token' => $this->instance->getAccessToken()));

		if ( ! empty($queryResult)) {
			$cache->save($cacheName, $queryResult, 300);
		}

		return $queryResult;
	}

	/**
	 * Adds tab to facebook page
	 * @param UserFacebookPageTab $tab 
	 */
	public function addTabToPage(UserFacebookPageTab $tab)
	{
		$this->toggleTabOnPage($tab, 'add');
	}

	/**
	 * Removes tab from facebook page
	 * @param UserFacebookPageTab $tab 
	 */
	public function removeTabFromPage(UserFacebookPageTab $tab)
	{
		$this->toggleTabOnPage($tab, 'remove');
	}

	/**
	 * Adds or removes tab from facebook page
	 * @param UserFacebookPageTab $tab
	 * @param string $action add/remove
	 */
	private function toggleTabOnPage(UserFacebookPageTab $tab, $action = 'remove')
	{
		$page = $tab->getPage();
		$facebookData = $page->getUserData();

		$accessToken = $facebookData->getFacebookAccessToken();
		if (empty($accessToken)) {
			throw new FacebookApiException(array(
				'error_msg' => 'Could not find user access token'
			));
		}

		$this->setAccessToken($accessToken);

		// check app permissions
		$this->checkAppPermissions(false);

		$pageAccessToken = $this->getPageAccessToken($facebookData->getFacebookUserId(), $page->getPageId());
		if (is_null($pageAccessToken)) {
			throw new FacebookApiException(array(
				'error_msg' => 'Could not find page access token'
			));
		}
		if ($action == 'add') {
			// 1 ) Adding app to page and page bar
			// me/tabs?app_id=APP_ID&method=post&access_token=PAGE_ACCESS_TOKEN
			$this->instance->api('/me/tabs', 'POST', array('access_token' => $pageAccessToken, 'app_id' => $this->getAppId()));

			// 2 ) Changing tab name
			// me/tabs/APP_ID?access_token=PAGE_ACCESS_TOKEN&custom_name=CUSTOM_NAME&method=post
			$this->instance->api('/me/tabs/app_' . $this->getAppId(), 'POST', array('access_token' => $pageAccessToken, 'custom_name' => $tab->getTabTitle()));
		} else {
			// remove app from page
			$this->instance->api('/me/tabs/app_' . $this->getAppId(), 'DELETE', array('access_token' => $pageAccessToken));
		}
	}

	/**
	 * Returns page access token
	 * @param string $ownerId - Facebook user ID
	 * @param string $pageId
	 * @return string 
	 */
	public function getPageAccessToken($ownerId, $pageId)
	{
		$queryResult = $this->instance->api($ownerId . '/accounts', 'GET', array('access_token' => $this->instance->getAccessToken()));
		foreach ($queryResult['data'] as $result) {
			if ($result['id'] == $pageId) {
				return $result['access_token'];
			}
		}

		return null;
	}

}