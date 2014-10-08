<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 */
class TemplateBlock extends Abstraction\Block
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::TEMPLATE_DISCR;
	
	/**
	 * Used when block must be created on public scheme triggered by page publish.
	 * Objects with temporary flag are ignored when page is generated.
	 * @Column(name="temporary", type="boolean")
	 * @var boolean
	 */
	protected $temporary = false;
	
	/**
	 * Get locked value
	 * @return boolean
	 */
	public function getLocked()
	{
		return $this->locked;
	}
	
	/**
	 * Set locked value
	 * @param boolean $locked
	 */
	public function setLocked($locked = true)
	{
		$this->locked = $locked;
	}

	/**
	 * @return boolean
	 */
	public function getTemporary()
	{
		return $this->temporary;
	}

	/**
	 * @param boolean $temporary 
	 */
	public function setTemporary($temporary)
	{
		$this->temporary = $temporary;
	}

}
