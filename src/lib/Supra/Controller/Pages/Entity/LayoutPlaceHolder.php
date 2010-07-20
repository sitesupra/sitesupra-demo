<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Template place holder class
 * @Entity
 * @Table(name="layout_place_holder")
 */
class LayoutPlaceHolder extends Abstraction\Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @ManyToOne(targetEntity="Layout", inversedBy="placeHolders")
	 * @var Layout
	 */
	protected $layout;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set name
	 * @param string $name
	 */
	public function setName($name)
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