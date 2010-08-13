<?php

namespace Supra\Tests\NestedSet\Fixture;

/**
 * 
 */
class NestedSet
{
	public static function organizeTree($nodes)
	{
		$nodes['meat']->moveAsNextSiblingOf($nodes['fruit']);
		$nodes['pork']->moveAsLastChildOf($nodes['meat']);
		$nodes['meat']->addChild($nodes['fruit']);

		$nodes['fruit']->moveAsLastChildOf($nodes['food']);
		$nodes['meat']->moveAsLastChildOf($nodes['food']);

		$nodes['red']->moveAsLastChildOf($nodes['fruit']);
		$nodes['yellow']->moveAsNextSiblingOf($nodes['red']);

		$nodes['cherry']->moveAsLastChildOf($nodes['red']);
		$nodes['banana']->moveAsLastChildOf($nodes['yellow']);

		$nodes['beef']->moveAsNextSiblingOf($nodes['pork']);
		$nodes['pork']->moveAsNextSiblingOf($nodes['beef']);
	}
}