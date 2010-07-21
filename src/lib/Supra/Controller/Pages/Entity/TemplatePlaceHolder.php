<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Template Place Holder
 * @Entity
 */
class TemplatePlaceHolder extends Abstraction\PlaceHolder
{

	/**
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * Set template
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->setMaster($template);
	}

	/**
	 * Get template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->getMaster();
	}

	/**
	 * Set locked value
	 * @param boolean $locked
	 */
	public function setLocked($locked = true)
	{
		$this->locked = (bool)$locked;
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