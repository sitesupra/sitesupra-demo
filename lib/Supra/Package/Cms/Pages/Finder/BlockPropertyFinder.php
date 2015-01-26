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

namespace Supra\Package\Cms\Pages\Finder;

use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;
use Supra\Package\Cms\Entity\BlockProperty;

/**
 * BlockPropertyFinder
 */
class BlockPropertyFinder extends AbstractFinder
{
	/**
	 * @var LocalizationFinder
	 */
	private $localizationFinder;

	/**
	 * @var array
	 */
	protected $components = array();

	/**
	 * @param LocalizationFinder $localizationFinder
	 */
	public function __construct(LocalizationFinder $localizationFinder)
	{
		$this->localizationFinder = $localizationFinder;

		parent::__construct($localizationFinder->getEntityManager());
	}

	/**
	 * @return QueryBuilder
	 */
	protected function doGetQueryBuilder()
	{
		$qb = $this->localizationFinder->getQueryBuilder();
		$qb = clone($qb);

		$qb->from(BlockProperty::CN(), 'bp');
		$qb->andWhere('bp.localization = l');
		$qb->join('bp.localization', 'l3');
		$qb->join('bp.block', 'b');
		$qb->join('b.placeHolder', 'ph');
		$qb->leftJoin('bp.metadata', 'bpm');
		$qb->leftJoin('bpm.referencedElement', 're');
		$qb->join('l3.master', 'e3');
		$qb->join('l3.path', 'lp3');

		$qb->select('bp, b, l3, e3, bpm, ph, lp3, re');

		$qb = $this->prepareComponents($qb);

		return $qb;
	}

	public function addFilterByComponent($component, $fields = null)
	{
		$this->components[$component] = (array) $fields;
	}

	protected function prepareComponents(QueryBuilder $qb)
	{
		if ( ! empty($this->components)) {
			$or = $qb->expr()->orX();
			$i = 1;

			foreach ($this->components as $component => $fields) {
				$and = $qb->expr()->andX();
				$and->add("b.componentClass = :component_$i");
				$qb->setParameter("component_$i", $component);

				if ( ! empty($fields)) {
					$and->add("bp.name IN (:fields_$i)");
					$qb->setParameter("fields_$i", $fields, Connection::PARAM_STR_ARRAY);
				}

				$or->add($and);
				$i ++;
			}

			$qb->andWhere($or);
		}

		return $qb;
	}

}
