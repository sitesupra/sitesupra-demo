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
	const LOCKED_SAVE_PROPERTY_NAME = 'locked';
	
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
		
		$this->entityManager->persist($block);
		$this->entityManager->flush();

		$controller = $block->createController();
		$block->prepareController($controller, $request);
		$block->executeController($controller);
		$response = $controller->getResponse();
		$locked = $block->getLocked();
		
		$array = array(
			'id' => $block->getId(),
			'type' => $blockType,
			// If you can insert it, you can edit it
			'closed' => false,
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
		$input = $this->getRequestInput();
		
		$pageId = $pageData->getMaster()->getId();
		$blockId = $input->get('block_id');
		
		/* @var $block Entity\Abstraction\Block */
		$block = $this->entityManager->find(Entity\Abstraction\Block::CN(), $blockId);
		
		if (empty($block)) {
			throw new CmsException(null, "Block doesn't exist anymore");
		}
		
		// Receive block property definition
		$blockController = $block->createController();
		/* @var $blockController \Supra\Controller\Pages\BlockController */
		
		$block->prepareController($blockController, $request);
		
		// Load received property values and data from the POST
		if ($input->hasChild('properties')) {
		
			$properties = $input->getChild('properties');

			if ($block instanceof Entity\TemplateBlock) {
				if ($properties->has(self::LOCKED_SAVE_PROPERTY_NAME)) {
					$locked = $properties->getValid(self::LOCKED_SAVE_PROPERTY_NAME, 'boolean');
					$block->setLocked($locked);
					$properties->offsetUnset(self::LOCKED_SAVE_PROPERTY_NAME);
				}
			}

			foreach ($properties as $propertyName => $propertyPost) {

				$property = $blockController->getProperty($propertyName);

				// Could be new, should persist
				$this->entityManager->persist($property);
				/* @var $property Entity\BlockProperty */

				$editable = $property->getEditable();

				$name = $propertyName;
				$type = $property->getType();
				$value = null;
				$valueData = array();

				// Specific result received from CMS for HTML
				if ($editable instanceof \Supra\Editable\Html) {
					$value = $propertyPost['html'];
					if (isset($propertyPost['data'])) {
						$valueData = $propertyPost['data'];
					}
				} elseif ($editable instanceof \Supra\Editable\Link) {
					// No value for the link, just metadata
					$value = null;

					if ( ! empty($propertyPost)) {
						$valueData = array($propertyPost);
						$valueData[0]['type'] = Entity\ReferencedElement\LinkReferencedElement::TYPE_ID;
					}
				} else {
					$value = $propertyPost;
				}

				// Property select in one DQL
				$blockPropertyEntity = Entity\BlockProperty::CN();

				// Remove all old references
				$metadataCollection = $property->getMetadata();
				foreach ($metadataCollection as $metadata) {
					$this->entityManager->remove($metadata);
				}

				// Empty the metadata
				$property->resetMetadata();

				// Set new refeneced elements
				$property->setValue($value);

				foreach ($valueData as $elementName => &$elementData) {
					$element = Entity\ReferencedElement\ReferencedElementAbstract::fromArray($elementData);

					$blockPropertyMetadata = new Entity\BlockPropertyMetadata($elementName, $property, $element);
					$property->addMetadata($blockPropertyMetadata);

					// Should be persisted by cascade
//					// Let's persist new elements
//					$this->entityManager->persist($element);
//					$this->entityManager->persist($blockPropertyMetadata);
				}
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
