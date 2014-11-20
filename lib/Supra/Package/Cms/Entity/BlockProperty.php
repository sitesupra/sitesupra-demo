<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\AuditedEntityInterface;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;
use Supra\Package\Cms\Editable\EditableInterface;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Localization;

use Supra\Controller\Pages\Exception;

/**
 * Block property class.
 * 
 * @Entity
 * //HasLifecycleCallbacks
 */
class BlockProperty extends VersionedEntity implements AuditedEntityInterface
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
	 * Content type (class name of Supra\Editable\EditableInterface class)
	 * @Column(type="string")
	 * @var string
	 */
	protected $type;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;
	
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
		
		$this->name = $name;
		$this->metadata = new ArrayCollection();
	}
	
//	/**
//	 * @PostLoad
//	 */
//	public function initializeEditable()
//	{
//		$this->setValue($this->value);
//	}

	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}

	/**
	 * @return Localization
	 */
	public function getOriginalLocalization()
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
	 * @deprecated use getEditableClass instead.
	 * @return string
	 */
	public function getType()
	{
		return $this->getEditableClass();
	}

	/**
	 * Set content type
	 * @param string $type 
	 */
	public function setType($type)
	{
		throw new \RuntimeException("Should not be used anymore");
		//$this->type = $type;
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
	 * @TODO: should we validate the value?
	 * @TODO: should we serialize arrays passed?
	 * 
	 * @param string $value
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
	
//	/**
//	 * @return EditableInterface
//	 */
//	public function getEditable()
//	{
//		return $this->editable;
//	}

	/**
	 * @param EditableInterface $editable
	 */
	public function setEditable(EditableInterface $editable)
	{
//		$editable->setContent($this->value);
//		$this->value = $editable->getStorableContent();
//		$this->editable = $editable;

		$this->type = get_class($editable);
	}

	/**
	 * @return string
	 */
	public function getEditableClass()
	{
		return $this->type;
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
			} catch (Exception\PagesControllerException $e) {
				$object = null;
				throw $e;
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		// If the owner block belongs to the owner localization, return block,
		// localization otherwise.
		if ($this->localization->equals($this->block->getPlaceHolder()->getMaster())) {
			return $this->block;
		}
		
		return $this->localization;
	}

//	public function __clone()
//	{
//		parent::__clone();
//
//		if (! empty($this->id)) {
//
////			$this->block = null;
////			$this->localization = null;
////
////			$clonedMetadata = array();
////
////			foreach ($this->metadata as $metaItem) {
////				$clonedMetadata[] = clone $metaItem;
////			}
////
////			$this->metadata = new ArrayCollection($clonedMetadata);
//		}
//	}

	/**
	 * Helper for the publishing process.
	 * Initializes proxy associations because not initialized proxies aren't merged by Doctrine.
	 *
	 * @return void
	 */
	public function initializeProxyAssociations()
	{
		if ($this->metadata instanceof PersistentCollection) {
			$this->metadata->initialize();
		}
	}
}
