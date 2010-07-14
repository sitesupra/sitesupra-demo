<?php

namespace Supra\Controller\Pages;

/**
 * Page and template place holder data abstraction
 * @MappedSuperclass
 */
abstract class PlaceHolder extends EntityAbstraction
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var int
	 */
	protected $id;

	/**
	 * @ManyToOne(targetEntity="LayoutPlaceHolder")
	 * @JoinColumn(name="layout_place_holder_id")
	 * @var LayoutPlaceHolder
	 * NOTE: removed because decided to specify layout place holder by name not object so layout could be changed with no hassle
	protected $layoutPlaceHolder;
	 */

	/**
	 * @Column(name="name", type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * Set layout place holder name
	 * @param string $Name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Get layout place holder name
	 * @return string
	 */
	public function getName() {
		return $this->name;
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
	 * Set master object
	 * @param PageAbstraction $master
	 */
	abstract public function setMaster(PageAbstraction $master);

}