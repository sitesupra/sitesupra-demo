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
 * @Entity
 * @method ApplicationPage getMaster()
 */
class ApplicationLocalization extends PageLocalization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::APPLICATION_DISCR;
	
//	/**
//     * @OneToMany(
//	 *		targetEntity="Supra\Package\Cms\Entity\ApplicationLocalizationParameter", 
//	 *		mappedBy="localization", 
//	 *		cascade={"persist", "remove"}, 
//	 *		indexBy="name",
//	 *		fetch="LAZY"
//	 * )
//	 * 
//     * @var \Doctrine\Common\Collections\Collection
//     */
//	protected $parameters;
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct($locale) 
	{
		parent::__construct($locale);	
//		$this->parameters = new Collections\ArrayCollection();
	}
	
//	/**
//	 * @return \Doctrine\Common\Collections\Collection
//	 */
//	public function getParameterCollection()
//	{
//		return $this->parameters;
//	}
	
//	/**
//	 * @param \Supra\Controller\Pages\Entity\ApplicationLocalizationParameter $parameter
//	 */
//	public function addParameterToCollection(ApplicationLocalizationParameter $parameter)
//	{
//		$this->parameters->set($parameter->getName(), $parameter);
//	}
	
//	/**
//	 * @param string $name
//	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalizationParameter | null
//	 */
//	public function getParameter($name)
//	{
//		if ($this->parameters->offsetExists($name)) {
//			return $this->parameters
//					->offsetGet($name);
//		}
//		
//		return null;
//	}
	
//	/**
//	 * @param string $name
//	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalizationParameter
//	 */
//	public function getOrCreateParameter($name)
//	{
//		$parameter = $this->getParameter($name);
//		
//		if ($parameter === null) {	
//			$parameter = new ApplicationLocalizationParameter($name);
//			$parameter->setApplicationLocalization($this);
//		}
//		
//		return $parameter;
//	}
	
//	/**
//	 * @param string $name
//	 * @param mixed $default
//	 * @return mixed
//	 */
//	public function getParameterValue($name, $default = null)
//	{
//		if ($this->parameters->offsetExists($name)) {
//			return $this->parameters
//					->offsetGet($name)
//					->getValue();
//		}
//		
//		return $default;
//	}
}
