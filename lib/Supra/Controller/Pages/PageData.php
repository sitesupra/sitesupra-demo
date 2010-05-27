<?php

namespace Supra\Controller\Pages;

/**
 * PageData class
 * @Entity
 */
class PageData
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $id;

	/**
	 * @OneToOne(targetEntity="Page")
	 */
	protected $page;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	
}