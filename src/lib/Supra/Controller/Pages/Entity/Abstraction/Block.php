<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Request,
		Supra\Controller\Response;

/**
 * Block database entity abstraction
 * @MappedSuperclass
 */
abstract class Block extends Entity
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
	protected $component;

	/**
	 * @var PlaceHolder
	 */
	protected $placeHolder;

	/**
	 * Page block is not locked at any time
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
	}

	/**
	 * Sets place holder
	 * @param PlaceHolder $placeHolder
	 */
	public function setPlaceHolder(PlaceHolder $placeHolder)
	{
		if ($this->writeOnce($this->placeHolder, $placeHolder)) {
			$this->placeHolder->addBlock($this);
		}
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getComponent()
	{
		return $this->component;
	}

	/**
	 * @param string $component
	 */
	public function setComponent($component)
	{
		$this->component = $component;
	}

}