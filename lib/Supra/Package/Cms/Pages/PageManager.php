<?php

namespace Supra\Package\Cms\Pages;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Locale\LocaleInterface;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Pages\DeepCopy\DoctrineCollectionFilter;
use Supra\Package\Cms\Pages\DeepCopy\DoctrineEntityFilter;
use DeepCopy\DeepCopy;
use DeepCopy\Filter\KeepFilter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyTypeMatcher;
use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;

class PageManager implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Makes deep copy with persisting.
	 *
	 * @param Localization $source
	 * @param LocaleInterface $targetLocale
	 * @return Localization
	 */
	public function copyLocalization(
			EntityManager $entityManager,
			Localization $source,
			LocaleInterface $targetLocale
	) {
		$deepCopy = new DeepCopy();

		// Matches RedirectTargetPage::$page property.
		// Keeps the $page property redirect target is referencing to.
		$deepCopy->addFilter(
				new KeepFilter(),
				new PropertyMatcher('Supra\Package\Cms\Entity\RedirectTargetPage', 'page')
		);

		// Matches PageLocalization::$template.
		// Prevents the template to be cloned.
		$deepCopy->addFilter(
				new KeepFilter(),
				new PropertyMatcher('Supra\Package\Cms\Entity\PageLocalization', 'template')
		);

		// Matches Localization::$master.
		// Prevents AbstractPage to be cloned.
		$deepCopy->addFilter(
				new KeepFilter(),
				new PropertyMatcher('Supra\Package\Cms\Entity\Abstraction\Localization', 'master')
		);

		// Matches Block::$blockProperties collection.
		// Replaces with empty collection, since block properties can be obtained via Localization::$blockProperties.
		$deepCopy->addFilter(
				new DoctrineEmptyCollectionFilter(),
				new PropertyMatcher('Supra\Package\Cms\Entity\Abstraction\Block', 'blockProperties')
		);

		// Matches Localization::$lock.
		// Nullifies editing lock entity.
		$deepCopy->addFilter(
				new SetNullFilter(),
				new PropertyMatcher('Supra\Package\Cms\Entity\Abstraction\Localization', 'lock')
		);

		// Matches Entity Collection.
		// Creates Copy and persists the elements in it.
		$deepCopy->addFilter(
				new DoctrineCollectionFilter($entityManager),
				new PropertyTypeMatcher('Doctrine\Common\Collections\Collection')
		);

		// Matches any Entity.
		// Creates copy and persists it.
		$deepCopy->addFilter(
				new DoctrineEntityFilter($entityManager),
				new PropertyTypeMatcher('Supra\Package\Cms\Entity\Abstraction\Entity')
		);

		$copiedLocalization = $deepCopy->copy($source);

		$copiedLocalization->setLocaleId($targetLocale->getId());

		$entityManager->persist($copiedLocalization);

		return $copiedLocalization;
	}

	/**
	 * Recursively clone the page or page localization object
	 * @param Entity\Abstraction\Entity $entity
	 * @return Entity\Abstraction\Entity
	 */
	private function recursiveClone(Entity $entity, $newLocale = null)
	{
		$masterId = null;

		// make sure it isn't called for localization without making new locale version
		if ( ! empty($newLocale)) {

			if ( ! $entity instanceof Entity\Abstraction\Localization) {
				throw new \RuntimeException("Locale can be passed to clone only with localization entity");
			}

			$masterId = $entity->getMaster()->getId();
		}

		$this->_clonedEntities = array();
		$this->_cloneRecursionDepth = 0;

		$newEntity = $this->recursiveCloneInternal($entity, null, true);
		$this->createBlockRelations();
		$this->recursiveCloneFillOwningSide($newEntity, $masterId, $newLocale);

		return $newEntity;
	}

	/**
	 * Recursively goes through entity collections, clones and persists elements from them
	 *
	 * @param Entity $entity
	 * @param Entity $associationOwner
	 * @param boolean $skipPathEvent
	 * @return Entity
	 */
	private function recursiveCloneInternal(EntityManager $entityManager, Entity $source)
	{
		$entityHash = spl_object_hash($source);

		if (isset($this->_clonedEntities[$entityHash])) {
			return $this->_clonedEntities[$entityHash];
		}

		$newEntity = clone $source;

		$this->_clonedEntities[$entityHash] = $newEntity;
		$this->_clonedEntitySources[spl_object_hash($newEntity)] = $source;

		$entityData = $entityManager->getUnitOfWork()
				->getOriginalEntityData($source);

		$classMetadata = $entityManager->getClassMetadata(get_class($source));

		foreach ($classMetadata->getAssociationMappings() as $fieldName => $association) {

			// Don't visit this association, might get other blocks in template cloning
			if ($source instanceof Block && $fieldName == 'blockProperties') {
				continue;
			}

			if (! $association['isOwningSide']) {

				$newValue = null;

				if (isset($entityData[$fieldName])) {
					if ($entityData[$fieldName] instanceof Collection) {
						$newValue = new ArrayCollection();
						foreach ($entityData[$fieldName] as $offset => $collectionItem) {
							$newChild = $this->recursiveCloneInternal($collectionItem);
							$newValue->offsetSet($offset, $newChild);
						}
					} else {
						$newValue = $this->recursiveCloneInternal($entityData[$fieldName]);
					}

					$objectReflection = new \ReflectionObject($newEntity);
					$propertyReflection = $objectReflection->getProperty($fieldName);
					$propertyReflection->setAccessible(true);
					$propertyReflection->setValue($newEntity, $newValue);
				}
			}
		}

		$entityManager->persist($newEntity);

		return $newEntity;
	}

	/**
	 * Fills the new objects UP
	 * @param Entity\Abstraction\Entity $newEntity
	 * @throws \RuntimeException
	 */
	private function recursiveCloneFillOwningSide(Entity $newEntity, $masterId = null, $newLocale = null)
	{
		$em = $this->getDoctrineEntityManager();
		
		foreach ($entityManager->getClassMetadata(get_class($newEntity)) as $fieldName => $association) {

			// Don't visit this association, will get properties from localization
			if ($newEntity instanceof Block && $fieldName == 'blockProperties') {
				continue;
			}

			$fieldReflection = $classMetadata->reflFields[$fieldName];
			/* @var $fieldReflection \ReflectionProperty */
			$fieldReflection->setAccessible(true);
			$associationValue = $fieldReflection->getValue($newEntity);

			if ( ! $association['isOwningSide']) {

				if ($associationValue instanceof Collection) {
					foreach ($associationValue as $collectionItem) {
						$this->recursiveCloneFillOwningSide($collectionItem, $masterId, $newLocale);
					}
				} else {
					$this->recursiveCloneFillOwningSide($associationValue, $masterId, $newLocale);
				}
			} else {

				if ( ! is_null($associationValue)) {
					$joinedEntityHash = ObjectRepository::getObjectHash($associationValue);

					if (isset($this->_clonedEntities[$joinedEntityHash])) {
						$newJoinedEntity = $this->_clonedEntities[$joinedEntityHash];
						$fieldReflection->setValue($newEntity, $newJoinedEntity);
					} else {

						// Not found. Possibilities are:
						// * The object of lower level was cloned (e.g.
						//		localization was cloned, localization-page
						//		association is being checked). Don't need to do
						//		anything.
						// * Block property pointing to parent template block.
						//		Need to try changing if new localization is
						//		being created.

						if ( ! empty($newLocale) && ! empty($masterId)) {

							if ($newEntity instanceof Entity\BlockProperty && $fieldName == 'block') {

								/* @var	$associationValue Entity\Abstraction\Block */

								$oldBlockId = $associationValue->getId();


								if ($relation instanceof Entity\BlockRelation) {
									$groupId = $relation->getGroupId();

									$matchingBlock = $em->createQueryBuilder()
											->select('b')

											// All required tables
											->from(Entity\BlockRelation::CN(), 'r')
											->from(Entity\Abstraction\Block::CN(), 'b')
											->join('b.placeHolder', 'ph')
											->join('ph.localization', 'l')

											// condition to bind block and relation
											->andWhere('r.blockId = b.id')

											// group condition
											->andWhere('r.groupId = :groupId')
											->setParameter('groupId', $groupId)

											// locale condition
											->andWhere('l.locale = :locale')
											->setParameter('locale', $newLocale)

	//										// master condition
	//										->andWhere('l.master = :masterId')
	//										->setParameter('masterId', $masterId)

											->from(Entity\Abstraction\Block::CN(), 'b2')
											->andWhere('b2.id = :oldBlockId')
											->setParameter('oldBlockId', $oldBlockId)
											->join('b2.placeHolder', 'ph2')
											->join('ph2.localization', 'l2')

											// Find blocks with common parent master
											->andWhere('l.master = l2.master')

											// finally..
											->getQuery()
											->getOneOrNullResult();
								}

								// Don't need such property..
								if (empty($matchingBlock)) {
									$em->remove($newEntity);
								} else {
									$fieldReflection->setValue($newEntity, $matchingBlock);
								}
							}
						}
					}
				}
			}
		}
	}
}