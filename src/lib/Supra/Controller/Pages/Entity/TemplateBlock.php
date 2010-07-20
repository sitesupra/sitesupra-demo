<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 * @Table(name="template_block")
 */
class TemplateBlock extends Abstraction\Block
{
	/**
	 * @ManyToOne(targetEntity="TemplatePlaceHolder", inversedBy="blocks")
	 * @var TemplatePlaceHolder
	 */
	protected $placeHolder;

	/**
	 * @Column(type="boolean")
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