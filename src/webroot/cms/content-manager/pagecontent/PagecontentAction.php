<?php

namespace Supra\Cms\ContentManager\Pagecontent;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Filter\EditableHtml;
use Supra\Editable;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

/**
 * Controller for page content requests
 */
class PagecontentAction extends PageManagerAction
{

	const ACTION_BLOCK_MOVE = 'blockMove';
	const ACTION_BLOCK_PROPERTY_EDIT = 'blockPropertyEdit';

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

		$insertBeforeBlockId = $this->getRequestInput()->get('reference_id', null);

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPageLocalization()
				->getPlaceHolders()
				->get($placeHolderName);

		//foreach ($placeHolder->getBlocks() as $someBlock) {
		//	\Log::debug('0 BLOCK ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

		// Generate block according the page type provided
		$newBlock = Entity\Abstraction\Block::factory($page);

		$newBlock->setComponentName($blockType);

		// This is also a fall-through case if no valid positionin block is found.
		$newBlock->setPosition($placeHolder->getMaxBlockPosition() + 1);

		if ( ! empty($insertBeforeBlockId)) {

			$insertBeforeBlock = null;

			// Find block before which the new block will be inserted
			foreach ($placeHolder->getBlocks() as $key => $someBlock) {
				/* @var $someBlock Supra\Controller\Pages\Entity\Abstraction\Block */

				//\Log::debug('CHECKING BLOCK: ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());

				if ($someBlock->getId() == $insertBeforeBlockId) {

					$insertBeforeBlock = $someBlock;
					break;
				}
			}

			if ( ! empty($insertBeforeBlock)) {

				//\Log::debug('INSERT BEFORE BLOCK: ' . $insertBeforeBlock->getId() . ', POSITION: ' . $insertBeforeBlock->getPosition());

				foreach ($placeHolder->getBlocks() as $someBlock) {

					if ($someBlock->getPosition() >= $insertBeforeBlock->getPosition()) {

						$someBlock->setPosition($someBlock->getPosition() + 1);

						//\Log::debug('MOVED BLOCK: ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
					}
				}

				$newBlock->setPosition($insertBeforeBlock->getPosition() - 1);
			}
		}

		//\Log::debug('NEW BLOCK: ' . $newBlock->getId() . ', POSITION: ' . $newBlock->getPosition());

		$newBlock->setPlaceHolder($placeHolder);

		//foreach ($placeHolder->getBlocks() as $someBlock) {
		//	\Log::debug('1 BLOCK ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

		$this->entityManager->persist($newBlock);
		$this->entityManager->flush();

		$this->savePostTrigger();

		$controller = $newBlock->createController();
		$newBlock->prepareController($controller, $request);
		$newBlock->executeController($controller);
		$response = $controller->getResponse();
		$locked = $newBlock->getLocked();

		$array = array(
			'id' => $newBlock->getId(),
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

		$block = $this->entityManager->find(Entity\Abstraction\Block::CN(), $blockId);
		/* @var $block Entity\Abstraction\Block */

		if (empty($block)) {
			throw new CmsException(null, "Block doesn't exist anymore");
		}

		$pageData = $request->getPageLocalization();

		$this->checkActionPermission($pageData, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

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
		$propertyInput = $input->getChild('properties', true);

		$this->handlePropertyValues($blockController, $propertyInput);

		$this->entityManager->flush();

		$controllerClass = $this->getPageControllerClass();

		// Regenerate the request object
		$controllerRequest = $this->getPageRequest();

		// Need to be inside page and block controller scopes
		ObjectRepository::beginControllerContext($controllerClass);
		ObjectRepository::beginControllerContext($blockController);

		try {
			$block->prepareController($blockController, $controllerRequest);

			$blockController->prepareTwigEnvironment();
			$block->executeController($blockController);

			$response = $blockController->getResponse();
			/* @var $response HttpResponse */
			$outputString = $response->getOutputString();

			$e = null;
		} catch (\Exception $e) {
			$outputString = null;
		};

		ObjectRepository::endControllerContext($blockController);
		ObjectRepository::endControllerContext($controllerClass);

		if ($e instanceof \Exception) {
			throw $e;
		}

		$this->savePostTrigger();

		// Block HTML in response
		$this->getResponse()->setResponseData(
				array('internal_html' => $outputString)
		);
	}

	/**
	 * Removes the block
	 */
	public function deleteblockAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$blockId = $this->getRequestParameter('block_id');

		$block = $this->entityManager->find(Entity\Abstraction\Block::CN(), $blockId);

		if (empty($block)) {
			throw new CmsException(null, 'Block was not found');
		}

		$this->checkBlockSharedProperties($block);

		$this->entityManager->remove($block);
		$this->entityManager->flush();

		$this->savePostTrigger();

		// OK response
		$this->getResponse()->setResponseData(true);
	}

	/**
	 * Will confirm the removal if shared properties exist
	 * @param Entity\Abstraction\Block $block
	 */
	private function checkBlockSharedProperties(Entity\Abstraction\Block $block)
	{
		$class = $block->getComponentClass();
		$configuration = ObjectRepository::getComponentConfiguration($class);

		$hasSharedProperties = false;

		// Collects all shared properties
		if ($configuration instanceof BlockControllerConfiguration) {
			foreach ($configuration->properties as $property) {
				/* @var $property BlockPropertyConfiguration */
				if ($property->shared) {
					$hasSharedProperties = true;

					// enough to find one
					break;
				}
			}
		}

		if ($hasSharedProperties) {
			$this->getConfirmation("{#page.delete_block_shared_confirmation#}");
		}
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

		$eventManager = $this->entityManager->getEventManager();

		$eventArgs = new PageEventArgs();
		$eventArgs->setRevisionInfo(self::ACTION_BLOCK_MOVE);
		$eventManager->dispatchEvent(AuditEvents::pageContentEditEvent, $eventArgs);

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

		$this->savePostTrigger();

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
	 * 
	 * @FIXME
	 */
	public function savePlaceholderAction()
	{
		$this->isPostRequest();
		$this->checkLock();
		$input = $this->getRequestInput();
		$request = $this->getPageRequest();

		$placeHolderName = $input->get('place_holder_id');

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPageLocalization()
				->getPlaceHolders()
				->get($placeHolderName);

		$localization = $request->getPageLocalization();
			
		if (empty($placeHolder)) {
			
			$groups = $localization->getPlaceHolderGroups();
	
			if ($groups->offsetExists($placeHolderName)) {
				
				$properties = $input->getChild('properties');
				$layoutName = $properties->get('layout');
				
				$layout = $this->entityManager->getRepository(Entity\Theme\ThemePlaceholderGroupLayout::CN())
						->findOneByName($layoutName);
				
				$group = $groups->get($placeHolderName);
				$group->setGroupLayout($layout);
				
				if ($input->has('locked')) {
					$locked = $input->getValid('locked', 'boolean');
					$placeHolders = $group->getPlaceholders();
					foreach($placeHolders as $placeHolder) {
						if ($placeHolder instanceof Entity\TemplatePlaceHolder) {
							$placeHolder->setLocked($locked);
						}
					}
				}
				
				$this->entityManager->flush();
				return;
			}
		}
		
		if ( ! $input->has('locked') && $localization instanceof Entity\PageLocalization) {
			// silently exit for now
			return;
		}
		
		if (empty($placeHolder)) {
			throw new CmsException(null, "The placeholder by name '$placeHolderName' doesn't exist anymore");
		}

		if ( ! $placeHolder instanceof Entity\TemplatePlaceHolder) {
			throw new CmsException(null, "Not possible to change locked status for page placeholder");
		}
		
		$locked = $input->getValid('locked', 'boolean');
		$placeHolder->setLocked($locked);

		$this->entityManager->flush();

		$this->savePostTrigger();
	}
	
	/**
	 * 
	 */
	public function moveblocksAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$request = $this->getPageRequest();

		$newPlaceholderName = $this->getRequestInput()->get('place_holder_id');

		$newOrderedBlockIds = $this->getRequestInput()->getChild('order')->getArrayCopy();

		$movedBlockId = $this->getRequestInput()->get('block_id');

		/* @var $targetPlaceholder Entity\Abstraction\PlaceHolder */
		$targetPlaceholder = $request->getPageLocalization()
				->getPlaceHolders()
				->get($newPlaceholderName);

		/* @var $block Entity\Abstraction\Block */

		$movedBlock = $this->entityManager->find(Entity\Abstraction\Block::CN(), $movedBlockId);
		/* @var $sourcePlaceholder Entity\Abstraction\PlaceHolder */
		$sourcePlaceholder = $movedBlock->getPlaceHolder();

		//foreach ($sourcePlaceholder->getBlocks() as $someBlock) {
		//	\Log::debug('0 source placeholder BLOCK: ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

		//foreach ($targetPlaceholder->getBlocks() as $someBlock) {
		//	\Log::debug('0 target placeholder BLOCK: ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

		$keyToRemove = null;
		foreach ($sourcePlaceholder->getBlocks() as $key => $someBlock) {

			if ($someBlock->getId() == $movedBlockId) {
				$keyToRemove = $key;
				break;
			}
		}

		if ($keyToRemove) {

			$sourcePlaceholder->getBlocks()->remove($keyToRemove);

			foreach ($sourcePlaceholder->getBlocks() as $someBlock) {

				if ($someBlock->getPosition() >= $movedBlock->getPosition()) {
					$someBlock->setPosition($someBlock->getPosition() - 1);
				}
			}
		}

		$targetBlocks = array();
		foreach ($targetPlaceholder->getBlocks() as $someBlock) {

			$targetBlocks[$someBlock->getId()] = $someBlock;
		}

		foreach ($newOrderedBlockIds as $position => $someBlockId) {

			if (isset($targetBlocks[$someBlockId])) {

				$targetBlocks[$someBlockId]->setPosition($position);
			} else {

				$movedBlock->setPlaceHolder($targetPlaceholder);
				$movedBlock->setPosition($position);
			}
		}

		//foreach ($sourcePlaceholder->getBlocks() as $someBlock) {
		//	\Log::debug('1 source placeholder BLOCK: ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

		//foreach ($targetPlaceholder->getBlocks() as $someBlock) {
		//	\Log::debug('1 target placeholder BLOCK: ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

		$this->entityManager->persist($sourcePlaceholder);
		$this->entityManager->persist($targetPlaceholder);
		$this->entityManager->flush();
	}

	/**
	 *
	 * @param Supra\Controller\Pages\BlockController $blockController
	 * @param Supra\Request\RequestData  $input
	 * @return BlockProperty
	 */
	protected function handlePropertyValues(\Supra\Controller\Pages\BlockController $blockController, \Supra\Request\RequestData $input)
	{
		$blockConfiguration = $blockController->getConfiguration();
		$propertyConfigurations = $blockConfiguration->properties;

		foreach ($propertyConfigurations as $configuration) {

			$propertyName = $configuration->name;

			if ($input->offsetExists($propertyName)) {

				$property = $blockController->getProperty($propertyName);

				if ( ! $property instanceof Entity\SharedBlockProperty) {
					$this->entityManager->persist($property);
					/* @var $property Entity\BlockProperty */
				}

				$editable = $property->getEditable();

				$value = null;
				$referencedElementsData = array();

				// Specific result received from CMS for HTML
				if ($editable instanceof Editable\Html) {

					$propertyData = $input->getChild($propertyName);
					$value = $propertyData->get('html');

					if ($propertyData->hasChild('data')) {
						$referencedElementsData = $propertyData['data'];
					}
				} elseif ($editable instanceof Editable\Link) {

					if ($input->hasChild($propertyName)) {

						$referencedLinkData = $input->getChild($propertyName)
								->getArrayCopy();

						$referencedLinkData['type'] = Entity\ReferencedElement\LinkReferencedElement::TYPE_ID;
						$referencedElementsData[0] = $referencedLinkData;
					} else {
						// Scalar sent if need to empty the link
						$checkValue = $input->get($propertyName);

						if ( ! empty($checkValue)) {
							throw new \InvalidArgumentException("Empty value need to be sent to unset the link, $checkValue received");
						}
					}
				} elseif ($editable instanceof Editable\Gallery) {
					
					if ($input->hasChild($propertyName)) {
						
						$imagesListInput = $input->getChild($propertyName);	
						$this->storeGalleryProperties($imagesListInput, $property);
						
					} else {
						$checkValue = $input->get($propertyName);

						if ( ! empty($checkValue)) {
							throw new \InvalidArgumentException("Empty value need to be sent to empty the gallery, $checkValue received");
						}
						
						$metadataCollection = $property->getMetadata();
						$this->removeMetadataCollection($metadataCollection);
					}
				} elseif ($editable instanceof Editable\BlockBackground) {

					$blockBackgroundData = $input->getChild($propertyName);

					if ($blockBackgroundData->get('classname', false)) {

						$value = $blockBackgroundData->get('classname');

						if ($property->getMetadata()->containsKey('image')) {
							$property->getMetadata()->remove('image');
						}
					} else {

						if ($blockBackgroundData->hasChild('image')) {

							$imageData = $blockBackgroundData->getChild('image')->getArrayCopy();

							$imageData['type'] = Entity\ReferencedElement\ImageReferencedElement::TYPE_ID;
							$referencedElementsData['image'] = $imageData;

							$this->entityManager->flush();
						}

						$value = null;
					}
				}
				elseif ($editable instanceof Editable\Video) {

					if ($input->hasChild($propertyName)) {

						$videoData = $input->getChild($propertyName)
								->getArrayCopy();
						
						$videoData['type'] = Entity\ReferencedElement\VideoReferencedElement::TYPE_ID;
						$referencedElementsData[0] = $videoData;
					} else {
						// Scalar sent if need to empty the link
						$checkValue = $input->get($propertyName);

						if ( ! empty($checkValue)) {
							throw new \InvalidArgumentException("Empty value need to be sent to unset the link, $checkValue received");
						}
					}
				}			
				else {
					$propertyData = $input->get($propertyName);
					$value = $propertyData;
				}

				$property->setValue($value);

				$metadataCollection = $property->getMetadata();

				foreach ($referencedElementsData as $referencedElementName => &$referencedElementData) {

					if ( ! isset($referencedElementData['href'])) {
						$referencedElementData['href'] = null;
					}
					
					if ($referencedElementData['type'] == Entity\ReferencedElement\VideoReferencedElement::TYPE_ID) {

						$videoData = Entity\ReferencedElement\VideoReferencedElement::parseVideoSourceInput($referencedElementData['source']);

						if ($videoData === false) {
							throw new CmsException(null, "Video link you provided is invalid or this video service is not supported. Sorry about that.");
						}
									
						$referencedElementData = $videoData + $referencedElementData;
					}
						
					$referencedElementFound = false;

					if ( ! empty($metadataCollection)) {

						foreach ($metadataCollection as $metadataItem) {
							/* @var $metadataItem Entity\BlockPropertyMetadata */

							$metadataItemName = $metadataItem->getName();

							if ($metadataItemName == $referencedElementName) {

								$referencedElement = $metadataItem->getReferencedElement();
								$referencedElement->fillArray($referencedElementData);

								$referencedElementFound = true;

								break;
							}
						}
					}

					if ( ! $referencedElementFound) {

						$referencedElement = Entity\ReferencedElement\ReferencedElementAbstract::fromArray($referencedElementData);
						
						$metadataItem = new Entity\BlockPropertyMetadata($referencedElementName, $property, $referencedElement);

						$property->addMetadata($metadataItem);
					}
				}

				// Delete removed metadata
				if ( ! $editable instanceof Editable\Gallery) {
					foreach ($metadataCollection as $metadataName => $metadataValue) {
						/* @var $metadataValue Entity\BlockPropertyMetadata */

						if ( ! array_key_exists($metadataName, $referencedElementsData)) {
							$metadataCollection->remove($metadataName);
							$this->entityManager->remove($metadataValue);
						}
					}
				}
			}
		}
	}
	
	private function storeGalleryProperties($input, $property)
	{
		$editable = $property->getEditable();
		/* @var $editable Editable\Gallery */
		
		$galleryBlockController = $editable->getDummyBlockController();
		$galleryBlockController->setRequest($this->getPageRequest());
		
		$metadataArray = array();

		$metadataCollection = $property->getMetadata();
		foreach($metadataCollection as $metadataItem) {
			$metadataArray[$metadataItem->getId()] = $metadataItem;			
		}
		
		$index = 0;

		while ($input->valid()) {
			
			//	[properties]
			//		[title]:cover-3.jpg
			//		[description]:
			//		[link]:
			//	[id]:00eop8lua00400g0kkco
			$metaItemInput = $input->getNextChild();
			
			$imageId = $metaItemInput->get('id');
			
			if ($metaItemInput->has('__meta__')) {
				
				$id = $metaItemInput->get('__meta__');
				
				if (isset($metadataArray[$id])) {					
					$metaItem = $metadataArray[$id];
					unset($metadataArray[$id]);
				} else {
					\Log::error("Metadata item with ID #{$id} not found");
					
					$index++;
					continue;
				}
				
				$element = $metaItem->getReferencedElement();
				
			} else {
				// new element added
				$element = new Entity\ReferencedElement\ImageReferencedElement();	
				$metaItem = new Entity\BlockPropertyMetadata($index, $property, $element);
			}
			
			$metaItem->setName($index);
			
			if ($metaItemInput->hasChild('image')) {
				$imageData = $metaItemInput->getChild('image')
						->getArrayCopy();

				$imageData['type'] = Entity\ReferencedElement\ImageReferencedElement::TYPE_ID;
				$element->fillArray($imageData);
			}
			
			$element->setImageId($imageId);
			
			$metaItem->setReferencedElement($element);
			
			/* @var $property Entity\BlockProperty */
			$property->addMetadata($metaItem);
			
			
			$galleryBlockController->setParentMetadata($metaItem);
			
			$propertyInput = $metaItemInput->getChild('properties');
			$this->handlePropertyValues($galleryBlockController, $propertyInput);
			
			$index++;
		}
	
		$this->removeMetadataCollection($metadataArray);
	}
	
	private function removeMetadataCollection($collection)
	{
		$ids = \Supra\Database\Entity::collectIds($collection);
		
		if ( ! empty($ids)) {
				
			$qb = $this->entityManager
						->createQueryBuilder();

			$subProperties = $qb->select('bp')
						->from(Entity\BlockProperty::CN(), 'bp')
						->where('bp.masterMetadataId IN (:ids)')
						->setParameter('ids', $ids)
						->getQuery()
						->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true)
						->setHydrationMode(\Doctrine\ORM\Query::HYDRATE_SIMPLEOBJECT)
						->getResult();
			
			foreach ($subProperties as $property) {
				$this->entityManager->remove($property);
			}
			
			$this->entityManager->flush();
			
			foreach ($collection as $item) {
				$this->entityManager->remove($item);
			}
		}
	}
	
	/**
	 * @FIXME: optimize by generating response only for requested group
	 */
	protected function contenthtmlPlaceholderGroupAction()
	{
		$this->isPostRequest();
		$this->checkLock();
		$request = $this->getPageRequest();
		$input = $this->getRequestInput();

		$groupName = $input->get('block_id');

		$pageData = $request->getPageLocalization();
		$this->checkActionPermission($pageData, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

		$controller = $this->getPageController();

		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		ObjectRepository::beginControllerContext($controller);

		$outputString = null;
		$groupResponse = null;
		try {
			$controller->execute();
			$placeResponses = $controller->returnPlaceResponses();
			
			foreach($placeResponses as $placeResponse) {
				if ($placeResponse instanceof \Supra\Controller\Pages\Response\PlaceHolderGroup\PlaceHolderGroupResponse) {
					if ($placeResponse->getGroupName() == $groupName) {
						$groupResponse = $placeResponse;
						break;
					}
				}
			}
		} catch (\Exception $e) {
			ObjectRepository::endControllerContext($controller);
			throw $e;
		}
		
		ObjectRepository::endControllerContext($controller);
		
		if ( ! is_null($groupResponse)) {
			$outputString = $groupResponse->getOutputString();
		}

		$this->getResponse()->setResponseData(
				array('internal_html' => $outputString)
		);
	}
	
	/**
	 * For now, it's an alias of contenthtmlPlaceholderGroupAction()
	 */
	public function contenthtmlPagePlaceholderAction()
	{
		$this->contenthtmlPlaceholderGroupAction();
	}
	
	/**
	 * alias of contenthtmlPlaceholderGroupAction()
	 */
	public function contenthtmlPlaceholderAction()
	{
		$this->contenthtmlPlaceholderGroupAction();
	}
	
	/**
	 * For now, it's an alias of savePlaceholderAction()
	 */
	public function savePagePlaceholderAction()
	{
		$this->savePlaceholderAction();
	}
}
