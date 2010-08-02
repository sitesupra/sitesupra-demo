<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 */
class TemplateBlock extends Abstraction\Block
{
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