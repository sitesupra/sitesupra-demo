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

/**
 * Page containing an application
 * @Entity
 */
class ApplicationPage extends Page
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::APPLICATION_DISCR;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $applicationId;

	/**
	 * @param string $applicationId
	 */
	public function __construct($applicationId)
	{
		parent::__construct();
		$this->applicationId = $applicationId;
	}

	/**
	 * @return string
	 */
	public function getApplicationId()
	{
		return $this->applicationId;
	}	
}
