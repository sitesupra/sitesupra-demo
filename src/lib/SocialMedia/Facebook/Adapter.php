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

}