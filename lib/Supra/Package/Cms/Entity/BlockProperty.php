<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Localization;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class BlockProperty extends Abstraction\Entity implements \IteratorAggregate
{
	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\Localization", inversedBy="blockProperties")
	 * @JoinColumn(nullable=false)
	 * @var Localization
	 */
	protected $localization;

	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\Block", inversedBy="blockProperties", cascade={"persist"})
	 * @JoinColumn(name="block_id", referencedColumnName="id", nullable=false)
	 * @var Block
	 */
	protected $block;

	/**
	 * Content type (class name of Editable class)
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $type;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $hierarchicalName;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;
	
	/**
	 * @OneToMany(targetEntity="BlockProperty", mappedBy="parent", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $properties;

	/**
	 * @ManyToOne(targetEntity="BlockProperty", inversedBy="properties", cascade={"persist"})
	 * @var BlockProperty
	 */
	protected $parent;

	/**
	 * Value additional data about links, images
	 * 
	 * @OneToMany(
	 *		targetEntity="BlockPropertyMetadata",
	 *		mappedBy="blockProperty",
	 *		cascade={"persist", "remove"},
	 *		indexBy="name",
	 *		orphanRemoval=true
	 * )
	 * 
	 * @var Collection
	 */
	protected $metadata;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();

		$this->name = (string) $name;
		$this->metadata = new ArrayCollection();
		$this->properties = new ArrayCollection();
	}

	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}

	/**
	 * @param Localization $data
	 */
	public function setLocalization(Localization $data)
	{
		if ($this->writeOnce($this->localization, $data)) {
			$this->checkScope($this->localization);
		}
	}

	/**
	 * @return Collection
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}
	
	public function resetBlock()
	{
		$this->block = null;
	}
	
	public function resetLocalization()
	{
		$this->localization = null;
	}
	
	/**
	 * @param BlockPropertyMetadata $metadata
	 */
	public function addMetadata(BlockPropertyMetadata $metadata)
	{
		$name = $metadata->getName();
		$this->metadata->offsetSet($name, $metadata);
	}
	
	/**
	 * @return Block
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * @param Block $block
	 */
	public function setBlock(Block $block)
	{
		$this->block = $block;
		//if ($this->writeOnce($this->block, $block)) {
			$this->checkScope($this->block);
		//}
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param null|string $value
	 */
	public function setValue($value)
	{
		if ($value !== null && ! is_string($value)) {
			throw new \UnexpectedValueException(sprintf(
					'Block property accepts only NULL and string values, [%s] passed in.',
					gettype($value)
			));
		}
		
		$this->value = $value;
	}
	
	/**
	 * @return string
	 */
	public function getEditableClass()
	{
		return $this->type;
	}

	/**
	 * @TODO: just keep editable ID?
	 * @param string $class
	 */
	public function setEditableClass($class)
	{
		$this->type = $class;
	}

	/**
	 * Checks if associations scopes are matching
	 * @param Entity $object
	 */
	private function checkScope(Entity &$object)
	{
		if ( ! empty($this->localization) && ! empty($this->block)) {
			try {
				// do not-strict match (allows page data with template block)
				$this->localization->matchDiscriminator($this->block);
			} catch (\Exception $e) {
				$object = null;
				throw $e;
			}
		}
	}

	public function __clone()
	{
		parent::__clone();

		if (! empty($this->id)) {

			$this->block = null;
			$this->localization = null;
//
//			$clonedMetadata = array();
//
//			foreach ($this->metadata as $metaItem) {
//				$clonedMetadata[] = clone $metaItem;
//			}
//
//			$this->metadata = new ArrayCollection($clonedMetadata);
		}
	}

	/**
	 * @param BlockProperty $parent
	 */
	public function setParent(BlockProperty $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return BlockProperty
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @return bool
	 */
	public function hasParent()
	{
		return $this->parent !== null;
	}
	
	/**
	 * @return Collections\Collection
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param BlockProperty $property
	 * @throws \LogicException
	 */
	public function addProperty(BlockProperty $property)
	{
		if ($this->properties->contains($property)) {
			throw new \LogicException("Property [{$property->getName()}] is already in set.");
		}

		$this->properties->add($property);
	}

	/**
	 * @internal
	 * @return string
	 */
	public function getHierarchicalName()
	{
		if ($this->parent === null) {
			return $this->name;
		}

		return $this->parent->getHierarchicalName() . '.' . $this->name;
	}

	/**
	 * To use in DQL queries.
	 *
	 * @internal
	 *
	 * @preUpdate
	 * @prePersist
	 */
	public function composeHierarchicalName()
	{
		$this->hierarchicalName = $this->getHierarchicalName();
	}

	/**
	 * Allows to iterate over sub properties.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return $this->properties->getIterator();
	}
}
