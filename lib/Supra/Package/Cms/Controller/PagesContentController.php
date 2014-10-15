<?php

namespace Supra\Package\Cms\Controller;

use Doctrine\ORM\EntityManager;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\PageBlock;
use Supra\Package\Cms\Entity\TemplateBlock;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Core\HttpFoundation\SupraJsonResponse;

use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\PageLocalizationPath;

class PagesContentController extends AbstractPagesController
{
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

		$this->getPageController()
				->prepareBlockController($blockController, $pageRequest);
		
		$self = $this;

		$closure = function (EntityManager $entityManager) use ($input, $blockController, $self) {

			$configuration = $blockController->getConfiguration();

			$propertyArray = $input->get('properties', array());

			foreach ($propertyArray as $name => $value) {

				$propertyConfiguration = $configuration->getProperty($name);

				$editable = $propertyConfiguration->getEditable();

				$property = $blockController->getProperty($name);

				$self->configureEditableValueTransformers($editable, $property);

				$editable->setEditorValue($value);

				$property->setValue($editable->getRawValue());

				$entityManager->persist($property);
			}
		};

		$this->getEntityManager()
				->transactional($closure);

		// execute, so response is rendered
		$blockController->execute();

		// Respond with block HTML
		return new SupraJsonResponse(array(
				'internal_html' => (string) $blockController->getResponse()
		));
	}

	public function publishAction()
	{
		$publicEm = $this->container->getDoctrine()
				->getManager('public');
		// @TODO: obtain via ->getDoctrine()->getManager('cms');
		$draftEm = $this->getEntityManager();

//		$this->createMissingPlaceHolders();

		$draftEm->beginTransaction();
		$publicEm->beginTransaction();

		try {
			$localization = $this->getPageLocalization();

			if ($localization instanceof PageLocalization) {

				// *) Checks for path duplicates in Public schema
				$pathEntity = $localization->getPathEntity();

				$duplicatePath = $publicEm->createQueryBuilder()
						->select('p.path')
						->from(PageLocalizationPath::CN(), 'p')
						->where('p.locale = ?0 AND p.path = ?1 AND p.id <> ?2')
						->setParameters(array(
							$pathEntity->getLocale(),
							$pathEntity->getPath()->getFullPath(),
							$pathEntity->getId()
						))
						->getQuery()
						->getArrayResult();

				if ($duplicatePath) {
					throw new Exception\RuntimeException(sprintf(
							'Another page with path [%s] already exists',
							$duplicatePath['path']
					));
				}

				// *) Sets the first publication time if empty
				if ( ! $localization->isPublishTimeSet()) {
					$localization->setCreationTime();
					// Flush right now, to recalculate revision id and have
					// actual version in public entity
					$draftEm->flush($localization);
				}

				// Initialize, because not initialized proxy objects are not merged
				// this is still actual after up to 2.4.0 version.
				// @TODO: check in 2.5?
				$localization->initializeProxyAssociations();
			}

			/**
			 * 1. Merge
			 */
			// 1.1. Localization entity
			$publicLocalization = $publicEm->merge($localization);
			/* @var $publicLocalization Supra\Package\Cms\Entity\Abstraction\Localization */

			// Reset Lock object since it is not needed in Public schema
			$publicLocalization->setLock(null);

			// 1.2. Localization tags
			$tagCollection = $localization->getTagCollection();

			// 1.2.1. remove deleted tags
			$publicTagCollection = $publicLocalization->getTagCollection();
			foreach ($publicTagCollection as $tag) {
				if ( ! $tagCollection->offsetExists($tag->getName())) {
					$publicEm->remove($tag);
				}
			}

			// 1.2.2. merge all tags existing in the Draft
			foreach ($tagCollection as $tag) {
				$publicEm->merge($tag);
			}

			// 1.3. PageLocalization redirect target
			if ($localization instanceof PageLocalization) {

				$publicRedirect = $publicLocalization->getRedirectTarget();

				if ($publicRedirect !== null
						&& ! $publicRedirect->equals($localization->getRedirect())) {

					// Remove already published redirect target object if it differs from new one.
					// New one will be merged thanks to Doctrine's cascade persisting.
					$publicEm->remove($publicRedirect);
				}
			}

			/**
			 * Page contents.
			 */

			// 1.4 Blocks
			//		1. get all blocks in Draft version
			$draftBlocks = $this->getBlocksInLocalization($draftEm, $localization);

			//		2. get all blocks in Public version
			$publicBlocks = $this->getBlocksInLocalization($publicEm, $publicLocalization);

			//		3. remove blocks from 2. if they don't exists in 1.
			$draftBlockIds = Entity::collectIds($draftBlocks);
			$publicBlockIds = Entity::collectIds($publicBlocks);

			foreach (array_diff($publicBlockIds, $draftBlockIds) as $removedBlockId) {
				foreach ($publicBlocks as $block) {
					if ($block->getId() === $removedBlockId) {
						$publicEm->remove($block);
						break;
					}
				}
			}

			// not needed anymore.
			unset ($publicBlocks, $publicBlockIds);

			//		4. merge all the Draft version blocks.
			foreach ($draftBlocks as $block) {
				$publicEm->merge($block);
			}

			// 1.5 Placeholders
			$placeHolderIds = array();
			$placeHolderNames = array();

			//		- doing merge + collecting the IDs and names to cleanup not used placeholders
			foreach ($draftBlocks as $block) {
				$placeHolder = $block->getPlaceHolder();
				
				$publicEm->merge($placeHolder);

				$id = $placeHolder->getId();
				$name = $placeHolder->getName();

				if (! array_key_exists($id, $placeHolderIds)) {
					$placeHolderIds[] = $id;
				}

				if (! array_key_exists($name, $placeHolderNames)) {
					$placeHolderNames[] = $name;
				}
			}

			// not needed anymore.
			unset ($draftBlocks, $draftBlockIds);

			if (! empty($placeHolderIds)
					&& ! empty($placeHolderNames)) {

				$queryString = 'SELECT p FROM %s p WHERE p.localization = ?0 AND p.id NOT IN (?1) AND p.name IN (?2)';

				$query = $publicEm->createQuery(sprintf($queryString, PlaceHolder::CN()))
						->setParameters(array(
							$localization->getId(),
							$placeHolderIds,
							$placeHolderNames,
						));

				// @TODO: it's not performance-friendly to load placeholders just to remove them
				foreach ($query->getResult() as $placeHolder) {
					$publicEm->remove($placeHolder);
				}
			}

			// not needed anymore.
			unset($placeHolderIds, $placeHolderNames);

			// 1.6 Block properties
			$draftProperties = $this->getBlockPropertySet();
			$draftPropertyIds = $draftProperties->collectIds();

			$publicProperties = $this->getPageBlockProperties($publicEm, $publicLocalization);
			$publicPropertyIds = Entity::collectIds($publicProperties);

			foreach (array_diff($publicPropertyIds, $draftPropertyIds) as $removedPropertyId) {
				foreach ($publicProperties as $property) {
					if ($property->getId() === $removedPropertyId) {
						$publicEm->remove($property);
						break;
					}
				}
			}

			// 7. For properties 5b get block, placeholder IDs, check their existance in public, get not existant
			$missingBlockIds = array();

		// Searching for missing parent template blocks IDs in the public schema
		$blockIdList = $draftProperties->getBlockIdList();
		$parentTemplateBlockIdList = array_diff($blockIdList, $draftBlockIdList);

		if ( ! empty($parentTemplateBlockIdList)) {
			$blockEntity = Entity\Abstraction\Block::CN();

			$qb = $publicEm->createQueryBuilder();
			$qb->from($blockEntity, 'b')
					->select('b.id')
					->where($qb->expr()->in('b', $parentTemplateBlockIdList));

			$query = $qb->getQuery();
			$existentBlockIdList = $query->getResult(ColumnHydrator::HYDRATOR_ID);

			$missingBlockIdList = array_diff($parentTemplateBlockIdList, $existentBlockIdList);
		}

		// 8. Merge missing place holders from 7 (reset $locked property)
		$draftPlaceHolderIdList = $this->getPlaceHolderIdList($draftEm, $missingBlockIdList);
		$publicPlaceHolderIdList = $this->loadEntitiesByIdList($publicEm,
				Entity\Abstraction\PlaceHolder::CN(),
				$draftPlaceHolderIdList,
				'e.id',
				ColumnHydrator::HYDRATOR_ID);
		$missingPlaceHolderIdList = array_diff($draftPlaceHolderIdList, $publicPlaceHolderIdList);

		$missingPlaceHolders = $this->loadEntitiesByIdList($draftEm,
				Entity\Abstraction\PlaceHolder::CN(),
				$missingPlaceHolderIdList);

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($missingPlaceHolders as $placeHolder) {
			$placeHolder = $publicEm->merge($placeHolder);

			// Reset locked property
			if ($placeHolder instanceof Entity\TemplatePlaceHolder) {
				$placeHolder->setLocked(false);
			}
		}

		// 9. Merge missing blocks (add $temporary property)
		$missingBlocks = $this->loadEntitiesByIdList($draftEm, Entity\TemplateBlock::CN(), $missingBlockIdList);

		/* @var $block Entity\TemplateBlock */
		foreach ($missingBlocks as $block) {
			$block = $publicEm->merge($block);
			$block->setTemporary(true);
		}

		// 10. Clear all property metadata on the public going to be merged
		//TODO: remove reference elements as well!
		$propertyIdList = $draftProperties->collectIds();

		// 12. Remove old redirect if exists and doesn't match with new one
		// NOTE: remove() on publicEm should be called before publicEm::unitOfWork will be clear()'ed (code below)
		// or removing will fail ("detached entity could not be removed")
		if ( ! is_null($oldRedirect)) {
			if (is_null($newRedirect) || ! $oldRedirect->equals($newRedirect)) {
				$publicEm->remove($oldRedirect);
			}
		}

		if ( ! empty($propertyIdList)) {

			$qb = $publicEm->createQueryBuilder();
			$metaData = $qb->select('m')
					->from(Entity\BlockPropertyMetadata::CN(), 'm')
					->where($qb->expr()->in('m.blockProperty', $propertyIdList))
					->getQuery()->execute();
			$metaDataIds = Entity\Abstraction\Entity::collectIds($metaData);

			if ( ! empty($metaDataIds)) {

				$qb = $publicEm->createQueryBuilder();
				$subProperties = $qb->select('bp')
						->from(Entity\BlockProperty::CN(), 'bp')
						->where($qb->expr()->in('bp.masterMetadataId', $metaDataIds))
						->getQuery()->execute();

				foreach ($subProperties as $subProperty) {
					$publicEm->remove($subProperty);
				}

//				$qb = $publicEm->createQueryBuilder();
//				$subProperties = $qb->select('p')
//						->from(Entity\BlockProperty::CN(), 'p')
//						->where($qb->expr()->in('p.masterMetadata', $metaDataIds))
//						->getQuery()->execute();
//				$subPropertyIds = Entity\Abstraction\Entity::collectIds($subProperties);
//
//				if ( ! empty($subPropertyIds)) {
//					$qb = $publicEm->createQueryBuilder();
//					$qb->delete(Entity\BlockPropertyMetadata::CN(), 'm')
//							->where($qb->expr()->in('m.blockProperty', $subPropertyIds))
//							->getQuery()->execute();
//
//					$qb = $publicEm->createQueryBuilder();
//					$qb->delete(Entity\BlockProperty::CN(), 'p')
//							->where($qb->expr()->in('p.id', $subPropertyIds))
//							->getQuery()->execute();
//				}
//
			}

			$qb = $publicEm->createQueryBuilder();
			$qb->delete(Entity\BlockPropertyMetadata::CN(), 'r')
					->where($qb->expr()->in('r.blockProperty', $propertyIdList))
					->getQuery()->execute();

			// Force to clear UoW, or #11 step will fail,
			// as properties are still marked as existing inside EM
			$publicEm->flush();
			$publicEm->getUnitOfWork()->clear();
		}

		// 11. Merge all properties from 5
		$ownedProperties = array();
		foreach ($draftProperties as $property) {

			// Initialize the property metadata so it is copied as well
			/* @var $property Entity\BlockProperty */
			$metadata = $property->getMetadata();
			if ($metadata instanceof \Doctrine\ORM\PersistentCollection) {
				$metadata->initialize();
			}

			//foreach($metadata as $metadataItem) {
			//	$subProperties = $metadataItem->getMetadataProperties();
			//	if ($subProperties instanceof \Doctrine\ORM\PersistentCollection) {
			//		$subProperties->initialize();
			//	}
			//}

			$publicEm->merge($property);
		}


			// flushing only Public,
			// because expecting that there are no changes in Draft schema.
			$publicEm->flush();

		} catch (\Exception $e) {

			$draftEm->rollback();
			$publicEm->rollback();

			throw $e;
		}

		$draftEm->commit();
		$publicEm->commit();

		return new SupraJsonResponse();

//		$template = $this->getPageTemplate();
//
//		$sectionSet = $this->getLayoutSectionSet();
//		foreach ($sectionSet as $layoutSection) {
//			/* @var $layoutSection Entity\TemplateLayoutSection */
//			$publicEm->merge($layoutSection);
//
//			// @FIXME: temporary, local layout section
//
//			$positionData = $layoutSection->getPositionInTemplateDataObject($template);
//			$publicEm->merge($positionData);
//		}
//
//		// Searching for LayoutSection existing in public schema
//		$sectionCn = Entity\TemplateLayoutSection::CN();
//		$templateSet = $this->getTemplateTemplateHierarchy($template);
//		$publicSectionIds = $publicEm->createQuery("SELECT s.id FROM {$sectionCn} s WHERE s.template IN (?0)")
//				->setParameter(0, $templateSet->collectIds())
//				->getResult(ColumnHydrator::HYDRATOR_ID);
//
//		$removedSectionIds = array_diff($publicSectionIds, Entity\Abstraction\Entity::collectIds($sectionSet));
//
//		if ( ! empty($removedSectionIds)) {
//			$positionDataCn = Entity\TemplateLayoutSectionPositionData::CN();
//			$publicEm->createQuery("DELETE FROM {$positionDataCn} pd WHERE pd.layoutSection IN (?0)")
//					->execute(array($removedSectionIds));
//
//			$publicEm->createQuery(sprintf(
//						'DELETE FROM %s p WHERE p.layoutSection IN (?0)',
//						Entity\TemplateLayoutSectionProperty::CN()
//				))->execute(array($removedSectionIds));
//
//			$removedSections = $this->loadEntitiesByIdList(
//					$publicEm,
//					Entity\TemplateLayoutSection::CN(),
//					$removedSectionIds
//			);
//
//			foreach ($removedSections as $section) {
//				$publicEm->remove($section);
//			}
//		}
//
//		$existingSectionPropertyIds = array();
//		$sectionPropertySet = $this->getLayoutSectionPropertySet();
//		foreach($sectionPropertySet as $property) {
//			$publicEm->merge($property);
//			$existingSectionPropertyIds[] = $property->getId();
//		}
//
//		// Delete section properties that were not in draft, and have published section variant
//		if ( ! empty($publicSectionIds)) {
//			$propertyCn = Entity\TemplateLayoutSectionProperty::CN();
//
//			if ( ! empty($existingSectionPropertyIds)) {
//				$publicEm->createQuery("DELETE FROM {$propertyCn} p WHERE p.id NOT IN (?0) AND p.layoutSection IN (?1) AND p.template IN (?2)")
//					->execute(array($existingSectionPropertyIds, $publicSectionIds, $templateSet->collectIds()));
//			} else {
//				$publicEm->createQuery("DELETE FROM {$propertyCn} p WHERE p.layoutSection IN (?0) AND p.template IN (?1)")
//						->execute(array($publicSectionIds, $templateSet->collectIds()));
//			}
//		}
//
//		// related to #14410
//		if ( ! empty($removedSections)) {
//			$publicEm->flush();
//		}
//
//		$gridSet = $this->getGridSet();
//		$gridIds = array();
//		foreach ($gridSet as $grid) {
//			$publicEm->merge($grid);
//			$gridIds[] = $grid->getId();
//		}
//
//		// Now we need to remove deleted blocks from public schema
////		$publicBlockIds = array();
//		$draftBlockIds = array();
//
//		// a) For all grids in page we will search for public blocks
//		$blockCn = Entity\Abstraction\Block::CN();
//		$gridCn = Entity\Abstraction\Grid::CN();
//
//		$publicBlockIds = $publicEm->createQuery("SELECT b.id FROM {$blockCn} b INNER JOIN {$gridCn} g WITH g.id = b.grid WHERE g.id IN (?0)")
//				->setParameters(array($gridIds))
//				->getResult(ColumnHydrator::HYDRATOR_ID);
//
//		// b) Collect ids of blocks in page block set
//		$blockSet = $this->getBlockSet();
//		foreach ($blockSet as $block) {
//			$publicEm->merge($block);
//			$draftBlockIds[] = $block->getId();
//		}
//
//		// c) calculate ids difference
//		$removedBlockIds = array_diff($publicBlockIds, $draftBlockIds);
//
//		// d) instead of creating DELETE query, load the entities and call EntityManager::remove()
//		//    so the cascade cleanup will be done by the Doctrine
//		if ( ! empty($removedBlockIds)) {
//			$removedBlocks = $this->loadEntitiesByIdList($publicEm, Entity\Abstraction\Block::CN(), $removedBlockIds);
//			foreach ($removedBlocks as $removedBlock) {
//				// properties, metadata and referenced elements should be removed by cascade
//				$publicEm->remove($removedBlock);
//			}
//		}
//
//		// Need find the difference between the block property metadata records
//		// as metadata can be removed while property continues to exist
//		$existingPropertyIds = array();
//		$existingMetaIds = array();
//
//		// Merge all blocks on page (inlcuding the global ones)
//		$blockPropertySet = $this->getBlockPropertySet();
//		foreach ($blockPropertySet as $blockProperty) {
//			$publicEm->merge($blockProperty);
//
//			$existingPropertyIds[] = $blockProperty->getId();
//
//			$metaCollection = $blockProperty->getMetadata();
//
//			foreach ($metaCollection as $meta) {
//				$existingMetaIds[] = $meta->getId();
//				//$publicEm->merge($meta);
//			}
//		}
//
//		// Cleanup removed public properties by selecting all the properties that are not in draft
//		// and have block_id one of the block in set
//		$propertyCn = Entity\BlockProperty::CN();
//		if ( ! empty($draftBlockIds)) {
//
//			$propertyDql = "SELECT bp FROM {$propertyCn} bp WHERE bp.id NOT IN (?0) AND bp.block IN (?1)";
//
//			$removedProperties = $publicEm->createQuery($propertyDql)
//					->setParameters(array($existingPropertyIds, $draftBlockIds))
//					->getResult();
//
//			foreach ($removedProperties as $removedProperty) {
//				$publicEm->remove($removedProperty);
//			}
//		}
//
//		$metadataCn = Entity\BlockPropertyMetadata::CN();
//		$dql = "SELECT m FROM {$metadataCn} m WHERE m.blockProperty IN (?0) AND m.id NOT IN (?1)";
//		$removedMeta = $publicEm->createQuery($dql)
//				->setParameters(array($existingPropertyIds, $existingMetaIds))
//				->getResult();
//
//		foreach ($removedMeta as $meta) {
//			$publicEm->remove($meta);
//		}
//
//		$publicEm->flush();
//
//		foreach ($blockPropertySet as $blockProperty) {
//			$metaCollection = $blockProperty->getMetadata();
//			foreach ($metaCollection as $meta) {
//				$publicEm->merge($meta);
//			}
//		}
//
//		$publicEm->flush();
//
//		$eventManager->fire(Event\PageCmsEvents::pagePostPublish, $eventArgs);

//		// 0. Copy placeHolder groups
//		$placeHolderGroups = $draftData->getPlaceHolderGroups();
//		foreach ($placeHolderGroups as $group) {
//			$publicEm->merge($group);
//
//			$placeHolders = $group->getPlaceholders();
//			foreach ($placeHolders as $placeHolder) {
//				$publicEm->merge($placeHolder);
//			}
//		}

		/*
		 * For some reason in some cases Doctrine couldn't insert block because
		 * placeholder wasn't yet created
		 */
		$publicEm->flush();

		// 4.2. Delete not used placeholders
		$placeHolderEntity = Entity\Abstraction\PlaceHolder::CN();
		$localizationId = $draftData->getId();
		$placeHolderIds = array_values($placeHolderIds);
		$placeHolderNames = array_values($placeHolderNames);

		if ( ! empty($placeHolderIds)) {

			$notUsedPlaceHolders = $publicEm->createQuery("SELECT ph FROM $placeHolderEntity ph
					WHERE ph.localization = ?0 AND ph.id NOT IN (?1) AND ph.name IN (?2)")
					->setParameters(array($localizationId, $placeHolderIds, $placeHolderNames))
					->getResult();

			foreach ($notUsedPlaceHolders as $notUsedPlaceHolder) {
				$publicEm->remove($notUsedPlaceHolder);
			}
		}

		// 4.3. Flush just to be sure to track erorrs early
		$publicEm->flush();


		// 5. Merge all blocks in 1
		foreach ($draftBlocks as $block) {
			$publicEm->merge($block);
		}
		

//		$pageRequest = $this->getPageRequest();
//
//		$copyContent = function() use ($pageRequest) {
//					$pageRequest->publish();
//				};
//
//		$publicEm->transactional($copyContent);
//
//		$this->triggerPageCmsEvent(Event\PageCmsEvents::pagePostPublish);
	}

	/**
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 * @param Localization $localization
	 */
	private function getBlocksInLocalization($entityManager, Localization $localization)
	{
		$queryString = 'SELECT b FROM %s b JOIN b.placeHolder p WHERE p.localization = ?0';

		$blocks = $entityManager->createQuery(sprintf($queryString, Block::CN()))
				->setParameters(array($localization->getId()));

		foreach ($blocks as $key => $block) {
			if ($block instanceof PageBlock
					&& $block->isInactive()) {

				unset($blocks[$key]);
			}
		}

		return $blocks;
	}
}