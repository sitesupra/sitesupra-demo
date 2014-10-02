<?php

namespace Supra\Package\Cms\Configuration;

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

		$treeBuilder->root('cms')
			->children()
				->scalarNode('prefix')->isRequired()->end()
				->arrayNode('css_pack')->prototype('scalar')->end()->end()
				->arrayNode('js_pack')->prototype('scalar')->end()->end()
				->scalarNode('active_theme')->end()
			->end();

		return $treeBuilder;
	}

}
