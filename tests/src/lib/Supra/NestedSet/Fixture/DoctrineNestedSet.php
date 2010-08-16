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
		// Faster inserts
		$sql = "INSERT INTO product (id, lft, rgt, lvl, title, price) VALUES
				(1, 1, 18, 0, 'Food', null),
				(2, 12, 17, 1, 'Meat', null),
				(3, 2, 11, 1, 'Fruit', null),
				(4, 15, 16, 2, 'Pork', null),
				(5, 3, 6, 2, 'Red', null),
				(6, 7, 10, 2, 'Yellow', null),
				(7, 13, 14, 2, 'Beef', null),
				(8, 4, 5, 3, 'Cherry', null),
				(9, 8, 9, 3, 'Banana', null)";

		$connection = $em->getConnection();
		$statement = $connection->prepare($sql);
		$statement->execute();

		$rep = $em->getRepository('Supra\Tests\NestedSet\Model\Product');

//		$nodes['food'] = new Model\Product('Food');
//		$nodes['meat'] = new Model\Product('Meat');
//		$nodes['fruit'] = new Model\Product('Fruit');
//		$nodes['pork'] = new Model\Product('Pork');
//		$nodes['red'] = new Model\Product('Red');
//		$nodes['yellow'] = new Model\Product('Yellow');
//		$nodes['beef'] = new Model\Product('Beef');
//		$nodes['cherry'] = new Model\Product('Cherry');
//		$nodes['banana'] = new Model\Product('Banana');
//
//		foreach ($nodes as &$node) {
//			$em->persist($node);
//		}
//
//		self::organizeTree($nodes);

		return $rep;
	}
}