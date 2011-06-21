<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 */
class TemplateBlock extends Abstraction\Block
{
	/**
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $locked = false;
	
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
		$this->validateLock();
	}
}