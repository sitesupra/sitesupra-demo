<?php

namespace Supra\Cms\ContentManager\Pagecontent;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;

/**
 * Controller for page content requests
 */
class PagecontentAction extends PageManagerAction
{
	/**
	 * Insert block action
	 */
	public function insertblockAction()
	{
		$this->isPostRequest();
		
		$locale = $this->getLocale();
		$media = $this->getMedia();
		$data = $this->getPageData();
		$page = $data->getMaster();
		$request = $this->getPageRequest();
		
		$placeHolderName = $this->getRequestParameter('placeholder_id');
		$blockType = $this->getRequestParameter('type');
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPage()
				->getPlaceHolders()
				->get($placeHolderName);
		
		// Generate block according the page type provided
		$block = Entity\Abstraction\Block::factory($page);
		
		$block->setComponentName($blockType);
		$block->setPlaceHolder($placeHolder);
		$block->setPosition($placeHolder->getMaxBlockPosition() + 1);
		
		$this->entityManager->persist($block);
		$this->entityManager->flush();

		$controller = $block->createController();
		$block->prepareController($controller, $request);
		$block->executeController($controller);
		$response = $controller->getResponse();
		
		$array = array(
			'id' => $block->getId(),
			'type' => $blockType,
			
			//TODO: implement block locking inside the template
			'locked' => false,
			
			// TODO: generate
			'properties' => array(
				'html' => array(
					'html' => null,
					'data' => array(),
				),
			),
			'html' => $response->__toString(),
		);
		
		$this->getResponse()->setResponseData($array);
	}
	
	/**
	 * Content save action
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$locale = $this->getLocale();
		$pageData = $this->getPageData();
		
		$pageId = $this->getRequestParameter('page_id');
		$blockId = $this->getRequestParameter('block_id');
		
		/* @var $blockEntity Entity\Abstraction\Block */
		$blockEntity = $this->entityManager->find(PageRequest::BLOCK_ENTITY, $blockId);
		
		//TODO: Fix this
		$name = 'html';
		$type = 'Supra\Editable\Html';
		$value = $_POST['properties']['html']['html'];
		$valueData = $_POST['properties']['html']['data'];
		
		// Property select in one DQL
		$blockPropertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		
		$query = $this->entityManager->createQuery("SELECT p FROM $blockPropertyEntity AS p
				JOIN p.data AS d
				JOIN p.block AS b
			WHERE d.master = ?0 AND p.block = ?1 AND d.locale = ?2 AND p.name = ?3 AND p.type = ?4");
		
		$params = array(
			$pageId,
			$blockId,
			$locale,
			$name,
			$type
		);
		
		$query->setParameters($params);
		
		/* @var $blockProperty Entity\BlockProperty */
		$blockProperty = null;
		
		try {
			$blockProperty = $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $noResults) {
			
			$blockEntity = PageRequest::BLOCK_ENTITY;
			$block = $this->entityManager->find($blockEntity, $blockId);
			
			if (empty($block)) {
				throw new CmsException(null, "Block doesn't exist anymore");
			}
			
			$blockProperty = new Entity\BlockProperty($name, $type);
			$this->entityManager->persist($blockProperty);
			$blockProperty->setData($pageData);
			$blockProperty->setBlock($block);
		}
		
		$blockProperty->setValue($value);
		$blockProperty->setValueData($valueData);
		
		$this->entityManager->flush();
		
		// OK response
		$this->getResponse()->setResponseData(true);
	}
	
	/**
	 * Removes the block
	 */
	public function deleteblockAction()
	{
		$this->isPostRequest();
		
		$blockId = $this->getRequestParameter('block_id');
		
		$blockEntity = PageRequest::BLOCK_ENTITY;
		$blockQuery = $this->entityManager->createQuery("SELECT b FROM $blockEntity b
					WHERE b.id = ?0");
		
		$blockQuery->setParameters(array($blockId));
		$block = $blockQuery->getSingleResult();
		
		$this->entityManager->remove($block);
		$this->entityManager->flush();
		
		// OK response
		$this->getResponse()->setResponseData(true);
	}
	
	/**
	 * Action called on block order action
	 */
	public function orderblocksAction()
	{
		$this->isPostRequest();
		
		$pageId = $this->getRequestParameter('page_id');
		$locale = $this->getLocale();
		$media = $this->getMedia();
		$placeHolderName = $this->getRequestParameter('place_holder_id');
		$blockOrder = $this->getRequestParameter('order');
		$blockPositionById = array_flip($blockOrder);
		
		if (count($blockOrder) != count($blockPositionById)) {
			\Log::warn("Block order array received contains duplicate block IDs: ", $blockOrder);
		}
		
		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);
		
		/* @var $page Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);
		$data = $page->getData($locale);
//		$request->setRequestPageData($data);
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $page->getPlaceHolders()
				->offsetGet($placeHolderName);
		
		$blocks = $placeHolder->getBlocks();
		
		$maxPosition = max($blockPositionById);
		
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			$id = $block->getId();
			
			if ( ! array_key_exists($id, $blockPositionById)) {
				$maxPosition++;
				$block->setPosition($maxPosition);
			} else {
				$block->setPosition($blockPositionById[$id]);
			}
		}
		
		$this->entityManager->flush();
		
		$this->getResponse()->setResponseData(true);
	}
}
