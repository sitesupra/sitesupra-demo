<?php

namespace Supra\Controller\Pages\Entity;

/**
 * BlockPropertyMetadata
 * @Entity
 */
class BlockPropertyMetadata extends Abstraction\Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @ManyToOne(targetEntity="BlockProperty")
	 * @var BlockProperty
	 */
	protected $blockProperty;
	
	/**
	 * @OneToOne(targetEntity="Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract", cascade={"all"})
	 * @var ReferencedElement\ReferencedElementAbstract
	 */
	protected $referencedElement;
	
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

}
