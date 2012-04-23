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
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

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
	const ACTION_DUPLICATE = 'duplicate';
	
	
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
		$this->writeAuditLog('%item% restored', $pageData);
	}
	
	/**
	 * @return array
	 */
	private function getVersionArray()
	{
		$response = array();
		$timestamps = array();
		
		$userProvider = ObjectRepository::getUserProvider($this);
		
		$localization = $this->getPageLocalization();
		
		$localizationType = 'Page';
		if ($localization instanceof Entity\TemplateLocalization) {
			$localizationType = 'Template';
		}
	
		$historyRevisions = $this->getRevisionList();
		
		foreach ($historyRevisions as $revision) {
			
			$userId = $revision->getUser();
				
			$userName = '#' . $userId;
			$user = $userProvider->findUserById($userId);
			if ($user instanceof User) {
				$userName = $user->getName();
			}
			
			$title = null;
			$action = null;
			
			$revisionElementName = $revision->getElementName();
			$revisionType = $revision->getType();
		
			switch ($revisionType) {
				case PageRevisionData::TYPE_CHANGE_DELETE:
					$action = self::ACTION_DELETE;
					break;

				case PageRevisionData::TYPE_HISTORY:
					$action = self::ACTION_PUBLISH;
					$title = $localizationType;
					break;

				case PageRevisionData::TYPE_INSERT:
					$action = self::ACTION_INSERT;
					break;

				case PageRevisionData::TYPE_CREATE:
					$action = self::ACTION_CREATE;
					$title = $localizationType;
					break;

				case PageRevisionData::TYPE_HISTORY_RESTORE:
					$action = self::ACTION_RESTORE;
					$title = $localizationType;
					break;

				case PageRevisionData::TYPE_DUPLICATE:
					$action = self::ACTION_DUPLICATE;
					$title = $localizationType;
					break;

				default: 
					$action = self::ACTION_CHANGE;
			}
			
			if ( ! isset($title) && $revisionType & (PageRevisionData::TYPE_CHANGE | PageRevisionData::TYPE_CHANGE_DELETE | PageRevisionData::TYPE_INSERT)) {
				
				$blockName = null;
				switch($revisionElementName) {
					case Entity\PageLocalization::CN():
						$title = "{$localizationType} settings";
						break;
					
					case Entity\TemplateLocalization::CN():
						$title = "{$localizationType} settings";
						break;
					
					case Entity\ReferencedElement\LinkReferencedElement::CN():
					case Entity\ReferencedElement\ImageReferencedElement::CN():
					case Entity\BlockPropertyMetadata::CN():
						if ($revisionType == PageRevisionData::TYPE_REMOVED) {
							continue;
						}
						
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
			}
			
			if (empty($title) || empty($action)) {
				continue;
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
				$blockConfiguration = ObjectRepository::getComponentConfiguration($componentName);
				
				if ($blockConfiguration instanceof BlockControllerConfiguration) {
					$blockName = $blockConfiguration->title;
				}
			}
		}
		
		return $blockName;
	}
	
	/**
	 * Loads list of page revisions
	 * @return array
	 */
	private function getRevisionList()
	{
		$localizationId = $this->getRequestParameter('page_id');
		
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

		$params = array(
			'skipTypes' => array(
				PageRevisionData::TYPE_TRASH,
				PageRevisionData::TYPE_RESTORED,
			), 
			'reference' => $localizationId,
		);
		
		$qb = $this->entityManager->createQueryBuilder();
		$qb->select('r')
				->from(PageRevisionData::CN(), 'r')
				->where('r.reference = :reference AND r.type NOT IN (:skipTypes)')
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
