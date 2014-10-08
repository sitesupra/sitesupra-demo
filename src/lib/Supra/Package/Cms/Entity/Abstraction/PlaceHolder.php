<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\PagePlaceHolder;
use Supra\Controller\Pages\Entity\TemplatePlaceHolder;
use Supra\Controller\Pages\Entity\PlaceHolderGroup;

/**
 * Page and template place holder data abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Package\Cms\Entity\TemplatePlaceHolder", "page" = "Supra\Package\Cms\Entity\PagePlaceHolder"})
 */
abstract class PlaceHolder extends VersionedEntity implements
		AuditedEntity,
		OwnedEntityInterface
{
	/**
	 * FIXME: should be fixed after DDC-482 is done or else there is duplicate
	 *		column for distinguishing the place holder type,
	 *		0: template; 1: page
	 * FIXME: The DDC-482 was done but "INSTANCE OF" was created for WHERE
	 *		conditions only
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $type;

	/**
	 * @Column(name="name", type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\PlaceHolderGroup", inversedBy="placeholders", cascade={"persist"})
	 * @var \Supra\Controller\Pages\Entity\PlaceHolderGroup
	 */
	protected $group;
	
	/**
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\Abstraction\Block", mappedBy="placeHolder", cascade={"persist", "remove"})
	 * @OrderBy({"position" = "ASC"})
	 * @var Collection
	 */
	protected $blocks;

	/**
	 * @ManyToOne(targetEntity="Localization", inversedBy="placeHolders")
	 * @JoinColumn(name="localization_id", referencedColumnName="id", nullable=false)
	 * @var Localization
	 */
	protected $localization;

	/**
	 * Constructor
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();
		$this->setName($name);
		$this->blocks = new ArrayCollection();
	}

	/**
	 * Set layout place holder name
	 * @param string $Name
	 */
	protected function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get layout place holder name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @return string
	 */
	public function getTitle()
	{
		$name = $this->name;
		$names = preg_split('/(\s|_|\-|(?<=[a-z])(?=[A-Z])|(?<=[0-9])(?=[^0-9])|(?<=[^0-9])(?=[0-9]))+/', $name);
		
		$names = array_map('trim', $names);
		$names = array_map('ucfirst', $names);
		
		$title = implode(' ', $names);
		
		return $title;
	}

	/**
	 * Place holder locked status always is false for pages
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isLocked()
	{
		return $this->getLocked() === true;
	}

	/**
	 * Get blocks
	 * @return Collection
	 */
	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Adds a block
	 * @param Block $block
	 */
	public function addBlock(Block $block)
	{
		if ($this->lock('block')) {
			$this->matchDiscriminator($block);
			if ($this->addUnique($this->blocks, $block)) {
				$block->setPlaceHolder($this);
			}
			$this->unlock('block');
		}
	}
	
	/**
	 * Set master localization
	 * @param Localization $localization
	 */
	public function setMaster(Localization $localization)
	{
		$this->matchDiscriminator($localization);
		if ($this->writeOnce($this->localization, $localization)) {
			$this->localization->addPlaceHolder($this);
		}
	}

	/**
	 * @return Localization
	 */
	public function getMaster()
	{
		return $this->localization;
	}

	/**
	 * Alias of getMaster()
	 * @return Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}
	
	/**
	 * @return int
	 */
	public function getMaxBlockPosition()
	{
		$blocks = $this->getBlocks();
		$sort = null;
		
		/* @var $block Block */
		foreach ($blocks as $block) {
			$sort = max($sort, $block->getPosition());
		}
		
		return $sort;
	}
	
	/**
	 * Creates new instance based on the discriminator of source entity
	 * @param Localization $localization
	 * @param string $name
	 * @param PlaceHolder $source
	 * @return PlaceHolder
	 */
	public static function factory(Localization $localization, $name, PlaceHolder $source = null)
	{
		$discriminator = $localization::DISCRIMINATOR;
		$placeHolder = null;
		
		switch ($discriminator) {
			case self::TEMPLATE_DISCR:
				$placeHolder = new TemplatePlaceHolder($name);
				break;
			
			case self::PAGE_DISCR:
			case self::APPLICATION_DISCR:
				$placeHolder = new PagePlaceHolder($name);
				break;
			
			default:
				throw new Exception\LogicException("Not recognized discriminator value for entity {$localization}");
		}
		
		if ( ! is_null($source)) {
		
			$blocks = $source->getBlocks();

			/* @var $block Block */
			foreach ($blocks as $block) {

				// Don't clone locked blocks
				if ($block->getLocked()) {
					continue;
				}

				// Create new block
				$newBlock = Block::factoryClone($localization, $block);
				$placeHolder->addBlock($newBlock);

				// Should persist by cascade
//				// Persist only for draft connection with ID generation
//				if ($this instanceof PageRequestEdit) {
//					$em->persist($block);
//				}

				// Not used anymore
//				$templateBlockId = $block->getId();
//				$templateData = $source->getMaster();
//				$locale = $this->getLocale();
//
//				// Find the properties to copy from the template
//				$blockPropertyEntity = \Supra\Controller\Pages\Entity\BlockProperty::CN();
//				
//				$dql = "SELECT p FROM $blockPropertyEntity AS p
//								WHERE p.block = ?0 AND p.localization = ?1";
//
//				$query = $em->createQuery($dql);
//				$query->setParameters(array(
//					$templateBlockId,
//					$templateData->getId()
//				));
//
//				$blockProperties = $query->getResult();
//
//				$localization = $this->getPageLocalization();
				$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('#cms');				
				
				// Block properties are loaded from the block and filtered manually now
				$blockProperties = $block->getBlockProperties();
								
				$metadataMap = array();
				$clonedProperties = array();
				
				/* @var $blockProperty \Supra\Controller\Pages\Entity\BlockProperty */
				foreach ($blockProperties as $blockProperty) {
					// We are interested only in the properties belonging to the current localization
//					if ($blockProperty->getLocalization()->equals($localization)) {
						$metadataCollection = $blockProperty->getMetadata();
						
						$blockProperty = clone($blockProperty);

						/* @var $blockProperty \Supra\Controller\Pages\Entity\BlockProperty */
						
						$blockProperty->resetLocalization();
						$blockProperty->resetBlock();
						
						$blockProperty->setLocalization($localization);
						$blockProperty->setBlock($newBlock);
						
						$clonedProperties[] = $blockProperty;

						foreach ($metadataCollection as $metadata) {
							/* @var $metadata \Supra\Controller\Pages\Entity\BlockPropertyMetadata */

							$newMetadata = clone($metadata);
							$newMetadata->setBlockProperty($blockProperty);
							$em->persist($newMetadata);
							
							$metadataMap[$metadata->getId()] = $newMetadata;
						}
//					}

					// Should persist by cascade
//					// Persist only for draft connection with ID generation
//					if ($this instanceof PageRequestEdit) {
						$em->persist($blockProperty);
//					}
				}
				
				foreach ($clonedProperties as $property) {
					if ($property->getMasterMetadataId() !== null) {
						$metaId = $property->getMasterMetadataId();
						if (isset($metadataMap[$metaId])) {
							$property->setMasterMetadata($metadataMap[$metaId]);
						}
					}
				}
			}
		}

		return $placeHolder;
	}
	
	public function getOwner() 
	{
		return $this->localization;
	}
	
	/**
	 * @return \Supra\Controller\Pages\Entity\PlaceHolderGroup
	 */
	public function getGroup()
	{
		return $this->group;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\PlaceHolderGroup $group
	 */
	public function setGroup(\Supra\Controller\Pages\Entity\PlaceHolderGroup $group)
	{
		$this->group = $group;
		$group->addPlaceholder($this);
	}
	
	/**
	 * 
	 */
	public function resetGroup()
	{
		$this->group = null;
	}
	
}
