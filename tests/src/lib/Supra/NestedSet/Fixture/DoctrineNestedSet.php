<?php

namespace Supra\Tests\NestedSet\Fixture;

use Supra\NestedSet\DoctrineRepository,
		Supra\Tests\NestedSet\Model,
		Doctrine\ORM\EntityManager;

/**
 * 
 */
class DoctrineNestedSet extends NestedSet
{
	static function foodTree(EntityManager $em)
	{
		$rep = $em->getRepository('Supra\Tests\NestedSet\Model\Product');

		$nodes['food'] = new Model\Product('Food');
		$nodes['meat'] = new Model\Product('Meat');
		$nodes['fruit'] = new Model\Product('Fruit');
		$nodes['pork'] = new Model\Product('Pork');
		$nodes['red'] = new Model\Product('Red');
		$nodes['yellow'] = new Model\Product('Yellow');
		$nodes['beef'] = new Model\Product('Beef');
		$nodes['cherry'] = new Model\Product('Cherry');
		$nodes['banana'] = new Model\Product('Banana');

		foreach ($nodes as &$node) {
			$em->persist($node);
		}

		self::organizeTree($nodes);

		return $rep;
	}
}