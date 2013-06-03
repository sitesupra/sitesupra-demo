<?php

namespace Supra\Cms\ContentManager\Pagecontent;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;
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

		// Generate block according the page type provided
		$newBlock = Entity\Abstraction\Block::factory($page);
		$newBlock->setComponentName($blockType);
		
		$class = $newBlock->getComponentClass();
		$configuration = ObjectRepository::getComponentConfiguration($class);
		
		if ( ! $configuration instanceof BlockControllerConfiguration) {
			throw new \RuntimeException("Failed to get configuration for specified block type {$blockType}");
		}
		
		if ($configuration->unique) {
			$blockSet = $request->getBlockSet();
			foreach ($blockSet as $block) {
				/* @var $block Supra\Controller\Pages\Entity\Abstraction\Block */
				if ($block->getComponentClass() === $class) {
					throw new CmsException(null, "Only one instance of \"{$configuration->title}\" can be placed on the page");
				}
			}
		}
		
		$insertBeforeBlockId = $this->getRequestInput()->get('reference_id', null);

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPageLocalization()
				->getPlaceHolders()
				->get($placeHolderName);

		//foreach ($placeHolder->getBlocks() as $someBlock) {
		//	\Log::debug('0 BLOCK ' . $someBlock->getId() . ', POSITION: ' . $someBlock->getPosition());
		//}

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
			/* @var $response \Supra\Response\HttpResponse */
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

		$placeHolderName = ($input->has('place_holder_id') ? $input->get('place_holder_id') : $input->get('block_id'));

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
				
				$themeProvider = ObjectRepository::getThemeProvider($this);
				$activeTheme = $themeProvider->getCurrentTheme();

				$layouts = $activeTheme->getPlaceholderGroupLayouts();
				
				$layout = $layouts->get($layoutName);
				
				$group = $groups->get($placeHolderName);
				$group->setGroupLayout($layout);
				
				if ($input->has('locked')) {
					$locked = $input->getValid('locked', 'boolean');
					$group->setLocked($locked);
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

			if ($input->offsetExists($propertyName) || $propertyName == 'media') {

				$property = $blockController->getProperty($propertyName);
				$editable = $property->getEditable();
				
				if ( ! $property instanceof Entity\SharedBlockProperty
						|| ! $editable instanceof Editable\PageKeywords) {
					$this->entityManager->persist($property);
					/* @var $property Entity\BlockProperty */
				}
				
				// @TODO: another solution?
				if ($editable instanceof Editable\PageKeywords) {
					$localization = $this->getPageLocalization();
					$tagsArray = $localization->getTagArray();
                            
					$newTagArray = array();
					$keywordString = $input->get($propertyName);
					$keywordArray = explode(';', $keywordString);
					
					foreach ($keywordArray as $keyword) {
						
						if ( ! in_array($keyword, $tagsArray)) {
							$tag = new Entity\LocalizationTag();
							$tag->setName($keyword);
							
							$localization->addTag($tag);
							$this->entityManager->persist($tag);
						}
						
						$newTagArray[] = $keyword;
					}
					
					$tagsToRemove = array_diff($tagsArray, $newTagArray);
					$tagCollection = $localization->getTagCollection();

					foreach ($tagsToRemove as $tagToRemove) {
						$tag = $tagCollection->offsetGet($tagToRemove);
						$this->entityManager->remove($tag);
					}
					
					continue;
				}

				$value = null;
				$referencedElementsData = array();

				// Specific result received from CMS for HTML
				if ($editable instanceof Editable\Html) {
					
					$propertyData = $input->getChild($propertyName)
							->getArrayCopy();
					
					$fontsData = array();
					
					if (isset($propertyData['fonts'])
							&& ! empty($propertyData['fonts'])) {
						
						$fonts = $propertyData['fonts'];

						$knownGoogleFonts = $this->getGoogleCssFontList();
						foreach ($fonts as $fontFamily) {
							if ( ! in_array($fontFamily, $knownGoogleFonts)) {
								throw new CmsException(null, "Unknown font name {$fontFamily}");
							}
							
							$fontsData[] = $fontFamily;
						}
					}
					
					$propertyData['fonts'] = $fontsData;
					$value = $propertyData;
					
					if (isset($propertyData['data'])) {
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
				} elseif ($editable instanceof Editable\Gallery || $editable instanceof Editable\Slideshow) {
					
					if ($input->hasChild($propertyName)) {
						$listInput = $input->getChild($propertyName);
						
						if ($editable instanceof Editable\Gallery) {
							$this->storeGalleryProperties($listInput, $property);
						} else {
							$value = $this->storeSlideshowProperties($listInput, $property, $configuration);
						}
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
				} else if ($editable instanceof Editable\InlineMap) {
					if ($input->hasChild($propertyName)) {

						$mapData = $input->getChild($propertyName)
								->getArrayCopy();
						
						$editable->setContentFromEdit($mapData);
						$value = $editable->getContent();
						
//						$map = $input->getChild($propertyName);
//						
//						$latitude = (float)$mapInput->get('latitude');
//						$longitude = (float)$mapInput->get('longitude');
//						$longitude = (float)$mapInput->get('zoom');
//						
//						$value = "{$latitude}|{$longitude}|{$zoom}";
					}
				} else if ($editable instanceof Editable\InlineMedia) {
					
					if ($input->hasChild($propertyName)) {
						
						$mediaData = $input->getChild($propertyName)
								->getArrayCopy();
						
						$editable->setContentFromEdit($mediaData);
						$metaElement = $editable->getContentMetadataForEdit();
						if ( ! empty($metaElement)) {
							$referencedElementsData[0] = $metaElement->toArray();
						}
					}
				} else if ($editable instanceof Editable\PropertySet) {
					
					if ($input->hasChild($propertyName)) {
						
						$data = $input->getChild($propertyName)
								->getArrayCopy();
						
						$storableData = array();
						
						foreach ($data as $offset => $setItemData) {
							foreach ($configuration->properties as $propertyConfiguration) {

								$setPropertyName = $propertyConfiguration->name;
								$setPropertyEditable = $propertyConfiguration->editableInstance;

								$setPropertyValue = $setPropertyEditable->getDefaultValue();
								if (isset($setItemData[$setPropertyName])) {
									$setPropertyValue = $setItemData[$setPropertyName];
								}

								$setPropertyEditable->setContentFromEdit($setPropertyValue);
								$storableData[$offset][$setPropertyName] = $setPropertyEditable->getStorableContent();
							}
						}

						$editable->setContentFromEdit($storableData);
						$value = $editable->getStorableContent();
					}
				}
				
				else if ($editable instanceof Editable\MediaGallery) {
					$listInput = $input->getChild($propertyName);
					$propertyArray = $this->handleMediaGalleryInput($listInput, $configuration);
					$value = serialize($propertyArray);
				}
				
				else {
					$propertyData = $input->get($propertyName);
					$value = $propertyData;
				}

				$editable->setContentFromEdit($value);
				$storableValue = $editable->getContent();
				
				$property->setValue($storableValue);

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
			
			if ( ! $metaItemInput->hasChild('image')) {
				continue;
			}
			
			$imageData = $metaItemInput->getChild('image')
					->getArrayCopy();
			
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
				if ( ! $metaItemInput->hasChild('image')) {
					continue;
				}
				
//				$imageData = $metaItemInput->getChild('image')
//						->getArrayCopy();
					
				$element = Entity\ReferencedElement\ReferencedElementAbstract::fromArray($imageData);
				$metaItem = new Entity\BlockPropertyMetadata($index, $property, $element);				
			}
			
			$metaItem->setName($index);
								
			$element->fillArray($imageData);
			
//			if ($metaItemInput->hasChild('image')) {
//				$imageData = $metaItemInput->getChild('image')
//						->getArrayCopy();
//
//				$imageData['type'] = Entity\ReferencedElement\ImageReferencedElement::TYPE_ID;
//				$element->fillArray($imageData);
//			}
			
//			$element->setImageId($imageId);
			
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
		$this->savePlaceholderAction();
		$this->contenthtmlPlaceholderGroupAction();
	}
	
	/**
	 * alias of contenthtmlPlaceholderGroupAction()
	 */
	public function contenthtmlPlaceholderAction()
	{
		$this->savePlaceholderAction();
		$this->contenthtmlPlaceholderGroupAction();
	}
	
	/**
	 * For now, it's an alias of savePlaceholderAction()
	 */
	public function savePagePlaceholderAction()
	{
		$this->savePlaceholderAction();
	}
	
	/**
	 * @FIXME
	 */
	private function storeSlideshowProperties($input, $property, $configuration)
	{
		$slideshowData = array();
		
		while ($input->valid()) {
			
			$slideKey = $input->key();
			$slideInput = $input->getNextChild();
	
			$slideData = array();
			
			foreach ($configuration->properties as $propertyConfiguration) {
				/* @var $propertyConfiguration Supra\Controller\Pages\Configuration\BlockPropertyConfiguration */
				
				$name = $propertyConfiguration->name;
				$editable = $propertyConfiguration->editableInstance;
				
				/* @var $editable \Supra\Editable\EditableInterface */
				
				if ($slideInput->has($name) || $slideInput->hasChild($name)) {
					
					$content = $slideInput->has($name) ? $slideInput->get($name) : $slideInput->getChild($name)->getArrayCopy();
					
					if ($editable instanceof Editable\Set) {
						
						// set of button properties
						$setInput = $slideInput->getChild($name);
						
						$setDataArray = array();
						
						while ($setInput->valid()) {
							
							$setElementKey = $setInput->key();
							$setElementInput = $setInput->getNextChild();
							
							$setData = array();
							$value = null;
							
							foreach ($propertyConfiguration->properties as $setPropertyConfiguration) {
								/* @var $setPropertyConfiguration Supra\Controller\Pages\Configuration\BlockPropertyConfiguration */
								$propertyName = $setPropertyConfiguration->name;
								$propertyEditable = $setPropertyConfiguration->editableInstance;

								if ($propertyEditable instanceof Editable\Link) {
									$linkData = $setElementInput->getChild($propertyName)
											->PgetArrayCopy();
									$linkElement = new Entity\ReferencedElement\LinkReferencedElement;
									
									$linkElement->fillArray($linkData);
									$value = $linkElement->toArray();
								} 
								else if ($propertyEditable instanceof Editable\String) {
									$value = $setElementInput->get($propertyName);
								}
								
								$setData[$propertyName] = $value;
							}
							
							$setDataArray[$setElementKey] = $setData;
						}
					}
					else {
						
						try {
							$editable->setContentFromEdit($content);
						} catch (\Supra\Editable\Exception\RuntimeException $e) {
							throw new CmsException(null, $e->getMessage());
						}
						
						$slideData[$name] = $editable->getContentForEdit();
					}
					
				} else {
					$slideData[$name] = $propertyConfiguration->default;
				}
			}
			
			$slideshowData[$slideKey] = $slideData;
		}
		
		return $slideshowData;
	}
	
	/**
	 * 
	 */
	private function handleMediaGalleryInput($input, $propertyConfiguration)
	{
		$values = array();
		
		while ($input->valid()) {
			
			$offset = $input->key();
			$itemInput = $input->getNextChild();
			
			$itemData = array();
			
			foreach ($propertyConfiguration->properties as $configuration) {
				
				$name = $configuration->name;
				$editable = clone $configuration->editableInstance;
				
				if ($itemInput->offsetExists($name)) {
					
					$content = $itemInput->offsetGet($name);
					if ( ! empty($content)) {
						try {
							$editable->setContentFromEdit($content);
						} catch (\Supra\Editable\Exception\RuntimeException $e) {
							throw new CmsException(null, $e->getMessage());
						}

						$itemData[$name] = $editable->getContentForEdit();
					} else {
						$itemData[$name] = $editable->getDefaultValue();
					}
				} else {
					$itemData[$name] = $editable->getDefaultValue();
				}
			}
			
			$values[$offset] = $itemData;
		}
		
		return $values;
	}
}
