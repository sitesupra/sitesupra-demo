<?php

namespace Supra\Controller\Pages\Request;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Entity\BlockRelation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\SharedBlockProperty;
use Supra\Controller\Pages\Set\BlockPropertySet;

/**
 * 
 */
class SharedPropertyFinder
{
	/**
	 * @var EntityManager
	 */
	private $em;
	
	/**
	 * Blocks registered, by ID
	 * @var array
	 */
	private $blocks = array();

	/**
	 * Localizations registered, by ID
	 * @var array
	 */
	private $localizations = array();
	
	/**
	 * Array containing shared property search data
	 * @var array
	 */
	private $sharedPropertyData = array();
	
	/**
	 * Local storage for block relation groupId for requested IDs
	 * @var array
	 */
	private $blockGroupIds = array();
	
	/**
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}
	
	/**
	 * Adds the block-localization pair to find the shared properties
	 * @param Block $block
	 * @param Localization $localization
	 */
	public function addBlock(Block $block, Localization $localization)
	{
		$blockId = $block->getId();
		$localizationId = $localization->getId();
		
		$this->blocks[$blockId] = $block;
		$this->localizations[$localizationId] = $localization;
		
		// Shared block properties
		$class = $block->getComponentClass();
		$configuration = ObjectRepository::getComponentConfiguration($class);

		// Collects all shared properties
		if ($configuration instanceof BlockControllerConfiguration) {
			foreach ((array) $configuration->properties as $property) {
				/* @var $property BlockPropertyConfiguration */
				if ($property->shared) {

					$this->sharedPropertyData[] = array(
						'block_id' => $blockId,
						'localization_id' => $localizationId,
						'master_id' => $localization->getMaster()->getId(),
						'name' => $property->name,
						'type' => $property->editable,
					);
				}
			}
		}
	}
	
	/**
	 * Executes the query with 2 columns, returns array indexed by first column
	 * @param Query $query
	 * @return array
	 */
	private function indexedResult(Query $query)
	{
		$result = array();
		$data = $query->getResult(Query::HYDRATE_ARRAY);

		foreach ($data as $row) {
			$base = array_shift($row);
			$related = array_shift($row);

			$result[$base] = $related;
		}

		return $result;
	}
	
	/**
	 * Finds all block relation identifiers for passed block ID values, keeps locally
	 * @param array $blockIds
	 * @return array
	 */
	private function loadBlockGroupIds(array $blockIds)
	{
		if (empty($blockIds)) {
			return array();
		}
		
		$blockRelationCn = BlockRelation::CN();
		
		// Find related blocks groupId
		$dql = "SELECT br.blockId, br.groupId FROM $blockRelationCn br
				WHERE br.blockId IN (:blockIds)";

		$query = $this->em->createQuery($dql)
				->setParameter('blockIds', $blockIds);
		$blockGroupIds = $this->indexedResult($query);
		
		$this->blockGroupIds += $blockGroupIds;
	}
	
	/**
	 * Returns block relation ID if has been loaded by loadBlockGroupIds method
	 * @param string $blockId
	 * @return string
	 */
	private function getBlockGroupId($blockId)
	{
		if (isset($this->blockGroupIds[$blockId])) {
			return $this->blockGroupIds[$blockId];
		} else {
			return null;
		}
	}
	
	/**
	 * Finds the "original" (the first created) property ID for each block 
	 * relation group ID, master page ID, property name and type. Returns array
	 * of data arrays containing the block property ID and grouping fields.
	 * @return array
	 */
	private function loadOriginalPropertyData()
	{
		$em = $this->em;
		
		$blockRelationCn = BlockRelation::CN();
		$shares = &$this->sharedPropertyData;
		$sharedBlockIds = array_keys($this->blocks);

		if (empty($shares)) {
			return array();
		}

		$this->loadBlockGroupIds($sharedBlockIds);

		foreach ($shares as $key => $share) {
			$blockId = $share['block_id'];
			
			$groupId = $this->getBlockGroupId($blockId);

			if ( ! empty($groupId)) {
				$shares[$key]['block_group_id'] = $groupId;
			} else {
				unset($shares[$key]);
			}
		}
			
		if (empty($shares)) {
			return array();
		}

		// Build query
		// Find minimal property IDs from the given
		$sharedPropertyQueryBuilder = $em->createQueryBuilder();
		$sharedPropertyQueryBuilder->from(BlockProperty::CN(), 'bp')
				->from($blockRelationCn, 'br')
				->join('bp.localization', 'l')
				->join('l.master', 'm')
				->andWhere('br.blockId = bp.block')
				->select('MIN(bp.id) AS block_property_id,
						br.groupId AS block_group_id,
						m.id AS master_id,
						bp.name as name,
						bp.type as type')
				->groupBy('br.groupId, m.id, bp.name, bp.type');

		$or = $sharedPropertyQueryBuilder->expr()->orX();
		$sharedPropertyQueryBuilder->andWhere($or);
		$queryPosition = 0;

		foreach ($shares as $share) {
			$masterId = $share['master_id'];
			$groupId = $share['block_group_id'];
			$name = $share['name'];
			$type = $share['type'];

			$masterPosition = $queryPosition ++;
			$groupPosition = $queryPosition ++;
			$typePosition = $queryPosition ++;
			$namePosition = $queryPosition ++;

			$or->add('m.id = ?' . $masterPosition . ' AND br.groupId = ?' . $groupPosition
					. ' AND bp.type = ?' . $typePosition . ' AND bp.name = ?' . $namePosition);
			$sharedPropertyQueryBuilder->setParameter($masterPosition, $masterId)
					->setParameter($groupPosition, $groupId)
					->setParameter($typePosition, $type)
					->setParameter($namePosition, $name);
		}

		$originalPropertyData = $sharedPropertyQueryBuilder->getQuery()
				->getResult(Query::HYDRATE_ARRAY);
		
		return $originalPropertyData;
	}
	
	/**
	 * Finds the original versions of the shared block properties
	 * @return array
	 */
	public function find()
	{
		$originalPropertyData = $this->loadOriginalPropertyData();
		
		$ids = $this->findPropertyIds($originalPropertyData);
		$properties = $this->findProperties($ids);
		
		$sharedBlockProperties = $this->createSharedBlockProperties($originalPropertyData, $properties);
		
		return $sharedBlockProperties;
	}
	
	/**
	 * Finds all block property original versions and replaces/adds them into
	 * the property set passed
	 * @param BlockPropertySet $set
	 */
	public function replaceInPropertySet(BlockPropertySet $set)
	{
		$existingIds = $set->collectIds();
		
		$originalPropertyData = $this->loadOriginalPropertyData();
		$ids = $this->findPropertyIds($originalPropertyData);
		
		// Filter out already existent properties
		$ids = array_diff($ids, $existingIds);
		
		$properties = $this->findProperties($ids);
		
		$sharedBlockProperties = $this->createSharedBlockProperties($originalPropertyData, $properties);
		
		$blockIds = $set->getBlockIdList();
		$this->loadBlockGroupIds($blockIds);
		
		// Replace matching properties
		foreach ($sharedBlockProperties as $key => $sharedProperty) {
			/* @var $sharedProperty BlockProperty */
			
			// Replace with match
			foreach ($set as $key => $property) {
				/* @var $property BlockProperty */
				
				if ($this->propertyMatch($property, $sharedProperty)) {
					$sharedProperty->setReplacedBlockProperty($property);
					$set->offsetSet($key, $sharedProperty);
					unset($sharedBlockProperties[$key]);
					
					continue 2;
				}
			}
			
		}
		
		$set->appendArray($sharedBlockProperties);
	}
	
	/**
	 * Returns true if both properties belongs to the same block relation group,
	 * localization master and has the same type and name.
	 * 
	 * @param BlockProperty $property1
	 * @param BlockProperty $property2
	 * @return boolean
	 */
	private function propertyMatch(BlockProperty $property1, BlockProperty $property2)
	{
		$data1 = $this->getPropertyData($property1);
		
		if (empty($data1)) {
			return false;
		}
		
		$data2 = $this->getPropertyData($property2);
		
		if (empty($data2)) {
			return false;
		}
		
		return $this->propertyDataMatch($data1, $data2);
	}
	
	/**
	 * Convert the property into array which can be used to match the properties
	 * @param BlockProperty $property
	 * @return array
	 */
	private function getPropertyData(BlockProperty $property)
	{
		$blockId = $property->getBlock()->getId();
		$groupId = $this->getBlockGroupId($blockId);
		
		if (empty($groupId)) {
			return null;
		}
		
		$masterId = $property->getLocalization()->getMaster()->getId();
		
		$data = array(
			'master_id' => $masterId,
			'block_group_id' => $groupId,
			'name' => $property->getName(),
			'type' => $property->getType()
		);
		
		return $data;
	}
	
	/**
	 * Loads properties by ID list provided, indexed by ID
	 * @param array $ids
	 * @return array
	 */
	private function findProperties(array $ids)
	{
		if (empty($ids)) {
			return array();
		}
		
		$properties = $this->em->getRepository(BlockProperty::CN())
				->findBy(array('id' => $ids));
		
		$byId = array();
		
		foreach ($properties as $property) {
			/* @var $property BlockProperty */
			$id = $property->getId();
			$byId[$id] = $property;
		}
		
		return $byId;
	}
	
	/**
	 * Loads property ID list from the property data array
	 * @param array $originalPropertyData
	 * @return array
	 */
	private function findPropertyIds(array $originalPropertyData)
	{
		$ids = array();
		
		foreach ($originalPropertyData as $row) {
			$ids[] = $row['block_property_id'];
		}
		
		return $ids;
	}
	
	/**
	 * Creates array of shared block properties from the data arrays
	 * @param array $originalPropertyData
	 * @param array $properties
	 * @return SharedBlockProperty
	 */
	private function createSharedBlockProperties(array $originalPropertyData, array $properties)
	{
		$sharedProperties = array();
		
		$shares = &$this->sharedPropertyData;
		
		foreach ($shares as $share) {
			foreach ($originalPropertyData as $row) {
				
				// Property object wasn't found (weird) or was removed from the list
				$propertyId = $row['block_property_id'];

				if ( ! isset($properties[$propertyId])) {
					continue;
				}
				
				if ($this->propertyDataMatch($share, $row)) {
					$blockId = $share['block_id'];
					$localizationId = $share['localization_id'];
					$groupId = $share['block_group_id'];
					
					$blockProperty = $properties[$propertyId];
					$block = $this->blocks[$blockId];
					$localization = $this->localizations[$localizationId];
					
					$sharedProperties[] = new SharedBlockProperty($blockProperty, $block, $localization, $groupId, $propertyId);
					
					continue 2;
				}
			}
		}
		
		return $sharedProperties;
	}
	
	/**
	 * Compares property data array with other property data
	 * @param array $property1
	 * @param array $property2
	 * @return boolean
	 */
	private function propertyDataMatch(array $property1, array $property2)
	{
		$unique = array('master_id', 'block_group_id', 'name', 'type');

		foreach ($unique as $key) {
			if ($property1[$key] !== $property2[$key]) {
				return false;
			}
		}
		
		return true;
	}
}
