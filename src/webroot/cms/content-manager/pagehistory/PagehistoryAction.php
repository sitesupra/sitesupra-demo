<?php

namespace Supra\Cms\ContentManager\Pagehistory;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;


class PagehistoryAction extends PageManagerAction
{
	public function loadAction()
	{
		$response = $this->getVersionArray();
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	public function restoreAction()
	{
		$this->restoreHistoryVersion();
		
		$this->getResponse()
				->setResponseData(true);
	}
	
	private function getVersionArray()
	{
		$response = array();
		$timestamps = array();
	
		$pageId = $this->getRequestParameter('page_id');
		
		// History connection
		$em = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\History');
		$localizations = $em->getRepository(PageRequest::DATA_ENTITY)
				->findBy(array('id' => $pageId));
		
		foreach ($localizations as $localization) {
			
			$revisionId = $localization->getRevisionId();
			$revisionData = $em->find(PageRequest::REVISION_DATA_ENTITY, $revisionId);
			if (! ($revisionData instanceof Entity\RevisionData)) {
				throw new CmsException(null, 'Failed to load revision data');
			}

			$userId = $revisionData->getUser();
			$userProvider = ObjectRepository::getUserProvider($this);
		
			// If not found will show use ID
			$userName = '#' . $userId;
			$user = $userProvider->findUserById($userId);
			if ($user instanceof \Supra\User\Entity\User) {
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
