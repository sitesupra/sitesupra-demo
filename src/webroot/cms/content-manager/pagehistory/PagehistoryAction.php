<?php

namespace Supra\Cms\ContentManager\Pagehistory;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\PageController;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Entity\Abstraction\Localization;

class PagehistoryAction extends PageManagerAction
{
	public function loadAction()
	{
		$response = $this->getVersionArray();
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	/**
	 * Restores the page to the selected history state
	 */
	public function restoreAction()
	{
		$this->isPostRequest();
		
		$this->restoreLocalizationVersion();
		$this->getResponse()
				->setResponseData(true);
	}
	
	/**
	 * @return array
	 */
	private function getVersionArray()
	{
		$response = array();
		$timestamps = array();
	
		$localizationId = $this->getRequestParameter('page_id');
	
		$historyRevisions = $this->entityManager->getRepository(PageRevisionData::CN())
			->findBy(array('type' => PageRevisionData::TYPE_HISTORY, 'reference' => $localizationId));
		
		foreach ($historyRevisions as $revision) {
			
			$userId = $revision->getUser();
			$userProvider = ObjectRepository::getUserProvider($this);
		
			// If not found will show use ID
			$userName = '#' . $userId;
			$user = $userProvider->findUserById($userId);
			if ($user instanceof User) {
				$userName = $user->getName();
			}
			
			$pageInfo = array(
				'version_id' => $revision->getId(),
				'date' => $revision->getCreationTime()->format('c'),
				'author_fullname' => $userName,
			);
			
			$timestamp = $revision->getCreationTime()->format('U');
			$timestamps[] = $timestamp;
			$response[] = $pageInfo;
		}
		
		// sort array desc
		array_multisort($timestamps, $response);
		$response = array_reverse($response);
		
		return $response;
	}
	
}
