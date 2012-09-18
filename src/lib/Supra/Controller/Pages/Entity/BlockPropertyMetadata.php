<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Supra\Controller\Pages\Entity\Abstraction\OwnedEntityInterface;

/**
 * BlockPropertyMetadata
 * @Entity
 */
class BlockPropertyMetadata extends Entity implements AuditedEntityInterface, OwnedEntityInterface
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
	 * @OneToOne(targetEntity="Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract", cascade={"all"})
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
		if ( ! empty($this->overridenReferencedElement)) {
			return $this->overridenReferencedElement;
		}
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
	
	public function setOverridenReferencedElement($referencedElement)
	{
		$this->overridenReferencedElement = $referencedElement;
	}
	
	
}
