<?php

namespace Supra\Cms\Dashboard\Applications;

use Supra\Validator\Type\AbstractType;
use Supra\Cms\Exception\CmsException;
use Supra\Cms\Dashboard\DasboardAbstractAction;

class ApplicationsAction extends DasboardAbstractAction
{
	const ICON_64 = '_64x64.png';

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
		$appConfigs = $config->getArray();

		$basePath = '/' . SUPRA_CMS_URL . '/';

		foreach ($appConfigs as $config) {
			/* @var $config ApplicationConfiguration */
			if ( ! $config->hidden) {
				$applications[] = array(
					'title' => $config->title,
					'id' => $config->class,
					'icon' => $config->icon . self::ICON_64,
					'path' => $basePath . $config->url,
				);
			}
		}

		$favourites = array();
		$userPreferences = $this->userProvider->getUserPreferences($this->currentUser);
		if (isset($userPreferences['favourite_apps'])
				&& is_array($userPreferences['favourite_apps'])) {

			$favourites = $userPreferences['favourite_apps'];
		}

		$response = array(
			'favourites' => $favourites,
			'applications' => $applications,
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

		if (empty($appConfig)) {
			throw new CmsException(null, 'Wrong application id');
		}

		$input = $this->getRequestInput();
		$isFavourite = $input->getValid('favourite', AbstractType::BOOLEAN);

		$userPreferences = $this->userProvider->getUserPreferences($this->currentUser);
		/* @var $userPreferences Collection */

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
							array_slice($favouriteApps, 0, $key), 
							array($appId), 
							array_slice($favouriteApps, $key)
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
		foreach($favouriteApps as $key => $appName) {
			if ( ! isset($existingApps[$appName])) {
				unset($existingApps[$key]);
			}
			
			$duplicates = array_keys($favouriteApps, $appName);
			if (count($duplicates) > 1) {
				$count = count($duplicates);
				for($i = 1; $i < $count; $i++) {
					unset($favouriteApps[$duplicates[$i]]);
				}
			}
		}

		$this->currentUser->setPreference('favourite_apps', array_values($favouriteApps));
		
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
					array_slice($favouriteApps, 0, $key), 
					array($appId), 
					array_slice($favouriteApps, $key)
				);
			}
		} else {
			array_push($favouriteApps, $appId);
		}

		$this->currentUser->setPreference('favourite_apps', array_values($favouriteApps));
	}

}