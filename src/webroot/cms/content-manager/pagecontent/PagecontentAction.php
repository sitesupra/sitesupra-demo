<?php

namespace Supra\Cms\ContentManager\Pagecontent;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\BlockControllerCollection;

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
		
		$localeId = $this->getLocale()->getId();
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
		$localeId = $this->getLocale()->getId();
		$pageData = $this->getPageData();
		
		$pageId = $pageData->getMaster()->getId();
		$blockId = $this->getRequestParameter('block_id');
		
		/* @var $blockEntity Entity\Abstraction\Block */
		$blockEntity = $this->entityManager->find(PageRequest::BLOCK_ENTITY, $blockId);
		
		// We need block controller to receive block property definition
		$blockName = $blockEntity->getComponentName();
		$blockCollection = BlockControllerCollection::getInstance();
		$blockController = $blockCollection->getBlockController($blockName);
		$propertyDefinitionList = $blockController->getPropertyDefinition();
		
		// Load received property values and data from the POST
		$properties = $this->getRequestParameter('properties');
		
		foreach ($properties as $propertyName => $propertyPost) {
			
			if ( ! isset($propertyDefinitionList[$propertyName])) {
				throw new CmsException(null, "Property $propertyName not defined for block $blockName");
			}
			
			$propertyDefinition = $propertyDefinitionList[$propertyName];
			
			if ( ! $propertyDefinition instanceof \Supra\Editable\EditableInterface) {
				throw new CmsException(null, "Property $propertyName definition must implement EditableInterface");
			}
			
			$name = $propertyName;
			$type = get_class($propertyDefinition);
			$value = null;
			$valueData = array();
			
			// Specific result for HTML
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
					JOIN p.data AS d
					JOIN p.block AS b
				WHERE d.master = ?0 
					AND p.block = ?1 
					AND d.locale = ?2 
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

			// Image resizer
			// FIXME move outside (probably to doctrine listener)
			{
				$fileStorage = 
						\Supra\ObjectRepository\ObjectRepository::getFileStorage($this);

				foreach ($valueData as &$valueDataItem) {

					if ($valueDataItem['type'] == 'image') {

						$image = $fileStorage->getDoctrineEntityManager()->find(
								\Supra\FileStorage\Entity\Image::CN(), 
								$valueDataItem['image']);

						if ($image instanceof \Supra\FileStorage\Entity\Image) {
							$sizeName = $fileStorage->createResizedImage($image, 
									$valueDataItem['size_width'], 
									$valueDataItem['size_height']);
							$valueDataItem['size_name'] = $sizeName;
						}
					}
				}
				unset($valueDataItem);
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
		$this->checkLock();
		
		$placeHolderName = $this->getRequestParameter('place_holder_id');
		$blockOrder = $this->getRequestParameter('order');
		$blockPositionById = array_flip($blockOrder);
		
		if (count($blockOrder) != count($blockPositionById)) {
			\Log::warn("Block order array received contains duplicate block IDs: ", $blockOrder);
		}
		
		$pageRequest = $this->getPageRequest();
		
		$data = $this->getPageData();
		$page = $data->getMaster();
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $page->getPlaceHolders()
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
}
