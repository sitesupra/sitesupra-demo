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

use Doctrine\ORM\EntityRepository;
use Supra\Core\NestedSet\DoctrineRepository;
use Supra\Core\NestedSet\RepositoryInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\EntityManager;
use Supra\Core\NestedSet\SearchCondition\DoctrineSearchCondition;
use Supra\Core\NestedSet\SelectOrder\DoctrineSelectOrder;

/**
 * Abstract page repository
 */
abstract class PageAbstractRepository extends EntityRepository implements RepositoryInterface
{
	/**
	 * @var DoctrineRepository
	 */
	protected $nestedSetRepository;

	/**
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		parent::__construct($em, $class);
		$this->nestedSetRepository = new DoctrineRepository($em, $class);
	}

	/**
	 * @return DoctrineRepository
	 */
	public function getNestedSetRepository()
	{
		return $this->nestedSetRepository;
	}

	/**
	 * Output the dump of the whole node tree
	 * @return string
	 */
	public function drawTree()
	{
		$output = $this->nestedSetRepository->drawTree();
		return $output;
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		$this->nestedSetRepository->free($node);
	}

	/**
	 * Prepares the object to be available to garbage collector.
	 * The further work with the repository will raise errors.
	 */
	public function destroy()
	{
		$this->__call('destroy', array());
		$this->nestedSetRepository = null;
	}
	
	/**
	 * Get root nodes
	 * @return array
	 */
	public function getRootNodes()
	{
		$filter = new DoctrineSearchCondition();
		$filter->levelEqualsTo(0);
		
		$order = new DoctrineSelectOrder();
		$order->byLeftAscending();
		
		$rootNodes = $this->nestedSetRepository->search($filter, $order);
		
		return $rootNodes;
	}
}
