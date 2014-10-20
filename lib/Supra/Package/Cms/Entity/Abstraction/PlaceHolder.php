<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Package\Cms\Entity\PagePlaceHolder;
use Supra\Package\Cms\Entity\TemplatePlaceHolder;

/**
 * Page and template place holder data abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Package\Cms\Entity\TemplatePlaceHolder", "page" = "Supra\Package\Cms\Entity\PagePlaceHolder"})
 */
abstract class PlaceHolder extends VersionedEntity implements
		AuditedEntity
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
	 * @param Block $beforeThis
	 * @param Block $block
	 * @throws \LogicException
	 */
	public function addBlockBefore(Block $beforeThis, Block $block)
	{
		if (! $this->equals($beforeThis->getPlaceHolder())) {
			throw new \LogicException(sprintf(
					'Block [%s] you are trying to insert before belongs to another place holder.',
					$beforeThis->getId()
			));
		}
		
		$beforeThisPosition = $beforeThis->getPosition();
		
		foreach ($this->blocks as $existingBlock) {
			/* @var $existingBlock Block */
			if ($existingBlock->getPosition() >= $beforeThisPosition) {
				$existingBlock->setPosition($existingBlock->getPosition() + 1);
			}
		}

		$block->setPosition($beforeThisPosition);

		$this->addBlock($block);
	}

	/**
	 * @param Block $block
	 */
	public function addBlockLast(Block $block)
	{
		$maxPosition = null;

		foreach ($this->blocks as $existingBlock) {
			/* @var $block Block */
			$maxPosition = max($existingBlock->getPosition(), $maxPosition);
		}

		$block->setPosition($maxPosition + 1);

		$this->addBlock($block);
	}

	/**
	 * Removes block from collection and recalculates other blocks position indexes.
	 *
	 * @param Block $block
	 * @throws \InvalidArgumentException
	 */
	public function removeBlock(Block $block)
	{
		if (! $this->equals($block->getPlaceHolder())
				|| ! $this->blocks->contains($block)) {
			throw new \InvalidArgumentException('Block does not belongs to this placeholder.');
		}

		$position = $block->getPosition();
	
		$this->blocks->removeElement($block);

		foreach ($this->blocks as $existingBlock) {
			if ($existingBlock->getPosition() > $position) {
				$existingBlock->setPosition($existingBlock->getPosition() - 1);
			}
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
	 * Creates new instance based on the discriminator of source entity.
	 * 
	 * @param Localization $localization
	 * @param string $name
	 * @param PlaceHolder $source
	 * @return PlaceHolder
	 */
	public static function factory(Localization $localization, $name, PlaceHolder $source = null)
	{
		$placeHolder = null;
		
		switch ($localization::DISCRIMINATOR) {
			case self::TEMPLATE_DISCR:
				$placeHolder = new TemplatePlaceHolder($name);
				break;
			case self::PAGE_DISCR:
			case self::APPLICATION_DISCR:
				$placeHolder = new PagePlaceHolder($name);
				break;
			default:
				throw new \LogicException("Not recognized discriminator value for entity [{$localization}]");
		}
		
		if ($source !== null) {
			foreach ($source->getBlocks() as $block) {
				if (! $block->getLocked()) {
					$placeHolder->addBlock(Block::factory($localization, $block));
				}
			}
		}

		return $placeHolder;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		return $this->localization;
	}

}
