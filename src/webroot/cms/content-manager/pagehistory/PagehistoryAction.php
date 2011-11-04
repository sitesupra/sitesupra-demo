<?php

namespace Supra\Cms\ContentManager\Pagehistory;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\PageController;
use Supra\User\Entity\User;

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
		
		$this->restoreHistoryVersion();
		
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
	
		$pageId = $this->getRequestParameter('page_id');
		
		// History connection
		$historyEm = ObjectRepository::getEntityManager(PageController::SCHEMA_HISTORY);
		$localizationRevisions = $historyEm->getRepository(Entity\Abstraction\Localization::CN())
				->findBy(array('id' => $pageId));
		
		foreach ($localizationRevisions as $localization) {
			
			$revisionId = $localization->getRevisionId();
			$revisionData = $historyEm->find(PageRequest::REVISION_DATA_ENTITY, $revisionId);
			
			if ( ! $revisionData instanceof Entity\RevisionData) {
				throw new CmsException(null, 'Failed to load revision data');
			}

			$userId = $revisionData->getUser();
			$userProvider = ObjectRepository::getUserProvider($this);
		
			// If not found will show use ID
			$userName = '#' . $userId;
			$user = $userProvider->findUserById($userId);
			if ($user instanceof User) {
				$userName = $user->getName();
			}
			
			$pageInfo = array(
				'version_id' => $revisionData->getId(),
				'date' => $revisionData->getCreationTime()->format('c'),
				'author_fullname' => $userName,
			);
			
			// unix timestamp with milliseconds is used as array key for sorting purposes
			// though milliseconds are not stored in db..
			$timestamp = $revisionData->getCreationTime()->format('Uu');
			$timestamps[] = $timestamp;
			$response[] = $pageInfo;
		}
		
		// sort array desc
		array_multisort($timestamps, $response);
		$response = array_reverse($response);
		
		return $response;
	}
	
}
