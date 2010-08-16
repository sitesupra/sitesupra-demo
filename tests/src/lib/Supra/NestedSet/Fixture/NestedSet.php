<?php

namespace Supra\Tests\NestedSet\Fixture;

/**
 * 
 */
class NestedSet
{
	public static function organizeTree($nodes)
	{
		$nodes['pork']->moveAsLastChildOf($nodes['meat']);

		$nodes['food']->addChild($nodes['fruit']);
		$nodes['food']->addChild($nodes['meat']);

		$nodes['red']->moveAsLastChildOf($nodes['fruit']);
		$nodes['yellow']->moveAsNextSiblingOf($nodes['red']);

		$nodes['cherry']->moveAsLastChildOf($nodes['red']);
		$nodes['banana']->moveAsLastChildOf($nodes['yellow']);

		$nodes['beef']->moveAsPrevSiblingOf($nodes['pork']);
	}
}