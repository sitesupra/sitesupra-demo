<?php

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\AuditedEntity;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;

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
	 * @OneToOne(targetEntity="Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract", cascade={"all"})
	 * @var ReferencedElement\ReferencedElementAbstract
	 */
	protected $referencedElement;
	
	/**
	 * @var ReferencedElement\ReferencedElementAbstract
	 */
	protected $overridenReferencedElement;
	
	/**
	 * Binds
	 * @param string $name
	 * @param BlockProperty $blockProperty
	 * @param ReferencedElement\ReferencedElementAbstract $referencedElement
	 */
	public function __construct($name, BlockProperty $blockProperty, ReferencedElement\ReferencedElementAbstract $referencedElement)
	{
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
	 * @return ReferencedElement\ReferencedElementAbstract
	 */
	public function getReferencedElement()
	{
		return $this->referencedElement;
	}

	/**
	 * @param ReferencedElement\ReferencedElementAbstract $referencedElement 
	 */
	public function setReferencedElement($referencedElement)
	{
		$this->referencedElement = $referencedElement;
	}
	
	public function getOwner()
	{
		return $this->blockProperty;
	}
	
	/**
	 * Used after cloning
	 * @param BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}

	public function __clone()
	{
		parent::__clone();

		if ( ! empty($this->id)) {
			if ( ! empty($this->referencedElement)) {
				$this->referencedElement = clone($this->referencedElement);
			}
		}
	}

}
