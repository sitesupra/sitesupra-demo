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
	
	const PARAM_APPLICATION_LIST = 'aplication_list';

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
		$applicationData = array();

		$applicationList = $this->getCurrentUserApplicationList();

		$defaultUrlBase = '/' . SUPRA_CMS_URL . '/';
		
		foreach ($applicationList as $appConfiguration) {
	
			if (empty($appConfiguration->urlBase)) {
				$urlBase = $defaultUrlBase;
			} else {
				$urlBase = $appConfiguration->urlBase;
			}

			$applicationData[] = array(
				'title' =>	$appConfiguration->title,
				'id' =>		$appConfiguration->class,
				'icon' =>	$appConfiguration->icon . self::ICON_90,
				'path' => preg_replace('@[//]+@', '/', '/' . $urlBase . '/' . $appConfiguration->url)
			);
			
		}

		$this->getResponse()
				->setResponseData(array('applications' => $applicationData));
	}

	/**
	 *
	 */
	public function sortAction()
	{
		$this->isPostRequest();

		$applicationId = $this->getRequestParameter('id');

		$applicationList = $this->getCurrentUserApplicationList();
		
		if ( ! isset($applicationList[$applicationId])) {
			throw new CmsException('dasboard.applications.unknownId', "Unknown application id {$applicationId}");
		}
		
		$sortedKeysList = array_keys($applicationList);
		
		$key = array_search($applicationId, $sortedKeysList);
		if ($key !== false) {
			unset($applicationList[$key]);
		}
		
		if ($this->hasRequestParameter('before')) {
			
			$beforeId = $this->getRequestParameter('before');
			$key = array_search($beforeId, $sortedKeysList);
			if ($key !== false) {
				$sortedKeysList = array_merge(
					array_slice($sortedKeysList, 0, $key), array($applicationId), array_slice($sortedKeysList, $key)
				);
			} else {
				array_push($sortedKeysList, $beforeId);
			}
		} else {
			array_push($sortedKeysList, $applicationId);
		}
		
		$this->userProvider->setUserPreference(
						$this->currentUser,
						self::PARAM_APPLICATION_LIST,
						array_values($sortedKeysList)
		);
	}

	/**
	 *
	 */
	private function applicationIsVisible($user, $appConfig)
	{
		if ($appConfig->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			return $appConfig->authorizationAccessPolicy->isApplicationAdminAccessGranted($user);
		} else {
			return true;
		}
	}
	
	/**
	 * @return array
	 */
	protected function getVisibleApplicationConfigurations()
	{
		$applications = array();

		$applicationConfiguration = \Supra\Cms\CmsApplicationConfiguration::getInstance();
		$configurationArray = $applicationConfiguration->getArray();

		foreach ($configurationArray as $index => $configuration) {
			
			// if application is hidden by configuration
			if ($configuration->hidden) {
				continue;
			}
			
			// if user have no enough permissions
			if ( ! $this->applicationIsVisible($this->currentUser, $configuration)) {
				continue;
			}
						
			/* @var $configuration \Supra\Cms\ApplicationConfiguration */
			$applications[ (int) $configuration->sortIndex . '_' . $index] = $configuration; 
		}
		
		ksort($applications);
		
		// looping again to get id => value collection
		$applicationList = array();
		
		foreach ($applications as $application) {
			$applicationList[$application->id] = $application;
		}
		
		return $applicationList;
	}
	
	/**
	 * @return array
	 */
	protected function getCurrentUserApplicationList()
	{
		$userPreferences = $this->userProvider->getUserPreferences($this->currentUser);

		if ( ! isset($userPreferences[self::PARAM_APPLICATION_LIST])
				|| ! is_array($userPreferences[self::PARAM_APPLICATION_LIST])) {
			
			$userApplicationList = array();
		}
		
		$userApplicationList = $userPreferences[self::PARAM_APPLICATION_LIST];
				
		$applications = $this->getVisibleApplicationConfigurations();
		
		$sortedList = array();
		
		// sorted apps goes first
		foreach ($userApplicationList as $applicationId) {
			if (isset($applications[$applicationId])) {
				
				$sortedList[$applicationId] = $applications[$applicationId];
				unset($applications[$applicationId]);
			}
		}
		
		// populate list with all others apps
		foreach ($applications as $id => $configuration) {
			$sortedList[$id] = $configuration;
		}
		
		return $sortedList;
	}

}