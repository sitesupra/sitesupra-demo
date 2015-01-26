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

use Supra\Editable\EditableAbstraction;
use Supra\Package\Cms\Editable\Editable;

/**
 * @Entity
 */
class FileProperty extends Abstraction\Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $value;

	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\File", inversedBy="properties")
	 * @var Abstraction\File
	 */
	protected $file;
	
	/**
	 * @param string $name
	 * @param Abstraction\File $file
	 */
	public function __construct($name, Abstraction\File $file)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->file = $file;
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
	 * @return Abstraction\File
	 */
	public function getFile()
	{
		return $this->file;
	}
	
	/**
	 * @param Abstraction\File $file
	 */
	public function setFile(Abstraction\File $file)
	{
		$this->file = $file;
	}
	
	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
}
