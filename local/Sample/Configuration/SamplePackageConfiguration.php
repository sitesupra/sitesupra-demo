<?php

namespace Sample\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SupraPackageCmsConfiguration implements ConfigurationInterface
{
	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
//
//		$treeBuilder->root('sample')
//			->children()
//				->arrayNode('theme')
//				->scalarNode('prefix')->isRequired()->end()
//				->arrayNode('css_pack')->prototype('scalar')->end()->end()
//				->arrayNode('js_pack')->prototype('scalar')->end()->end()
//				->arrayNode('theme')
//					->children()
//						->scalarNode('name')->isRequired()->end()
//						->scalarNode('urlBase')->end()
//						->arrayNode('layouts')->prototype('array')
//							->children()
//								->scalarNode('name')->isRequired()->end()
//								->scalarNode('title')->isRequired()->end()
//							->end()
//						->end()
//					->end()
//			->end();

		return $treeBuilder;
	}

}
