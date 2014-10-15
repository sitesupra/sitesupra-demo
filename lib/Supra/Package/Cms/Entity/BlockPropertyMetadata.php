<?php

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\AuditedEntity;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract;

/**
 * BlockPropertyMetadata
 * @Entity
 */
class BlockPropertyMetadata extends VersionedEntity implements
	AuditedEntity
	//OwnedEntityInterface
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @ManyToOne(targetEntity="BlockProperty", inversedBy="metadata")
	 * @var BlockProperty
	 */
	protected $blockProperty;
	
	/**
	 * @OneToOne(
	 *		targetEntity="Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract",
	 *		cascade={"all"}
	 * )
	 *
	 * @JoinColumn(name="referencedElement_id", referencedColumnName="id", nullable=true)
	 *
	 * @var ReferencedElementAbstract
	 */
	protected $referencedElement;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * @param string $name
	 * @param BlockProperty $blockProperty
	 * @param null|ReferencedElementAbstract $referencedElement
	 */
	public function __construct(
			$name,
			BlockProperty $blockProperty,
			ReferencedElementAbstract $referencedElement = null
	) {
		parent::__construct();
		
		$this->name = $name;
		$this->blockProperty = $blockProperty;
		$this->referencedElement = $referencedElement;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
    
	/**
	 * @param string $name
	 */
    public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return BlockProperty
	 */
	public function getBlockProperty()
	{
		return $this->blockProperty;
	}

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
	
	/**
	 * @return ReferencedElementAbstract
	 */
	public function getReferencedElement()
	{
		return $this->referencedElement;
	}

	/**
	 * @param ReferencedElementAbstract $referencedElement 
	 */
	public function setReferencedElement($referencedElement)
	{
		$this->referencedElement = $referencedElement;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @inehritDoc
	 */
	public function getOwner()
	{
		return $this->blockProperty;
	}

	/**
	 * Clones referenced element too.
	 */
	public function __clone()
	{
		parent::__clone();

		if ( ! empty($this->id)
				&& $this->referencedElement !== null) {

			$this->referencedElement = clone $this->referencedElement;
		}
	}

}
