<?php

namespace Supra\Cms\ContentManager\Pagehistory;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\ObjectRepository\ObjectRepository;


class PagehistoryAction extends PageManagerAction
{
	public function loadAction()
	{
		$response = $this->getData();
		
		// TODO: sort by date
		$this->getResponse()
				->setResponseData($response);
	}
	
	public function restoreAction()
	{
		$this->getResponse()
				->setErrorMessage('Not implemented yet');
	}
	
	protected function getData()
	{
		$response = array();
	
		$pageId = $this->getRequestParameter('page_id');
		
		$draftEm = ObjectRepository::getEntityManager($this);
		$historyEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\History');
	
		$historyLocalizations = $historyEm->getRepository(PageRequest::DATA_ENTITY)
				->findBy(array('id' => $pageId));
		
		foreach ($historyLocalizations as $historyLocalization) {
			$revisionData = $historyLocalization->getRevisionData();

			if ( ! ($revisionData instanceof Entity\RevisionData)) {
				$revisionData = $historyEm->find(PageRequest::REVISION_DATA_ENTITY, $revisionData);
				if (! ($revisionData instanceof Entity\RevisionData)) {
					throw new \Supra\Controller\Pages\Exception\RuntimeException('Failed to load revision data');
				}
			}

			$userId = $revisionData->getUser();
			$userProvider = ObjectRepository::getUserProvider($this);
		
			$userName = '';
			$user = $userProvider->findUserById($userId);
			if ($user instanceof \Supra\User\Entity\User) {
				$userName = $user->getName();
			}

			$pageInfo = array(
				'version_id' => $revisionData->getId(),
				'date' => $revisionData->getCreatedTime()->format('c'),
				'author_fullname' => $userName,
			);

			$response[] = $pageInfo;
		}

		return $response;
	}
	
}
