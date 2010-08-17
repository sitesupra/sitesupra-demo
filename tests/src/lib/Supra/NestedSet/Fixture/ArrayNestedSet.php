<?php

namespace Supra\Tests\NestedSet\Fixture;

use Supra\NestedSet\ArrayRepository;

/**
 * Fixture for array nested set test
 */
class ArrayNestedSet extends NestedSet
{
	/**
	 * @return ArrayRepository
	 */
	static function foodTree()
	{
		$rep = new ArrayRepository();

		$nodes['banana'] = $rep->createNode('Banana');
		$nodes['cherry'] = $rep->createNode('Cherry');
		$nodes['beef'] = $rep->createNode('Beef');
		$nodes['yellow'] = $rep->createNode('Yellow');
		$nodes['red'] = $rep->createNode('Red');
		$nodes['pork'] = $rep->createNode('Pork');
		$nodes['fruit'] = $rep->createNode('Fruit');
		$nodes['meat'] = $rep->createNode('Meat');
		$nodes['food'] = $rep->createNode('Food');

		self::organizeTree($nodes);

		return $rep;
	}
}