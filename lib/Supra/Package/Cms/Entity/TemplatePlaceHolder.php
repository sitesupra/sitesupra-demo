<?php

namespace Supra\Package\Cms\Entity;

/**
 * Template Place Holder
 * @Entity
 */
class TemplatePlaceHolder extends Abstraction\PlaceHolder
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::TEMPLATE_DISCR;
	
	/**
	 * @var integer
	 */
	protected $type = 0;

	/**
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $locked = false;


	/**
	 * Set locked value
	 * @param boolean $locked
	 */
	public function setLocked($locked = true)
	{
		$this->locked = $locked;
	}

	/**
	 * Get locked value
	 * @return boolean
	 */
	public function getLocked()
	{
		return $this->locked;
	}

}
