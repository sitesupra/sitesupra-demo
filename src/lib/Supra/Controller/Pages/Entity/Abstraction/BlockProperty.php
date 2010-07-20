<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

/**
 * Block property abstract class
 * @MappedSuperclass
 */
abstract class BlockProperty extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @ManyToOne(targetEntity="Page")
	 * @var Data
	 */
	protected $data;
}