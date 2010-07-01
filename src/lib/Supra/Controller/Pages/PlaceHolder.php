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
	protected $layoutPlaceHolder;
	 */

	/**
	 * @Column(name="layout_place_holder_name", type="string")
	 * @var string
	 */
	protected $layoutPlaceHolderName;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $locked = false;
}