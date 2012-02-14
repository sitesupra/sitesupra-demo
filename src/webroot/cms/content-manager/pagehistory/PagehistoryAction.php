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
use Supra\Cms\ContentManager\Pagecontent\PagecontentAction;

class PagehistoryAction extends PageManagerAction
{
	
	const ACTION_PUBLISH = 'publish';
	const ACTION_MOVE = 'move';
	const ACTION_CREATE = 'create';
	const ACTION_INSERT = 'insert';
	const ACTION_DELETE = 'delete';
	const ACTION_CHANGE = 'change';
	const ACTION_ADD = 'add';
	const ACTION_RESTORE = 'restore';
	
	
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

		$pageData = $this->getPageLocalization();
		$this->writeAuditLog('history restore', '%item% restored', $pageData);
	}
	
	/**
	 * @return array
	 */
	private function getVersionArray()
	{
		$response = array();
		$timestamps = array();
	
		$historyRevisions = $this->loadRevisionList();
		
		$userProvider = ObjectRepository::getUserProvider($this);

		$firstCreateRevision = null;
		foreach ($historyRevisions as $revision) {
			
			$userId = $revision->getUser();
				
			$userName = '#' . $userId;
			$user = $userProvider->findUserById($userId);
			if ($user instanceof User) {
				$userName = $user->getName();
			}
			
			$title = null;
			$action = null;
			
			if ( ! is_null($firstCreateRevision)) {
				$action = self::ACTION_CREATE;
				
				$title = 'Page';
				$localization = $this->getPageLocalization();
				if ($localization instanceof Entity\TemplateLocalization) {
					$title = 'Template';
				}
			}
			
			$revisionElementName = $revision->getElementName();
			$revisionType = $revision->getType();
			
			$firstCreateRevision = null;
			
			if (is_null($action)) {
				switch($revisionType) {
					case PageRevisionData::TYPE_CHANGE_DELETE:
						$action = self::ACTION_DELETE;
						break;

					case PageRevisionData::TYPE_HISTORY:
						$action = self::ACTION_PUBLISH;
						break;
					
					case PageRevisionData::TYPE_INSERT:
						$action = self::ACTION_INSERT;
						break;

					case PageRevisionData::TYPE_CREATE:
						// FIXME! page elements are created when page is opened
						// so `real` page-create revision is wrong
						$firstCreateRevision = true;
						continue;
											
						break;
						
					case PageRevisionData::TYPE_HISTORY_RESTORE:
						$action = self::ACTION_RESTORE;
						$title = 'Page';
						$localization = $this->getPageLocalization();
						if ($localization instanceof Entity\TemplateLocalization) {
							$title = 'Template';
						}
						break;

					default: 
						$action = self::ACTION_CHANGE;
				}
			}
			
			if ( ! is_null($firstCreateRevision)) {
				continue;
			}			
			
			if ( ! isset($title) && in_array($revisionType, array(PageRevisionData::TYPE_CHANGE, PageRevisionData::TYPE_CHANGE_DELETE, PageRevisionData::TYPE_INSERT))) {
				
				$blockName = null;
				switch($revisionElementName) {
					case Entity\PageLocalization::CN():
						$title = 'Page settings';
						break;
					
					case Entity\TemplateLocalization::CN():
						$title = 'Template settings';
						break;
					
					case Entity\ReferencedElement\LinkReferencedElement::CN():
					case Entity\ReferencedElement\ImageReferencedElement::CN():
					case Entity\BlockPropertyMetadata::CN():
						$blockName = $this->getRevisionedEntityBlockName($revision);
						$title = "{$blockName} block settings";
						$action = self::ACTION_CHANGE;
						break;
					
					case Entity\BlockProperty::CN():
						$blockName = $this->getRevisionedEntityBlockName($revision);
						if ($action == self::ACTION_INSERT) {
							$action = self::ACTION_CHANGE;
						}
						$title = "{$blockName} block";
						break;
						
					case Entity\Abstraction\Block::CN():
					case Entity\PageBlock::CN():
					case Entity\TemplateBlock::CN():
						if ($action == self::ACTION_CHANGE) {
							if ($revision->getAdditionalInfo() == PagecontentAction::ACTION_BLOCK_MOVE) {
								$title = 'Blocks';
								$action = self::ACTION_MOVE;
							} else {
								$blockName = $this->getRevisionedEntityBlockName($revision);
								$title = "{$blockName} block settings";
							}
						} else {
							$blockName = $this->getRevisionedEntityBlockName($revision);
							$title = "{$blockName} block";
						}
						break;
				}
			} else if ( ! isset($title)) {
				// It was page/template publish action		
				switch ($revisionElementName) {
					case Entity\PageLocalization::CN():
						//$title = 'Page';
						//break;
						
					case Entity\TemplateLocalization::CN():
						//$title = 'Template';
						//break;
					
					default:
						//$title = 'Page';
						$title = '';
				}
			}
			
			$pageInfo = array(
				'version_id' => $revision->getId(),
				'date' => $revision->getCreationTime()->format('c'),
				'author_fullname' => $userName,
				'action' => $action,
				'title' => $title,
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
	
	/**
	 * Helper method to get block for revisioned block/blockProperty/blockPropertyMetadata/referencedElement entity
	 * @param PageRevisionData $revision
	 * @return string
	 */
	private function getRevisionedEntityBlockName(PageRevisionData $revision) 
	{
		$blockName = null;
		
		$entityManager = ObjectRepository::getEntityManager('#audit');
		$blockCollection = \Supra\Controller\Pages\BlockControllerCollection::getInstance();
		
		$params = array(
			'id' => $revision->getElementId(),
			'revision' => $revision->getId(),
		);

		$entityName = $revision->getElementName();
		if (in_array($entityName, array(Entity\ReferencedElement\LinkReferencedElement::CN(), Entity\ReferencedElement\ImageReferencedElement::CN()))) {
			$entity = $entityManager->getRepository(Entity\BlockPropertyMetadata::CN())
					->findOneBy(array('referencedElement' => $revision->getElementId()));
			
			if (is_null($entity)) {
				return null;
			}
			
			$entityName = Entity\BlockPropertyMetadata::CN();
		} else {
			$entity = $entityManager->getRepository($entityName)
					->findOneBy($params);
		}
		
		if ( ! is_null($entity)) {
			
			$block = null;
			switch($entityName) {
				case Entity\BlockPropertyMetadata::CN():
					$block = $entity->getBlockProperty()
						->getBlock();
					break;
				
				case Entity\BlockProperty::CN():
					$block = $entity->getBlock();
					break;
				
				case Entity\PageBlock::CN():
				case Entity\TemplateBlock::CN():
					$block = $entity;
					break;
			}
			
			if ( ! is_null($block)) {
				$componentName = $block->getComponentName();
				$blockConfiguration = $blockCollection->getBlockConfiguration($componentName);
				if ($blockConfiguration instanceof \Supra\Controller\Pages\Configuration\BlockControllerConfiguration) {
					$blockName = $blockConfiguration->title;
				}
			}
		}
		
		return $blockName;
	}
	
	private function loadRevisionList()
	{
		$localizationId = $this->getRequestParameter('page_id');
			
		$params = array(
			'types' => array(
				PageRevisionData::TYPE_HISTORY,
				PageRevisionData::TYPE_HISTORY_RESTORE,
				PageRevisionData::TYPE_CHANGE,
				PageRevisionData::TYPE_REMOVED,
				PageRevisionData::TYPE_CREATE,
				PageRevisionData::TYPE_INSERT,
			), 
			'reference' => $localizationId,
		);
		
		$qb = $this->entityManager->createQueryBuilder();
		
		$qb->select('r.id')
				->from(PageRevisionData::CN(), 'r')
				->where('r.type = :type AND r.reference = :localization')
				->orderBy('r.id', 'DESC')
				->setMaxResults(1)
				->setParameter('type', PageRevisionData::TYPE_HISTORY)
				->setParameter('localization', $localizationId);
				;
				
		$lastPublishRevisionId = $qb->getQuery()
				->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_SCALAR);

		$qb = $this->entityManager->createQueryBuilder();
		$qb->select('r')
				->from(PageRevisionData::CN(), 'r')
				->where('r.reference = :reference AND r.type IN (:types)')
				->orderBy('r.id', 'ASC')
				->setParameters($params);
				;
		
		if ( ! empty($lastPublishRevisionId)) {
			
			$lastPublishRevisionId = array_shift($lastPublishRevisionId);
			
			$qb->andWhere('(r.id >= :lastPublishId) OR (r.id <= :lastPublishId AND r.type = :type)')
					->setParameter('lastPublishId', $lastPublishRevisionId)
					->setParameter('type', PageRevisionData::TYPE_HISTORY);

		}
		
		$revisions = $qb->getQuery()
				->getResult();
						
		return $revisions;
		
	}
}
