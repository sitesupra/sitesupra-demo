<?php

namespace Supra\Tests\NestedSet\Fixture;

use Supra\NestedSet\DoctrineRepository,
		Supra\Tests\NestedSet\Model,
		Doctrine\ORM\EntityManager;

/**
 * Testing fixture for database nested set
 */
class DoctrineNestedSet extends NestedSet
{
	/**
	 * @param EntityManager $em
	 * @return Model\ProductRepository
	 */
	static function foodTree(EntityManager $em)
	{
		$connection = $em->getConnection();
		
		$metadata = $em->getClassMetadata('Supra\Tests\NestedSet\Model\ProductAbstraction');
		$tableName = $metadata->table['name'];
		
		$sql = "DELETE FROM $tableName";
		$statement = $connection->prepare($sql);
		$statement->execute();
		
		// Faster inserts
		$sql = "INSERT INTO $tableName (id, lft, rgt, lvl, title, price, discr) VALUES
				(1, 1, 18, 0, 'Food', null, 'product'),
				(2, 12, 17, 1, 'Meat', null, 'product'),
				(3, 2, 11, 1, 'Fruit', null, 'product'),
				(4, 15, 16, 2, 'Pork', null, 'product'),
				(5, 3, 6, 2, 'Red', null, 'product'),
				(6, 7, 10, 2, 'Yellow', null, 'product'),
				(7, 13, 14, 2, 'Beef', null, 'product'),
				(8, 4, 5, 3, 'Cherry', null, 'product'),
				(9, 8, 9, 3, 'Banana', null, 'product')";

		$statement = $connection->prepare($sql);
		$statement->execute();
		
		// For postgresql
		$platform = $connection->getDatabasePlatform()->getName();
		
		if ($platform == 'pgsql') {
			$sql = "SELECT setval('product_id_seq', MAX(id)) FROM product";
			$statement = $connection->prepare($sql);
			$statement->execute();
		}

		/* @var $rep Model\ProductRepository */
		$rep = $em->getRepository('Supra\Tests\NestedSet\Model\Product');

		// This took too long to generate the fixture
		
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