<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 */
class PageBlock extends Abstraction\Block
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::PAGE_DISCR;
	
	/**
	 * This property is always false for page block
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $inactive = false;
	
	/**
	 * @param boolean $inactive
	 */
	public function setInactive($inactive)
	{
		$this->inactive = $inactive;
	}
	
	/**
	 * @return boolean
	 */
	public function isInactive()
	{
		return $this->inactive === true;
	}
}