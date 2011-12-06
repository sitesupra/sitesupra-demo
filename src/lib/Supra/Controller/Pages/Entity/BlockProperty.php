<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Editable\EditableInterface;
use Doctrine\Common\Collections;
use Supra\Controller\Pages\Entity\Abstraction\OwnedEntityInterface;

/**
 * Block property class.
 * @Entity
 */
class BlockProperty extends Entity implements AuditedEntityInterface, OwnedEntityInterface
{
	/**
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\Abstraction\Localization", inversedBy="blockProperties")
	 * @JoinColumn(nullable=false)
	 * @var Localization
	 */
	protected $localization;

	/**
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\Abstraction\Block", inversedBy="blockProperties", cascade={"persist"})
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
	 * @OneToMany(targetEntity="BlockPropertyMetadata", mappedBy="blockProperty", cascade={"all"}, fetch="EAGER", indexBy="name")
	 * @var Collections\Collection
	 */
	protected $metadata;
	
	/**
	 * @Column(type="object", nullable=true)
	 * @var EditableInterface
	 */
	protected $editable;

	/**
	 * Constructor
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();
		$this->name = $name;
		$this->resetMetadata();
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
	 * @return Collections\Collection
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}
	
	/**
	 * Resets metadata collection to empty collection
	 */
	public function resetMetadata()
	{
		$this->metadata = new Collections\ArrayCollection();
	}
	
	/**
	 * @param BlockPropertyMetadata $metadata
	 */
	public function addMetadata(BlockPropertyMetadata $metadata)
	{
		$this->metadata->add($metadata);
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
	 * Get content type
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set content type
	 * @param string $type 
	 */
	public function setType($type)
	{
		throw new \RuntimeException("Should not be used anymore");
		$this->type = $type;
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
	 * TODO: should we validate the value? should we serialize arrays passed?
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * @return EditableInterface
	 */
	public function getEditable()
	{
		return $this->editable;
	}

	/**
	 * @param EditableInterface $editable
	 */
	public function setEditable(EditableInterface $editable)
	{
		$editable->setContent($this->value);
		$this->editable = $editable;
		$this->type = get_class($editable);
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
	 * Doctrine safe clone method with cloning of children
	 */
//	public function __clone()
//	{
//		if ( ! empty($this->id)) {
//			$this->regenerateId();
//			$this->block = null;
//			$this->localization = null;
//		}
//	}
	
	public function getOwner()
	{
		return $this->block;
	}

}
