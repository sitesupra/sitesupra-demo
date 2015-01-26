<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract;

/**
 * BlockPropertyMetadata
 * @Entity
 */

class BlockPropertyMetadata extends Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @ManyToOne(targetEntity="BlockProperty", inversedBy="metadata")
	 * @var BlockProperty
	 */
	protected $blockProperty;
	
	/**
	 * @OneToOne(
	 *		targetEntity="Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract",
	 *		orphanRemoval=true,
	 *		cascade={"persist", "remove"}
	 * )
	 *
	 * @JoinColumn(name="referencedElement_id", referencedColumnName="id", nullable=true)
	 *
	 * @var ReferencedElementAbstract
	 */
	protected $referencedElement;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * @param string $name
	 * @param BlockProperty $blockProperty
	 * @param null|ReferencedElementAbstract $referencedElement
	 */
	public function __construct(
			$name,
			BlockProperty $blockProperty,
			ReferencedElementAbstract $referencedElement = null
	) {
		parent::__construct();
		$this->name = $name;
		$this->blockProperty = $blockProperty;
		$this->referencedElement = $referencedElement;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
    
	/**
	 * @param string $name
	 */
    public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return BlockProperty
	 */
	public function getBlockProperty()
	{
		return $this->blockProperty;
	}

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
	
	/**
	 * @return ReferencedElementAbstract
	 */
	public function getReferencedElement()
	{
		return $this->referencedElement;
	}

	/**
	 * @param ReferencedElementAbstract $referencedElement 
	 */
	public function setReferencedElement($referencedElement)
	{
		$this->referencedElement = $referencedElement;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

}
