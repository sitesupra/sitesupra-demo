<?php

namespace Supra\Package\Cms\Controller;

use Symfony\Component\HttpFoundation\Response;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Exception\LayoutNotFound;
use Supra\Package\Cms\Pages\Exception\ObjectLockedException;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\TemplateBlock;
use Supra\Package\Cms\Exception\CmsException;

class PagesContentController extends AbstractPagesController
{
	/**
	 * Returns localization properties, inner html and placeholder contents.
	 *
	 * @return SupraJsonResponse
	 */
	public function getAction()
	{
		$localization = $this->getPageLocalization();

		$pageRequest = $this->createPageRequest();

		$pageController = $this->getPageController();

		$templateException = $response
				= $internalHtml
				= null;

		try {
			$response = $pageController->execute($pageRequest);
		} catch (\Twig_Error_Loader $e) {
			$templateException = $e;
		} catch (LayoutNotFound $e) {
			$templateException = $e;
		} catch (\Exception $e) {
			throw $e;
		}

		$localizationData = $this->getLocalizationData($localization);

		if ($templateException) {
			$internalHtml = '<h1>Page template or layout not found.</h1>
				<p>Please make sure the template is assigned and the template is published in this locale and it has layout assigned.</p>';
		} elseif ($response instanceof Response) {
			$internalHtml = $response->getContent();
		}

		$localizationData['internal_html'] = $internalHtml;

		$placeHolders = $pageRequest->getPlaceHolderSet()
				->getFinalPlaceHolders();

		$blocks = $pageRequest->getBlockSet();

		$placeHoldersData = &$localizationData['contents'];

		foreach ($placeHolders as $placeHolder) {

			$blocksData = array();

			foreach ($blocks->getPlaceHolderBlockSet($placeHolder) as $block) {
				/* @var $block Block */
				$blocksData[] = $this->getBlockData($block);
			}

			$placeHolderData = array(
				'id'		=> $placeHolder->getName(),
				'title'		=> $placeHolder->getTitle(),
				'locked'	=> $placeHolder->isLocked(),
				'closed'	=> ! $localization->isPlaceHolderEditable($placeHolder),
				'contents'	=> $blocksData,
				// @TODO: if this one is hardcoded, why not to hardcode in UI?
				'type'		=> 'list',
				// @TODO: list of blocks that are allowed to be insterted
//				'allow' => array(
//						0 => 'Project_Text_TextController',
//				),
			);

			$placeHoldersData[] = $placeHolderData;
		}

		$jsonResponse = new SupraJsonResponse($localizationData);

		// @FIXME: dummy. when needed, move to prefilter.
		$jsonResponse->setPermissions(array(array(
			'edit_page' => true,
			'supervise_page' => true
		)));

		return $jsonResponse;
	}

	public function saveAction()
	{
		$this->isPostRequest();
		
		$this->checkLock();
		
		$input = $this->getRequestInput();

		$block = $this->getEntityManager()
				->find(Block::CN(), $input->get('block_id'));
		/* @var $block Block */

		if ($block === null) {
			throw new CmsException(null, 'The block you are trying to save not found.');
		}

		// Template block advanced options
		if ($block instanceof TemplateBlock) {
			if ($input->has('locked')) {

				$locked = $input->filter('locked', false, false, FILTER_VALIDATE_BOOLEAN);

				$block->setLocked($locked);
			}
		}

		$blockController = $this->getBlockCollection()
				->createController($block);

		$localization = $block instanceof TemplateBlock
				? $block->getPlaceHolder()->getLocalization()
				: $this->getPageLocalization();

		$pageRequest = $this->createPageRequest($localization);

		$blockController->prepare($pageRequest);
		
		$this->getEntityManager()->transactional(function () use ($input, $blockController) {

			$propertyArray = $input->get('properties', array());

// @DEV: remove
//			$propertyArray = array(
//				'images' => array(
//					array(
//						'type'	=> 'image',
//						'image'	=> '017rzqswi010s8co08o4',
//						'id'	=> '017rzqswi010s8co08o4',
//						'align'	=> null,
//						'style'	=> null,
//						'size_width' => 243,
//						'size_height' => 162,
//						'crop_top' => 0,
//						'crop_left' => 21,
//						'crop_width' => 186,
//						'crop_height' => 162,
//					),
//					array(
//						'type'	=> 'image',
//						'image'	=> '017rzqt5a01ok08ocwgs',
//						'id'	=> '017rzqt5a01ok08ocwgs',
//						'align'	=> null,
//						'style'	=> null,
//						'size_width' => 243,
//						'size_height' => 162,
//						'crop_top' => 0,
//						'crop_left' => 21,
//						'crop_width' => 186,
//						'crop_height' => 162,
//					),
//					array(
//						'type'	=> 'image',
//						'image'	=> '017rzqsj101w840kggck',
//						'id'	=> '017rzqsj101w840kggck',
//						'align'	=> null,
//						'style'	=> null,
//						'size_width' => 243,
//						'size_height' => 162,
//						'crop_top' => 0,
//						'crop_left' => 21,
//						'crop_width' => 186,
//						'crop_height' => 162,
//					),
//				)
//			);

			foreach ($propertyArray as $name => $value) {
				$blockController->savePropertyValue($name, $value);
			}
		});

		// execute, so response is rendered
		$blockController->execute();

		// Respond with block HTML
		return new SupraJsonResponse(array(
				'internal_html' => (string) $blockController->getResponse()
		));
	}

	public function publishAction()
	{
		//$this->container['cache.frontend']->flushAll();
		$this->container->getCache()->clear('block_cache');

		$auditReader = $this->getAuditReader();

		$localization = $this->getPageLocalization();

		if ($localization instanceof PageLocalization) {
			// check the template localization first.
			// if it was never published before, publish it too.
			$templateLocalization = $localization->getTemplateLocalization();

			if (! $templateLocalization->isPublished()) {
				$templateLocalization->setPublishTime();

				$this->getEntityManager()
					->flush($templateLocalization);

				$templateLocalization->setPublishedRevision(
						$auditReader->getCurrentRevision(
							$templateLocalization::CN(),
							$templateLocalization->getId()
				));
			}
		}

		$localization->setPublishTime();

		$this->getEntityManager()
				->flush($localization);

		$currentRevision = $auditReader->getCurrentRevision($localization::CN(), $localization->getId());

		$this->getEntityManager()->createQuery(sprintf(
				'UPDATE %s l SET l.publishedRevision = ?0 WHERE l.id = ?1', $localization::CN()
				))->execute(array($currentRevision, $localization->getId()));

		return new SupraJsonResponse();
	}

	/**
	 * Handles new block insertion request.
	 */
	public function insertBlockAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$pageRequest = $this->createPageRequest();

		$blockComponentName = $this->getRequestParameter('type');

		// Generate block according the page localization type provided
		$block = Block::factory($this->getPageLocalization());
		$block->setComponentName($blockComponentName);

		$class = $block->getComponentClass();

		$blockConfiguration = $this->getBlockCollection()
				->getConfiguration($class);

		// logic/code issue
		if (! $blockConfiguration->isInsertable()) {
			throw new CmsException(null, 'This block cannot be added.');
		}

		// deny adding if block is defined as unique
		// and page block set already contains it.
		if ($blockConfiguration->isUnique()) {

			foreach ($pageRequest->getBlockSet() as $existingBlock) {

				if ($existingBlock->getComponentClass() === $class) {
					throw new CmsException(
							null,
							sprinf(
									'Only one instance of "%s" block can be added on the page.',
									$blockConfiguration->getTitle()
							)
					);
				}
			}
		}

		$placeHolderName = $this->getRequestParameter('placeholder_id');

		$placeHolder = $pageRequest->getPlaceHolderSet()
				->getFinalPlaceHolders()
				->offsetGet($placeHolderName);

		if ($placeHolder === null) {
			throw new \InvalidArgumentException(sprintf(
					'Missing placeholder [%s] in place holder set.',
					$placeHolderName
			));
		}

		$insertBeforeTargetId = $this->getRequestInput()->get('reference_id');

		if (! empty($insertBeforeTargetId)) {

			$insertBeforeTarget = null;
			
			foreach ($placeHolder->getBlocks() as $existingBlock) {
				if ($existingBlock->getId() === $insertBeforeTargetId) {
					$insertBeforeTarget = $existingBlock;
					break;
				}
			}

			if ($insertBeforeTarget === null) {
				throw new \InvalidArgumentException(sprintf(
						'Blocks collection item [%s] not found.',
						$insertBeforeTargetId
				));
			}

			$placeHolder->addBlockBefore($insertBeforeTarget, $block);

		} else {
			$placeHolder->addBlockLast($block);
		}

		$entityManager = $this->getEntityManager();

		$entityManager->persist($block);

		$entityManager->flush();

		return new SupraJsonResponse($this->getBlockData($block, true));
	}

	/**
	 * Handles block deletion request.
	 */
	public function deleteBlockAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$blockId = $this->getRequestParameter('block_id');

		$entityManager = $this->getEntityManager();

		$block = $entityManager->find(Block::CN(), $blockId);

		if ($block === null) {
			throw new CmsException(null, sprintf('Block with ID [%s] not found.', $blockId));
		}

		$entityManager->remove($block);

		$entityManager->flush();
		
		return new SupraJsonResponse();
	}

	/**
	 * Handles block move(between multiple placeholders) request.
	 */
	public function moveBlocksAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$pageRequest = $this->createPageRequest();

		$blockId = $this->getRequestInput()->get('block_id');

		$block = $this->getEntityManager()
				->find(Block::CN(), $blockId);

		if ($block === null) {
			throw new CmsException(null, sprintf('Block with ID [%s] not found.', $blockId));
		}

		$targetPlaceHolderName = $this->getRequestParameter('place_holder_id');

		$targetPlaceHolder = $pageRequest->getPlaceHolderSet()
				->getFinalPlaceHolders()
				->offsetGet($targetPlaceHolderName);

		if ($targetPlaceHolder === null) {
			throw new \InvalidArgumentException(sprintf(
					'Placeholder [%s] not found in placeholders set.',
					$targetPlaceHolderName
			));
		}

		$currentPlaceHolder = $block->getPlaceHolder();

		if ($currentPlaceHolder === $targetPlaceHolder) {
			// JS error or data spoofing
			throw new \LogicException('Current and new placeholders are the same.');
		}

		$currentPlaceHolder->removeBlock($block);

		$targetPlaceHolder->addBlockLast($block);

		// @TODO: not quite sure that block will exists in collection already.
		//		should be rewrited.
		$blockSet = $pageRequest->getBlockSet()
				->getPlaceHolderBlockSet($targetPlaceHolder);

		$blockPositionMap = array_values($this->getRequestParameter('order', array()));

		if ($blockSet->count() !== count($blockPositionMap)) {
			// JS error or data spoofing
			throw new \UnexpectedValueException('Ordered elements and actual blocks count does not match.');
		}

		foreach ($blockPositionMap as $position => $id) {

			$block = $blockSet->findById($id);

			if ($block === null) {
				throw new \UnexpectedValueException(sprintf('Block [%s] not found.'));
			}

			$block->setPosition($position);
		}

		$this->getEntityManager()
				->flush();

		return new SupraJsonResponse();
	}

	/**
	 * Handles block reordering(block movement withing a single placeholder) request.
	 */
	public function reorderBlocksAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$placeHolderName = $this->getRequestParameter('place_holder_id');

		$pageRequest = $this->createPageRequest();

		$placeHolder = $pageRequest->getPlaceHolderSet()
				->getFinalPlaceHolders()
				->offsetGet($placeHolderName);

		if ($placeHolder === null) {
			throw new \InvalidArgumentException(sprintf(
					'Placeholder [%s] not found in placeholders set.',
					$placeHolderName
			));
		}

		$blockSet = $pageRequest->getBlockSet()
				->getPlaceHolderBlockSet($placeHolder);

		$blockPositionMap = array_values($this->getRequestParameter('order', array()));

		if ($blockSet->count() !== count($blockPositionMap)) {
			// JS error or data spoofing
			throw new \UnexpectedValueException('Ordered elements and actual blocks count does not match.');
		}

		foreach ($blockPositionMap as $position => $id) {
			
			$block = $blockSet->findById($id);
			
			if ($block === null) {
				throw new \UnexpectedValueException(sprintf('Block [%s] not found.'));
			}

			$block->setPosition($position);
		}

		$this->getEntityManager()
				->flush();

		return new SupraJsonResponse();
	}

	/**
	 * @return SupraJsonResponse
	 */
	public function lockAction()
	{
		return $this->lockPage();
	}

	/**
	 * @return SupraJsonResponse
	 */
	public function unlockAction()
	{
		try {
			$this->checkLock();

			$this->unlockPage();

		} catch (ObjectLockedException $e) {
			// @TODO: check why it were made to ignore errors if locked.
		}

		return new SupraJsonResponse();
	}

	/**
	 * @param Localization $localization
	 * @return array
	 */
	private function getLocalizationData(Localization $localization)
	{
		$page = $localization->getMaster();

		$allLocalizationData = array();
		foreach ($page->getLocalizations() as $locale => $pageLocalization) {
			$allLocalizationData[$locale] = $pageLocalization->getId();
		}

		$ancestorIds = Entity::collectIds($localization->getAncestors());

		// abstract localization data
		$localizationData = array(
			'root'				=> $page->isRoot(),
			'tree_path'			=> $ancestorIds,
			'locale'			=> $localization->getLocaleId(),

			// All available localizations
			'localizations'		=> $allLocalizationData,

			// Editing Lock info
			'lock'				=> $this->getLocalizationEditLockData($localization),

			// Common properties
			'is_visible_in_menu'	=> $localization->isVisibleInMenu(),
			'is_visible_in_sitemap' => $localization->isVisibleInSitemap(),
			'include_in_search'		=> $localization->isIncludedInSearch(),

			// Common SEO properties
			'page_change_frequency' => $localization->getChangeFrequency(),
			'page_priority'			=> $localization->getPagePriority(),

// @TODO: check, if is used
//			'allow_edit'			=> @TODO, //$this->isAllowedToEditLocalization($localization),

			// Content defaults
			'internal_html' => null,
			'contents' => array(),

// @TODO: check, must be returned by parent method
//			'path_prefix'		=> ($localization->hasParent() ? $localization->getParent()->getPath() : null),
		);

		if ($localization instanceof PageLocalization) {

			$creationTime = $localization->getCreationTime();
			$publicationSchedule = $localization->getScheduleTime();

			$localizationData = array_replace($localizationData, array(
				// Page properties
				'keywords'			=> $localization->getMetaKeywords(),
				'description'		=> $localization->getMetaDescription(),
				// @TODO: return in one piece
				'created_date'		=> $creationTime->format('Y-m-d'),
				'created_time'		=> $creationTime->format('H:i:s'),
				// @TODO: return in one piece
				'scheduled_date'	=> $publicationSchedule ? $publicationSchedule->format('Y-m-d') : null,
				'scheduled_time'	=> $publicationSchedule ? $publicationSchedule->format('H:i:s') : null,

				'active'			=> $localization->isActive(),

				// Used template info
				'template'			=> array(
					'id'	=> $localization->getTemplate()->getId(),
					'title' => $localization->getTemplateLocalization()->getTitle(),
				),
			));

		} elseif ($localization instanceof TemplateLocalization) {

			$layoutData = null;

			if ($page->hasLayout($this->getMedia())) {

				$layoutName = $page->getLayoutName($this->getMedia());

				$layout = $this->getActiveTheme()
						->getLayout($layoutName);

				if ($layout !== null) {
					$layoutData = array(
						'id'	=> $layout->getName(),
						'title' => $layout->getTitle(),
					);
				}
			}

			$localizationData = array_replace($localizationData, array(
				'layouts' => $this->getActiveThemeLayoutsData(),
				'layout' => $layoutData,
			));
		}

		return array_replace(
				$this->loadNodeMainData($localization),
				$localizationData
		);
	}
	
}
