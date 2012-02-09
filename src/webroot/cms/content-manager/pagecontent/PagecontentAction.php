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
	 * Content save action.
	 * Responds with block inner HTML content.
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$this->checkLock();
		$request = $this->getPageRequest();
		$input = $this->getRequestInput();
		
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
		
		if ($block instanceof Entity\TemplateBlock) {
			if ($input->has('locked')) {
				$locked = $input->getValid('locked', 'boolean');
				$block->setLocked($locked);
			}
		}
		
		// Load received property values and data from the POST
		$properties = $input->getChild('properties', true);

		foreach ($properties as $propertyName => $propertyPost) {

			$property = $blockController->getProperty($propertyName);

			// Could be new, should persist
			$this->entityManager->persist($property);
			/* @var $property Entity\BlockProperty */

			$editable = $property->getEditable();

			$value = null;
			$valueData = array();

			// Specific result received from CMS for HTML
			if ($editable instanceof Editable\Html) {
				$value = $propertyPost['html'];
				if (isset($propertyPost['data'])) {
					$valueData = $propertyPost['data'];
				}
			} elseif ($editable instanceof Editable\Link) {
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

			// Remove all old references
//			$metadataCollection = $property->getMetadata();
//			foreach ($metadataCollection as $metadata) {
//				$this->entityManager->remove($metadata);
//			}
//			
//			// flush, to remove old entites before add something new
//			// or unique constraint rule @ blockPropertyMetadata will fail
//			// FIXME: remove flush from foreach
//			$this->entityManager->flush();

//			// Empty the metadata
//			$property->resetMetadata();

			// Set new refeneced elements
			$property->setValue($value);

			
			$metadataCollection = $property->getMetadata();
			foreach ($valueData as $elementName => &$elementData) {
				
				if ( ! isset($elementData['href'])) {
					$elementData['href'] = null;
				}
				
				$elementFound = false;
				if ( ! empty($metadataCollection)) {
					foreach($metadataCollection as $metadataItem) {
						
						$name = $metadataItem->getName();
						if ($name == $elementName) {
							$element = $metadataItem->getReferencedElement();
							$element->fillArray($elementData);
							
							$elementFound = true;
						}
					}
				}
				
				if ($elementFound) {
					continue;
				}

				$element = Entity\ReferencedElement\ReferencedElementAbstract::fromArray($elementData);
				$blockPropertyMetadata = new Entity\BlockPropertyMetadata($elementName, $property, $element);
				$property->addMetadata($blockPropertyMetadata);

				// Should be persisted by cascade
//					// Let's persist new elements
//					$this->entityManager->persist($element);
//					$this->entityManager->persist($blockPropertyMetadata);
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
	 * Alias to save method.
	 */
	public function contenthtmlAction()
	{
		$this->saveAction();
	}
	
	/**
	 * Saves placeholder settings (locked parameter)
	 */
	public function savePlaceholderAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();
		$request = $this->getPageRequest();
		
		$placeHolderName = $input->get('place_holder_id');
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPageLocalization()
				->getPlaceHolders()
				->get($placeHolderName);
		
		if (empty($placeHolder)) {
			throw new CmsException(null, "The placeholder by name '$placeHolderName' doesn't exist anymore");
		}
		
		if ( ! $placeHolder instanceof Entity\TemplatePlaceHolder) {
			throw new CmsException(null, "Not possible to change locked status for page placeholder");
		}
		
		$locked = $input->getValid('locked', 'boolean');
		$placeHolder->setLocked($locked);
		
		$this->entityManager->flush();
	}
}
