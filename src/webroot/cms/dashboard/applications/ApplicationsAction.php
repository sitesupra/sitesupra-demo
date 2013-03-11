<?php

namespace Supra\Cms\Dashboard\Applications;

use Supra\Validator\Type\AbstractType;
use Supra\Cms\Exception\CmsException;
use Supra\Cms\Dashboard\DasboardAbstractAction;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;

class ApplicationsAction extends DasboardAbstractAction
{

	const ICON_100 = '_100x100.png';
	const ICON_90 = '_90x90.png';

	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat applicationsAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * List all available applications
	 * and current user favourite applications
	 */
	public function applicationsAction()
	{
		$applications = array();

		$config = \Supra\Cms\CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray(true);

		$defaultUrlBase = '/' . SUPRA_CMS_URL . '/';

		$favorites = array();
		$favoritesResponse = array();

		$userPreferences = $this->userProvider->getUserPreferences($this->currentUser);
		if (
				isset($userPreferences['favourite_apps']) &&
				is_array($userPreferences['favourite_apps'])
		) {

			$favorites = $userPreferences['favourite_apps'];
		}

		foreach ($appConfigs as $config) {
			/* @var $config ApplicationConfiguration */

			if ($config->hidden 
					|| ! $this->applicationIsVisible($this->currentUser, $config)) {
				
				continue;
			}

			if (empty($config->urlBase)) {
				$urlBase = $defaultUrlBase;
			} else {
				$urlBase = $config->urlBase;
			}

			$applications[$config->id] = array(
				'title' => $config->title,
				'id' => $config->class,
				'icon' => $config->icon . self::ICON_90,
				'path' => preg_replace('@[//]+@', '/', '/' . $urlBase . '/' . $config->url)
			);
			
		}
		
		foreach($favorites as $favoriteApp) {
			if (isset($applications[$favoriteApp])) {
				array_push($favoritesResponse, $favoriteApp);
			}
		}
				
		$response = array(
			'favourites' => $favoritesResponse,
			'applications' => array_values($applications),
		);

		$this->getResponse()
				->setResponseData($response);
	}

	/**
	 * Add/remove favourite application
	 */
	public function favouriteAction()
	{
		$this->isPostRequest();

		$appId = $this->getRequestParameter('id');

		$config = \Supra\Cms\CmsApplicationConfiguration::getInstance();
		$appConfig = $config->getConfiguration($appId);

		if (empty($appConfig) || ! $this->applicationIsVisible($this->currentUser, $appConfig)) {
			throw new CmsException(null, 'Wrong application id');
		}

		$input = $this->getRequestInput();
		$isFavourite = $input->getValid('favourite', AbstractType::BOOLEAN);

		$favouriteApps = array();
		$userPreferences = $this->userProvider->getUserPreferences($this->currentUser);
		if (isset($userPreferences['favourite_apps'])
				&& is_array($userPreferences['favourite_apps'])) {

			$favouriteApps = $userPreferences['favourite_apps'];
		}

		if ($isFavourite) {
			if ($this->hasRequestParameter('before')) {

				$beforeId = $this->getRequestParameter('before');
				$key = array_search($beforeId, $favouriteApps);
				if ($key !== false) {
					$favouriteApps = array_merge(
							array_slice($favouriteApps, 0, $key), array($appId), array_slice($favouriteApps, $key)
					);
				}
			} else {
				array_push($favouriteApps, $appId);
			}
		} else {
			$key = array_search($appId, $favouriteApps);
			if ($key !== false) {
				unset($favouriteApps[$key]);
			}
		}

		// perform cleanup to array
		$existingApps = $config->getArray(true);
		foreach ($favouriteApps as $key => $appName) {
			if ( ! isset($existingApps[$appName])) {
				unset($existingApps[$key]);
			}

			$duplicates = array_keys($favouriteApps, $appName);
			if (count($duplicates) > 1) {
				$count = count($duplicates);
				for ($i = 1; $i < $count; $i ++ ) {
					unset($favouriteApps[$duplicates[$i]]);
				}
			}
		}

		$this->userProvider->setUserPreference($this->currentUser, 'favourite_apps', array_values($favouriteApps));
	}

	/**
	 * Action to handle move in favourite application list
	 */
	public function sortAction()
	{
		$this->isPostRequest();

		$appId = $this->getRequestParameter('id');

		$config = \Supra\Cms\CmsApplicationConfiguration::getInstance();
		$appConfig = $config->getConfiguration($appId);

		if (empty($appConfig)) {
			throw new CmsException(null, 'Wrong application id');
		}

		$userPreferences = $this->userProvider->getUserPreferences($this->currentUser);
		if ( ! isset($userPreferences['favourite_apps']) || ! is_array($userPreferences['favourite_apps'])) {
			throw new CmsException(null, 'User has no any favourite app');
		}

		$favouriteApps = $userPreferences['favourite_apps'];

		$key = array_search($appId, $favouriteApps);
		if ($key !== false) {
			unset($favouriteApps[$key]);
		}

		if ($this->hasRequestParameter('before')) {
			$beforeId = $this->getRequestParameter('before');
			$key = array_search($beforeId, $favouriteApps);
			if ($key !== false) {
				$favouriteApps = array_merge(
						array_slice($favouriteApps, 0, $key), array($appId), array_slice($favouriteApps, $key)
				);
			}
		} else {
			array_push($favouriteApps, $appId);
		}

		$this->userProvider->setUserPreference($this->currentUser, 'favourite_apps', array_values($favouriteApps));
	}

	/**
	 * @param type $applicationConfiguration
	 * @return integer
	 */
	private function applicationIsVisible($user, $appConfig)
	{
		if ($appConfig->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			return $appConfig->authorizationAccessPolicy->isApplicationAdminAccessGranted($user);
		} else {
			return true;
		}
	}

}