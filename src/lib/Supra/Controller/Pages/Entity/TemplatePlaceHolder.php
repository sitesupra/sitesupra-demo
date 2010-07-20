<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Template Place Holder
 * @Entity
 * @Table(name="template_place_holder")
 */
class TemplatePlaceHolder extends Abstraction\PlaceHolder
{
	/**
	 * @ManyToOne(targetEntity="Template")
	 * @var Template
	 */
	protected $template;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * @OneToMany(targetEntity="PageBlock", mappedBy="placeHolder", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blocks;

	/**
	 * Set template
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		if ($this->writeOnce($this->template, $template)) {
			$template->addPlaceHolder($this);
		}
	}

	/**
	 * Get template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Set master object
	 * @param Abstraction\Page $master
	 */
	public function setMaster(Abstraction\Page $master)
	{
		$this->isInstanceOf($master, __NAMESPACE__ . '\Template', __METHOD__);
		$this->setTemplate($master);
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
	 * Get locked value
	 * @return boolean
	 */
	public function getLocked()
	{
		return $this->locked;
	}

	/**
	 * Checks block object instance
	 * @param $block Abstraction\Block
	 * @throws Exception on failure
	 */
	protected function checkBlock(Abstraction\Block $block)
	{
		$this->isInstanceOf($block, __NAMESPACE__ . '\TemplateBlock', __METHOD__);
	}

}