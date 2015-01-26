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

namespace Supra\Package\Cms\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\ApplicationPage;
use Supra\Package\Cms\Entity\GroupPage;

/**
 * Page repository, used for pages, applications, groups, NOT templates
 */
class PageRepository extends PageAbstractRepository
{
	/**
	 * @param EntityManager $em
	 * @param ClassMetadata $class
	 */
	public function __construct(EntityManager $em, ClassMetadata $class)
	{
		parent::__construct($em, $class);
		
		// Bind additional conditions to the nested set repository
		$entities = array(
			Page::CN(),
			ApplicationPage::CN(),
			GroupPage::CN()
		);
		
		$orList = array();
		
		foreach ($entities as $entityName) {
			$orList[] = "e INSTANCE OF $entityName";
		}
		
		$additionalCondition = implode(' OR ', $orList);
		$additionalConditionSql = "discr IN ('page', 'application', 'group')";

		$this->nestedSetRepository->setAdditionalCondition($additionalCondition, $additionalConditionSql);
	}
}