<?php

namespace Supra\Cms\ContentManager\Pagecontent;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Response\HttpResponse;
use Supra\Controller\FrontController;
use Supra\Controller\Pages\Filter\EditableHtml;
use Supra\Editable;

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
		$this->checkLock();
		
		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();
		$data = $this->getPageLocalization();
		$page = $data->getMaster();
		$request = $this->getPageRequest();
		
		$placeHolderName = $this->getRequestParameter('placeholder_id');
		$blockType = $this->getRequestParameter('type');
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPageLocalization()
				->getPlaceHolders()
				->get($placeHolderName);
		
		// Generate block according the page type provided
		$block = Entity\Abstraction\Block::factory($page);
		
		$block->setComponentName($blockType);
		$block->setPlaceHolder($placeHolder);
		$block->setPosition($placeHolder->getMaxBlockPosition() + 1);
		$block->setLocale($localeId);

		$this->entityManager->persist($block);
		$this->entityManager->flush();

		$controller = $block->createController();
		$block->prepareController($controller, $request);
		$block->executeController($controller);
		$response = $controller->getResponse();
		$locked = false;
		
		if ($block instanceof Entity\TemplateBlock) {
			$locked = $block->getLocked();
		}
		
		$array = array(
			'id' => $block->getId(),
			'type' => $blockType,
			'locked' => $locked,
			
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
		$this->checkLock();
		$localeId = $this->getLocale()->getId();
		$pageData = $this->getPageLocalization();
		$request = $this->getPageRequest();
		
		$pageId = $pageData->getMaster()->getId();
		$blockId = $this->getRequestParameter('block_id');
		
		/* @var $block Entity\Abstraction\Block */
		$block = $this->entityManager->find(Entity\Abstraction\Block::CN(), $blockId);
		
		if (empty($block)) {
			throw new CmsException(null, "Block doesn't exist anymore");
		}
		
		// Receive block property definition
		$blockController = $block->createController();
		$block->prepareController($blockController, $request);
		$propertyDefinitionList = $blockController->getPropertyDefinition();
		
		// Load received property values and data from the POST
		$properties = $this->getRequestParameter('properties');
		
		foreach ($properties as $propertyName => $propertyPost) {
			
			if ( ! isset($propertyDefinitionList[$propertyName])) {
				throw new CmsException(null, "Property $propertyName not defined for block $block");
			}
			
			$propertyDefinition = $propertyDefinitionList[$propertyName];
			
			if ( ! $propertyDefinition instanceof \Supra\Editable\EditableInterface) {
				throw new CmsException(null, "Property $propertyName definition must implement EditableInterface");
			}
			
			$name = $propertyName;
			$type = get_class($propertyDefinition);
			$value = null;
			$valueData = array();
			
			// Specific result received from CMS for HTML
			if ($propertyDefinition instanceof \Supra\Editable\Html) {
				$value = $propertyPost['html'];
				if (isset($propertyPost['data'])) {
					$valueData = $propertyPost['data'];
				}
			} else {
				$value = $propertyPost;
			}

			// Property select in one DQL
			$blockPropertyEntity = Entity\BlockProperty::CN();

			$query = $this->entityManager->createQuery("SELECT p FROM $blockPropertyEntity AS p
					JOIN p.localization AS l
					JOIN p.block AS b
				WHERE l.master = ?0 
					AND p.block = ?1 
					AND l.locale = ?2 
					AND p.name = ?3
					AND p.type = ?4");

			$params = array(
				$pageId,
				$blockId,
				$localeId,
				$name,
				$type
			);

			$query->setParameters($params);

			/* @var $blockProperty Entity\BlockProperty */
			$blockProperty = null;

			try {
				$blockProperty = $query->getSingleResult();
			} catch (\Doctrine\ORM\NoResultException $noResults) {

				$blockProperty = new Entity\BlockProperty($name, $type);
				$this->entityManager->persist($blockProperty);
				$blockProperty->setLocalization($pageData);
				$blockProperty->setBlock($block);
			}

			// Remove all old references
			$metadataCollection = $blockProperty->getMetadata();
			foreach ($metadataCollection as $metadata) {
				$this->entityManager->remove($metadata);
			}

			// Empty the metadata
			$blockProperty->resetMetadata();

			// Set new refeneced elements
			$blockProperty->setValue($value);

			foreach ($valueData as $elementName => &$elementData) {
				$element = Entity\ReferencedElement\ReferencedElementAbstract::fromArray($elementData);

				$blockPropertyMetadata = new Entity\BlockPropertyMetadata($elementName, $blockProperty, $element);
				$blockProperty->addMetadata($blockPropertyMetadata);
			}
		}
		
		$this->entityManager->flush();
		
		$block->prepareController($blockController, $request);
		
		$blockController->prepareTwigHelper();
		$block->executeController($blockController);
		
		$response = $blockController->getResponse();
		/* @var $response HttpResponse */
		$outputString = $response->getOutputString();
		
		// Block HTML in response
		$this->getResponse()->setResponseData(
				array('internal_html' => $outputString));
	}
	
	/**
	 * Removes the block
	 */
	public function deleteblockAction()
	{
		$this->isPostRequest();
		$this->checkLock();
		
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
		$this->checkLock();
		
		$placeHolderName = $this->getRequestParameter('place_holder_id');
		$blockOrder = $this->getRequestParameter('order');
		$blockPositionById = array_flip($blockOrder);
		
		if (count($blockOrder) != count($blockPositionById)) {
			\Log::warn("Block order array received contains duplicate block IDs: ", $blockOrder);
		}
		
		$pageRequest = $this->getPageRequest();
		
		$data = $this->getPageLocalization();
		$page = $data->getMaster();
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $data->getPlaceHolders()
				->offsetGet($placeHolderName);
		
		$blocks = $pageRequest->getBlockSet()
				->getPlaceHolderBlockSet($placeHolder);
		
		$maxPosition = max($blockPositionById);
		
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			$id = $block->getId();

			if ( ! array_key_exists($id, $blockPositionById)) {
				$this->log->warn("Block $id not received in block order action for $page");
			} else {
				$block->setPosition($blockPositionById[$id]);
			}
		}
		
		$this->entityManager->flush();
		
		$this->getResponse()->setResponseData(true);
	}
	
	/**
	 * Responds with block inner HTML content
	 */
	public function contenthtmlAction()
	{
		//TODO: filter out inline editable properties
		$this->saveAction();
//		
//		return;
//		
//		$this->isPostRequest();
//		$blockId = $this->getRequestParameter('block_id');
//		$properties = $this->getRequestParameter('properties');
//		
//		$request = $this->getPageRequest();
//		$blocks = $request->getBlockSet();
//		$block = $blocks->findById($blockId);
//		
//		if (is_null($block)) {
//			throw new CmsException(null, "Block doesn't exist anymore");
//		}
//		
//		$propertySet = $request->getBlockPropertySet()
//				->getBlockPropertySet($block);
//		
//		$blockController = $block->createController();
//		$block->prepareController($blockController, $request);
//		
//		foreach ($properties as $name => $value) {
//			
//			$property = $blockController->getProperty($name);
//			
//			if ( ! $property instanceof Entity\BlockProperty) {
//				throw new CmsException(null, "Property $name doesn't exist for the block");
//			}
//
//			$this->entityManager->detach($property);
//			$editable = $property->getEditable();
//
//			/*
//			 * TODO: how to pass metadata here? Currently it's fixed by 
//			 * letting setting only not inline editable contents.
//			 */
//			if ( ! $editable->isInlineEditable()) {
//				$property->setValue($value);
//			}
//		}
//		
//		$blockController->prepareTwigHelper();
//		$block->executeController($blockController);
//		
//		$response = $blockController->getResponse();
//		/* @var $response HttpResponse */
//		$outputString = $response->getOutputString();
//		
//		$this->getResponse()->setResponseData(
//				array('internal_html' => $outputString));
	}
}
