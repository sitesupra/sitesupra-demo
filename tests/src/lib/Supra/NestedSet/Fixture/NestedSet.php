<?php

namespace Supra\Tests\NestedSet\Fixture;

use Supra\NestedSet\ArrayRepository;

/**
 * 
 */
class NestedSet
{
	static function foodTree()
	{
		$rep = new ArrayRepository();

		$food = $rep->createNode('Food');
		$meat = $rep->createNode('Meat');
		$fruit = $rep->createNode('Fruit');
		$pork = $rep->createNode('Pork');
		$red = $rep->createNode('Red');
		$yellow = $rep->createNode('Yellow');
		$beef = $rep->createNode('Beef');
		$cherry = $rep->createNode('Cherry');
		$banana = $rep->createNode('Banana');

		$meat->moveAsNextSiblingOf($fruit);
		$pork->moveAsLastChildOf($meat);
		$meat->addChild($fruit);

		$fruit->moveAsLastChildOf($food);
		$meat->moveAsLastChildOf($food);

		$red->moveAsLastChildOf($fruit);
		$yellow->moveAsNextSiblingOf($red);

		$cherry->moveAsLastChildOf($red);
		$banana->moveAsLastChildOf($yellow);

		$beef->moveAsNextSiblingOf($pork);
		$pork->moveAsNextSiblingOf($beef);

		return $rep;
	}
}