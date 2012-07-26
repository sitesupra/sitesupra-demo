<?php

namespace Supra\Cms\ContentManager\Pagecontent;

use Supra\Controller\SimpleController;
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

		$this->savePostTrigger();

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
		$inputProperties = $input->getChild('properties', true);

		$this->handlePropertyValues($blockController, $inputProperties);

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
	 * @param Supra\Controller\Pages\BlockController $blockController
	 * @param Supra\Request\RequestData  $input
	 * @return BlockProperty
	 */
	protected function handlePropertyValues(\Supra\Controller\Pages\BlockController $blockController, \Supra\Request\RequestData $input)
	{
		$blockConfiguration = $blockController->getConfiguration();
		$propertyDefinitions = $blockConfiguration->properties;

		foreach ($propertyDefinitions as $propertyDefinition) {

			$propertyName = $propertyDefinition->name;

			if ($input->has($propertyName) || $input->hasChild($propertyName)) {

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

						$imageList = $input->getChild($propertyName);

						while ($imageList->valid()) {

							$subInput = $imageList->getNextChild();
							$propertyInput = $subInput->getChild('properties');

							$referencedImageData = $subInput->getArrayCopy();

							// Mark the data with image type
							$referencedImageData['type'] = Entity\ReferencedElement\ImageReferencedElement::TYPE_ID;
							$referencedImageData['_subPropertyInput'] = $propertyInput;

							$referencedElementsData[] = $referencedImageData;
						}
					} else {
						// Scalar sent if need to empty the gallery
						$checkValue = $input->get($propertyName);

						if ( ! empty($checkValue)) {
							throw new \InvalidArgumentException("Empty value need to be sent to empty the gallery, $checkValue received");
						}
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
				} else {
					$propertyData = $input->get($propertyName);
					$value = $propertyData;
				}

				$property->setValue($value);

				$metadataCollection = $property->getMetadata();

				foreach ($referencedElementsData as $referencedElementName => &$referencedElementData) {

					if ( ! isset($referencedElementData['href'])) {
						$referencedElementData['href'] = null;
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

				if ($editable instanceof Editable\Gallery) {

					$metadataCollection = $property->getMetadata();

					$galleryController = $editable->getDummyBlockController();
					$galleryController->setRequest($this->getPageRequest());

					foreach ($referencedElementsData as $referencedElementName => $referencedElementData) {

						foreach ($metadataCollection as $metadataItemName => $metadataItem) {

							if ($metadataItemName === $referencedElementName) {

								$subInput = $referencedElementData['_subPropertyInput'];
								$galleryController->setParentMetadata($metadataItem);

								$this->handlePropertyValues($galleryController, $subInput);

								break;
							}
						}
					}
				}

				// Delete removed metadata
				foreach ($metadataCollection as $metadataName => $metadataValue) {
					/* @var $metadataValue Entity\BlockPropertyMetadata */

					if ( ! array_key_exists($metadataName, $referencedElementsData)) {

						$qb = $this->entityManager->createQueryBuilder();
						$qb->delete(Entity\BlockProperty::CN(), 'p')
								->where('p.masterMetadataId = ?0')
								->getQuery()->execute(array($metadataValue->getId()));

						$this->entityManager->flush();

						$metadataCollection->remove($metadataName);
						$this->entityManager->remove($metadataValue);
					}
				}
			}
		}
	}

}
