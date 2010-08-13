<?php

namespace Supra\Tests\NestedSet\Fixture;

use Supra\NestedSet\ArrayRepository;

/**
 * 
 */
class ArrayNestedSet extends NestedSet
{
	static function foodTree()
	{
		$rep = new ArrayRepository();

		$nodes['food'] = $rep->createNode('Food');
		$nodes['meat'] = $rep->createNode('Meat');
		$nodes['fruit'] = $rep->createNode('Fruit');
		$nodes['pork'] = $rep->createNode('Pork');
		$nodes['red'] = $rep->createNode('Red');
		$nodes['yellow'] = $rep->createNode('Yellow');
		$nodes['beef'] = $rep->createNode('Beef');
		$nodes['cherry'] = $rep->createNode('Cherry');
		$nodes['banana'] = $rep->createNode('Banana');

		self::organizeTree($nodes);

		return $rep;
	}
}