<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Template place holder class
 * @Entity
 */
class LayoutPlaceHolder extends Abstraction\Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @ManyToOne(targetEntity="Layout", inversedBy="placeHolders")
	 * @JoinColumn(name="layout_id", referencedColumnName="id", nullable=false)
	 * @var Layout
	 */
	protected $layout;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();
		$this->setName($name);
	}

	/**
	 * Set name
	 * @param string $name
	 */
	protected function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get layout
	 * @param Layout $layout
	 */
	public function setLayout(Layout $layout)
	{
		if ($this->writeOnce($this->layout, $layout)) {
			$this->layout->addPlaceHolder($this);
		}
	}

	/**
	 * Set layout
	 * @return Layout
	 */
	public function getLayout()
	{
		return $this->layout;
	}
}